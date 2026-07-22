<?php

namespace App\Support\Historis;

use App\Support\DipaBudgetOptionService;
use Illuminate\Support\Str;

/**
 * Pembaca bundel arsip tagihan (scan SILABI): ekstrak gambar halaman dari PDF,
 * OCR via Tesseract, lalu parse field-field SPP BLU untuk mengisi form
 * Input Tagihan Historis otomatis. Nama file dipakai sebagai pelengkap dan
 * fallback saat OCR tidak tersedia/terbaca.
 */
class TagihanArsipReader
{
    /**
     * Maksimal halaman bundel yang di-OCR. Bundel gabungan multi-SPP bisa
     * berisi 15+ halaman dengan lampiran nominatif di bagian belakang —
     * batas terlalu kecil membuat peserta tidak pernah terbaca.
     */
    private const MAX_HALAMAN = 20;

    /** Pangkat TNI/Polri yang dikenal — dipakai memisahkan pangkat dari jabatan. */
    private const PANGKAT_DIKENAL = [
        'Letda Pom', 'Letda', 'Lettu', 'Kapten', 'Serka', 'Serma', 'Sertu', 'Serda',
        'Peltu', 'Pelda', 'Praka', 'Pratu', 'Prada', 'Kopka', 'Koptu', 'Kopda',
        'Aiptu', 'Aipda', 'Bripka', 'Brigadir', 'Briptu', 'Bripda', 'Ipda', 'Iptu', 'AKP', 'Kompol',
    ];

    /**
     * @return array{fields: array<string, mixed>, potongan: array<int, array{nama: string, nominal: float}>, preview_jpeg: ?string, warnings: array<int, string>, ocr_aktif: bool}
     */
    public function read(string $pdfPath, ?string $namaFileAsli = null): array
    {
        $warnings = [];
        $namaFileAsli ??= basename($pdfPath);

        $halaman = $this->ekstrakJpegHalaman($pdfPath);
        $previewJpeg = $halaman[0] ?? null;

        $ocrAktif = $this->tesseractTersedia();
        $teks = '';
        $teksPerHalaman = [];
        if ($ocrAktif) {
            foreach ($halaman as $img) {
                $hasilHalaman = $this->ocr($img);

                // Halaman lampiran (nominatif dsb.) kerap discan landscape.
                // Bila hasil tegak tidak meyakinkan dan OSD menyarankan rotasi,
                // OCR ulang versi terputar lalu pakai yang kata bermaknanya
                // terbanyak. Jumlah token mentah bukan ukuran yang aman:
                // halaman miring justru menghasilkan RATUSAN token sampah,
                // sedangkan halaman tegak asli selalu kaya kata bermakna.
                if ($this->kataBermakna($hasilHalaman) < 60) {
                    $rotasi = $this->deteksiRotasi($img);
                    if ($rotasi !== 0 && $this->putarGambar($img, $rotasi)) {
                        $hasilPutar = $this->ocr($img);
                        if ($this->kataBermakna($hasilPutar) > $this->kataBermakna($hasilHalaman)) {
                            $hasilHalaman = $hasilPutar;
                        } else {
                            $this->putarGambar($img, -$rotasi); // kembalikan
                        }
                    }
                }

                $teksPerHalaman[] = $hasilHalaman;
                $teks .= $hasilHalaman . "\n";
            }
        } else {
            $warnings[] = 'OCR tidak aktif (binary Tesseract tidak ditemukan) — hanya nama file yang dibaca.';
        }

        $dariNama = $this->parseNamaFile($namaFileAsli);
        $fields = $this->parseTeks($teks, $warnings);

        // Bundel gabungan: satu PDF berisi beberapa SPP sekaligus (nama file
        // berpola rentang, mis. "0135-0136. …" — perjaldin taxi + uang harian).
        // Digabung menjadi SATU tagihan: tiap SPP menjadi satu komponen biaya,
        // nilai tagihan = penjumlahan seluruh SPP.
        $komponen = $this->deteksiKomponen($teks);
        if (count($komponen) > 1) {
            $fields['total_bruto'] = array_sum(array_column($komponen, 'nominal')) ?: $fields['total_bruto'];
            $nettoSegmen = array_column($komponen, 'netto');
            $fields['total_netto'] = in_array(null, $nettoSegmen, true) ? null : array_sum($nettoSegmen);
            $fields['potongan'] = array_merge([], ...array_column($komponen, 'potongan'));
            if (! empty($fields['deskripsi'])) {
                // Uraian tiap SPP berbeda pada akhiran ("… - Taxi") — deskripsi
                // tagihan gabungan memakai bagian yang sama saja.
                $fields['deskripsi'] = trim(preg_replace('/\s*-\s*[^\-]{2,60}$/', '', $fields['deskripsi'])) ?: $fields['deskripsi'];
            }
            $warnings[] = sprintf(
                'Terdeteksi %d SPP dalam satu bundel — digabung menjadi satu tagihan dengan %d komponen; periksa rincian pada kartu Komponen Biaya.',
                count($komponen), count($komponen)
            );
        }

        // Fallback / pelengkap dari nama file.
        $fields['deskripsi'] = $fields['deskripsi'] ?? $dariNama['deskripsi'] ?? null;
        $fields['pihak_nama'] = $fields['pihak_nama'] ?? $dariNama['vendor'] ?? null;
        $fields['total_bruto'] = $fields['total_bruto'] ?? $dariNama['bruto'] ?? null;
        $fields['register_nomor_urut'] = $dariNama['urut'] ?? null;

        // Validasi silang bruto OCR vs nama file.
        if (($dariNama['bruto'] ?? null) && ($fields['total_bruto'] ?? null)
            && (float) $dariNama['bruto'] !== (float) $fields['total_bruto']) {
            $warnings[] = sprintf(
                'Bruto hasil OCR (Rp %s) berbeda dengan bruto pada nama file (Rp %s) — periksa kembali.',
                number_format((float) $fields['total_bruto'], 0, ',', '.'),
                number_format((float) $dariNama['bruto'], 0, ',', '.')
            );
        }

        // Netto vs bruto − potongan.
        $potongan = $fields['potongan'];
        unset($fields['potongan']);
        $totalPotongan = array_sum(array_column($potongan, 'nominal'));
        if (($fields['total_netto'] ?? null) && ($fields['total_bruto'] ?? null)
            && abs(((float) $fields['total_bruto'] - $totalPotongan) - (float) $fields['total_netto']) > 1) {
            $warnings[] = 'Total Pembayaran pada berkas tidak sama dengan bruto − potongan hasil baca — periksa baris potongan.';
        }

        // Turunan nomor dokumen: pada berkas SILABI, SPP & SPM memakai nomor
        // yang sama; SP2D disarankan mengikuti pola nomor SPP.
        if (! empty($fields['nomor_spp'])) {
            $fields['nomor_spm'] = $fields['nomor_spm'] ?? $fields['nomor_spp'];
            $fields['nomor_sp2d'] = $fields['nomor_sp2d']
                ?? preg_replace('/^SPM-BLU/', 'SP2D-BLU', $fields['nomor_spp']);
        }
        if (! empty($fields['tanggal_spp'])) {
            $fields['tanggal_spm'] = $fields['tanggal_spm'] ?? $fields['tanggal_spp'];
            $fields['tanggal_npi'] = $fields['tanggal_npi'] ?? $fields['tanggal_spp'];
            $fields['tanggal_sp2d'] = $fields['tanggal_sp2d'] ?? $fields['tanggal_spp'];
        }

        // Cocokkan kode COA hasil OCR ke item DIPA aktif.
        $fields['dipa_revision_item_id'] = null;
        if (! empty($fields['kode_coa'])) {
            $fields['dipa_revision_item_id'] = $this->cocokkanCoa($fields['kode_coa']);
            if ($fields['dipa_revision_item_id'] === null) {
                $warnings[] = 'Kode COA ' . $fields['kode_coa'] . ' tidak ditemukan pada item DIPA aktif — pilih manual.';
            }
        }

        // Peserta/penerima dari halaman nominatif (honor/perjaldin).
        $peserta = [];
        foreach ($teksPerHalaman as $teksHalaman) {
            $baris = $this->parsePeserta($teksHalaman);
            if (count($baris) > count($peserta)) {
                $peserta = $baris; // ambil halaman dengan tabel terbanyak
            }
        }

        // Nominatif perjalanan dinas berlayout dua baris per orang (nama di
        // atas baris datanya) — mode blok seragam (PSM 6) nyaris tak membaca
        // tabelnya. Ulangi OCR halaman nominatif dengan mode kolom (PSM 4)
        // lalu parse dengan parser layout perjaldin.
        if (count($peserta) < 2 && $ocrAktif) {
            foreach ($teksPerHalaman as $i => $teksHalaman) {
                if (! isset($halaman[$i]) || ! preg_match('/NOMINATIF/i', $teksHalaman)) {
                    continue;
                }
                $teksKolom = $this->ocr($halaman[$i], '4');
                $baris = $this->parsePesertaPerjaldin($teksKolom);
                if (count($baris) > count($peserta)) {
                    $peserta = $baris;
                }
            }
        }

        return [
            'fields' => $fields,
            'potongan' => $potongan,
            'peserta' => $peserta,
            'komponen' => $komponen,
            'preview_jpeg' => $previewJpeg,
            'warnings' => $warnings,
            'ocr_aktif' => $ocrAktif,
        ];
    }

    /**
     * Deteksi bundel berisi lebih dari satu SPP (arsip gabungan): teks dipecah
     * pada tiap header SPP lalu tiap segmen diparse sendiri menjadi kandidat
     * komponen biaya. Mengembalikan [] bila hanya ada satu SPP.
     *
     * @return array<int, array<string, mixed>>
     */
    public function deteksiKomponen(string $teks): array
    {
        $segmen = preg_split('/(?=SURAT\s+PERMINTAAN\s+PEMBAYARAN)/i', $teks) ?: [];
        $hasil = [];

        foreach ($segmen as $bagian) {
            if (! preg_match('/Nomor\s*:?\s*SPM-BLU/i', $bagian)) {
                continue;
            }

            $abaikan = [];
            $f = $this->parseTeks($bagian, $abaikan);
            if (empty($f['nomor_spp']) || empty($f['total_bruto'])) {
                continue;
            }

            // Nama komponen dari akhiran uraian ("… - Taxi" → "Taxi").
            $nama = trim((string) ($f['deskripsi'] ?? ''));
            if ($nama !== '' && preg_match('/-\s*([^\-]{2,60})$/', $nama, $m)) {
                $nama = trim($m[1]);
            }

            $hasil[] = [
                'nomor_spp' => $f['nomor_spp'],
                'urut' => preg_match('/\/(\d{1,4})$/', $f['nomor_spp'], $m) ? (int) $m[1] : null,
                'nama' => $nama !== '' ? $nama : $f['nomor_spp'],
                'nominal' => $f['total_bruto'],
                'kode_coa' => $f['kode_coa'],
                'dipa_revision_item_id' => $f['kode_coa'] ? $this->cocokkanCoa($f['kode_coa']) : null,
                'netto' => $f['total_netto'],
                'potongan' => $f['potongan'],
            ];
        }

        return count($hasil) > 1 ? $hasil : [];
    }

    /**
     * Parse tabel nominatif penerima honor/perjaldin dari teks satu halaman.
     * Baris personel dikenali dari kombinasi: nominal berformat ribuan
     * (honor/jumlah) + deretan digit panjang (NRP/rekening/HP).
     *
     * @return array<int, array<string, mixed>>
     */
    public function parsePeserta(string $teks): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', $teks) as $line) {
            $line = trim(preg_replace('/[|\[\]()\x{2013}\x{2014}«»]+/u', ' ', $line));
            if ($line === '' || stripos($line, 'TOTAL') === 0) {
                continue;
            }

            // Nominal ribuan minimal satu — pemisah titik (500.000) ATAU koma
            // (430,000; dipakai nominatif perjaldin) — plus digit panjang
            // (rekening/NRP).
            preg_match_all('/\d{1,3}(?:[.,]\d{3})+/', $line, $mNominal);
            $nominals = array_map(fn ($v) => (float) str_replace(['.', ','], '', $v), $mNominal[0]);
            if ($nominals === [] || ! preg_match('/\d{6,}/', $line)) {
                continue;
            }

            // Nama: teks sebelum deretan digit pertama, buang nomor urut depan.
            // Pola utama (nominatif honor): nama langsung diikuti NRP/NIP.
            // Cadangan (nominatif perjaldin): NIP ada di baris lain — cukup
            // nama diikuti angka apa pun (no. SPPD/tanggal), NRP dikosongkan.
            $nrp = null;
            $jabatanDariSisa = true;
            if (preg_match('/^\s*\d{0,2}\s*([A-Za-z][A-Za-z .,\'\-]{2,60}?)\s+(\d{5,18})\b/u', $line, $mNama)) {
                $nrp = $mNama[2];
            } elseif (preg_match('/^\s*\d{0,2}\s*([A-Za-z][A-Za-z .,\'\-]{2,60}?)\s+(?=\d)/u', $line, $mNama)) {
                // Sisa baris = SPPD/tujuan/tanggal, bukan jabatan.
                $jabatanDariSisa = false;
            } else {
                continue;
            }
            $nama = trim($mNama[1]);

            // Jangan salah tangkap header/kalimat.
            if (preg_match('/NAMA|REKENING|JUMLAH|HONOR|NIP\.|Mengetahui|Bendahara/i', $nama)) {
                continue;
            }

            // Pangkat dari kosakata; sisanya (sebelum nominal pertama) = jabatan.
            $sisa = trim(substr($line, strlen($mNama[0])));
            $pangkat = null;
            foreach (self::PANGKAT_DIKENAL as $p) {
                if (preg_match('/^' . preg_quote($p, '/') . '\b/i', $sisa)) {
                    $pangkat = $p;
                    $sisa = trim(substr($sisa, strlen($p)));
                    break;
                }
            }
            $posNominal = strpos($sisa, $mNominal[0][0]);
            $jabatan = trim($posNominal !== false ? substr($sisa, 0, $posNominal) : '');
            $jabatan = $jabatanDariSisa ? (trim(preg_replace('/\s+/', ' ', $jabatan)) ?: null) : null;

            // Nominal: 3 angka = honor, pph, jumlah; 2 = honor & jumlah; 1 = honor.
            [$honor, $pph] = match (true) {
                count($nominals) >= 3 => [$nominals[0], $nominals[1]],
                count($nominals) === 2 => [$nominals[0], 0.0],
                default => [$nominals[0], 0.0],
            };

            // Deretan digit polos setelah nominal terakhir: rekening, HP (08..), NIK (16).
            $ekor = substr($line, strpos($line, end($mNominal[0])) + strlen(end($mNominal[0])));
            preg_match_all('/\d{7,20}/', $ekor, $mDigit);
            $rekening = null;
            $hp = null;
            foreach ($mDigit[0] as $d) {
                if ($hp === null && str_starts_with($d, '08') && strlen($d) >= 10 && strlen($d) <= 14) {
                    $hp = $d;
                } elseif ($rekening === null) {
                    $rekening = $d;
                }
            }

            $bank = null;
            if (preg_match('/Bank\s+([A-Za-z]{2,15})/i', $ekor, $mBank)) {
                $bank = 'Bank ' . strtoupper(substr($mBank[1], 0, 1)) . substr($mBank[1], 1);
            }

            $namaRekening = null;
            if ($bank && preg_match('/Bank\s+[A-Za-z]{2,15}\s+([A-Za-z][A-Za-z .\'\-]{2,40}?)(?=\s+\d|$)/i', $ekor, $mNr)) {
                $namaRekening = trim($mNr[1]) ?: null;
            }

            $rows[] = [
                'nama' => $nama,
                'nrp_nip' => $nrp,
                'pangkat' => $pangkat,
                'jabatan' => $jabatan,
                'nilai_honor' => $honor,
                'pph' => $pph,
                'rekening' => $rekening,
                'jenis_bank' => $bank,
                'nama_rekening' => $namaRekening,
                'no_hp' => $hp,
            ];
        }

        return $rows;
    }

    /**
     * Parse "Daftar Nominatif Pembayaran Perjalanan Dinas" (layout SILABI,
     * hasil OCR mode kolom/PSM 4): nama kerap berada SATU baris di ATAS baris
     * datanya (NIP terpecah spasi + no. SPPD + tujuan/tanggal + nominal +
     * rekening polos di ujung baris). Honor diambil dari nominal terakhir
     * (kolom JUMLAH) sehingga totalnya selaras dengan bruto tagihan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parsePesertaPerjaldin(string $teks): array
    {
        $rows = [];
        $namaTertunda = null;

        foreach (preg_split('/\r\n|\r|\n/', $teks) as $line) {
            $line = trim(preg_replace('/[|\[\]()\x{2013}\x{2014}«»\x{2018}\x{2019}]+/u', ' ', $line));
            if ($line === '') {
                continue;
            }
            if (preg_match('/JUMLAH|NOMINATIF|PEJABAT|BENDAHARA|Diperiksa|Mengetahui|NIP\.|KOMITMEN/i', $line)) {
                $namaTertunda = null;

                continue;
            }

            // Baris nama murni: huruf saja, tanpa deretan digit berarti.
            if (! preg_match('/\d{4}/', $line)
                && preg_match('/^\d{0,2}\s*([A-Za-z][A-Za-z .\'\-]{2,60})/u', $line, $m)) {
                // Buang token ekor 1–2 huruf — artefak garis tabel ("js", "a").
                $kandidat = preg_replace('/\s+[A-Za-z]{1,2}$/', '', $this->bersihkanNama($m[1]));
                if ($kandidat !== '' && str_word_count($kandidat) <= 5) {
                    $namaTertunda = $kandidat;
                }

                continue;
            }

            // Baris data: ≥1 nominal ribuan + rekening polos (10–16 digit) di
            // ujung — boleh diikuti sedikit artefak OCR.
            preg_match_all('/\d{1,3}(?:[.,]\d{3})+/', $line, $mN);
            if ($mN[0] === [] || ! preg_match('/(\d{10,16})\D{0,4}$/', $line, $mRek)) {
                continue;
            }
            $nominals = array_map(fn ($v) => (float) str_replace(['.', ','], '', $v), $mN[0]);

            // NIP 18 digit — kerap terpecah spasi "19860604 200712 1001".
            $nrp = null;
            if (preg_match('/\b(\d{8})\s+(\d{6})\s+(\d)\s*(\d{3})\b/', $line, $mNip)) {
                $nrp = $mNip[1] . $mNip[2] . $mNip[3] . $mNip[4];
            } elseif (preg_match('/\b(\d{18})\b/', $line, $mNip)) {
                $nrp = $mNip[1];
            }

            // Nama pada baris data sendiri (kadang tergabung); selain itu
            // pakai baris nama di atasnya.
            $nama = null;
            if (preg_match('/^(?:[a-z]{1,2}\s+)?\d{0,2}[.,]?\s*([A-Za-z][A-Za-z \'\-]{2,60}?)\s+(?=KP\.|\d)/u', $line, $mNm)) {
                $nama = $this->bersihkanNama($mNm[1]) ?: null;
            }
            $nama = $nama ?: $namaTertunda;
            if (! $nama) {
                continue;
            }

            // Kolom khas perjaldin pada baris yang sama: no. SPPD, tujuan,
            // tanggal berangkat + lama hari (angka tepat setelah tahun).
            $noSppd = preg_match('/\b(KP\.?\d*\/[\w\/.\-]+)/i', $line, $mS) ? $mS[1] : null;
            $tujuan = null;
            if ($noSppd && preg_match('/' . preg_quote($noSppd, '/') . '\s+\d{3,4}\s+([A-Za-z]{4,20})/u', $line, $mTu)) {
                $tujuan = $mTu[1];
            }
            $tglBerangkat = null;
            $lamaHari = null;
            if (preg_match('/(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\s*(\d{1,3})?\b/iu', $line, $mT)) {
                $tglBerangkat = $this->normalisasiTanggal($mT[1], $mT[2], $mT[3]);
                $lamaHari = isset($mT[4]) && $mT[4] !== '' ? (int) $mT[4] : null;
            }

            $rows[] = [
                'nama' => $nama,
                'nrp_nip' => $nrp,
                'pangkat' => null,
                'jabatan' => null,
                'nilai_honor' => end($nominals),
                'pph' => 0.0,
                'rekening' => $mRek[1],
                'jenis_bank' => null,
                'nama_rekening' => null,
                'no_hp' => null,
                'no_spt' => null,
                'no_sppd' => $noSppd,
                'tujuan' => $tujuan,
                'tgl_berangkat' => $tglBerangkat,
                'lama_hari' => $lamaHari,
            ];
            $namaTertunda = null;
        }

        return $rows;
    }

    /**
     * Jumlah kata bermakna (deret huruf ≥4 yang mengandung vokal) — proksi
     * kualitas hasil OCR. Halaman tegak asli ≥80; halaman miring hanya
     * menghasilkan token sampah pendek (skor ≤15).
     */
    private function kataBermakna(string $teks): int
    {
        preg_match_all('/[A-Za-z]{4,}/', $teks, $m);

        return count(array_filter($m[0], fn ($k) => preg_match('/[aiueoAIUEO]/', $k)));
    }

    /** Deteksi derajat rotasi halaman via Tesseract OSD (0 bila tegak/gagal). */
    private function deteksiRotasi(string $imagePath): int
    {
        $bin = (string) config('services.tesseract.path');
        $proc = proc_open(
            [$bin, $imagePath, 'stdout', '--tessdata-dir', (string) config('services.tesseract.tessdata'), '--psm', '0'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (! is_resource($proc)) {
            return 0;
        }
        $out = (stream_get_contents($pipes[1]) ?: '') . (stream_get_contents($pipes[2]) ?: '');
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        return preg_match('/Rotate:\s*(\d+)/', $out, $m) ? (int) $m[1] : 0;
    }

    /** Putar gambar in-place sesuai instruksi OSD ("Rotate: N" = N derajat searah jarum jam). */
    private function putarGambar(string $imagePath, int $derajat): bool
    {
        $img = @imagecreatefromjpeg($imagePath);
        if (! $img) {
            return false;
        }
        $rot = imagerotate($img, -$derajat, 0);
        imagejpeg($rot, $imagePath, 85);
        imagedestroy($img);
        imagedestroy($rot);

        return true;
    }

    // ── Parser teks OCR ──────────────────────────────────────────────

    /**
     * @param  array<int, string>  $warnings
     * @return array<string, mixed>
     */
    public function parseTeks(string $teks, array &$warnings = []): array
    {
        $f = [
            'tipe_tagihan' => 'KONTRAK_EKSTERNAL',
            'nomor_spp' => null, 'tanggal_spp' => null,
            'nomor_spm' => null, 'tanggal_spm' => null,
            'nomor_npi' => null, 'tanggal_npi' => null,
            'nomor_sp2d' => null, 'tanggal_sp2d' => null,
            'total_bruto' => null, 'total_netto' => null,
            'kode_coa' => null, 'deskripsi' => null,
            'pihak_nama' => null, 'pihak_npwp' => null, 'pihak_alamat' => null,
            'pihak_bank' => null, 'pihak_rekening' => null, 'pihak_nama_rekening' => null,
            'pihak_direktur' => null,
            'potongan' => [],
        ];

        if (trim($teks) === '') {
            return $f;
        }

        if (preg_match('/Nomor\s*:?\s*(SPM-BLU\/[A-Z0-9\/\-\.]+)/i', $teks, $m)) {
            $f['nomor_spp'] = trim($m[1]);
        } else {
            $warnings[] = 'Nomor SPP tidak terbaca dari scan.';
        }

        if (preg_match('/Tanggal\s*:?\s*(\d{1,2})[-\/ ]([A-Za-z]{3,9})[-\/ ](\d{4})/', $teks, $m)) {
            $f['tanggal_spp'] = $this->normalisasiTanggal($m[1], $m[2], $m[3]);
        }

        if (preg_match('/sejumlah\s*Rp\s*\.?\s*([\d.,]+)/i', $teks, $m)) {
            $f['total_bruto'] = $this->angka($m[1]);
        }

        if (preg_match('/TOTAL\s+PEMBAYARAN[^\d]{0,40}([\d.,]+)/i', $teks, $m)) {
            $f['total_netto'] = $this->angka($m[1]);
        }

        if (preg_match('/([A-Z]{2}\.\d{4}\.[A-Z]{2,3}\.\d{3}\.\d{3}\.[A-Z]\.\d{6}\.\d{5,6})/', $teks, $m)) {
            $f['kode_coa'] = $m[1];
        } else {
            $warnings[] = 'Kode COA tidak terbaca dari scan.';
        }

        // Potongan: baris "AKUN NOMINAL" pada blok POTONGAN. Kode akun kerap
        // ter-OCR rusak (mis. 411211 → "Tana2nt"), jadi bila header POTONGAN
        // terbaca, SEMUA baris bernominal di blok itu diambil — akun yang
        // rusak dikosongkan (diisi user) dengan warning; nominalnya tetap
        // akurat sehingga netto langsung benar.
        $sebelumTotal = $teks;
        if (($posTotal = stripos($teks, 'TOTAL PEMBAYARAN')) !== false) {
            $sebelumTotal = substr($teks, 0, $posTotal);
        }

        $blokPotongan = null;
        if (preg_match('/^[^\n]*POTONGAN[^\n]*JUMLAH[^\n]*$/mi', $sebelumTotal, $mHead, PREG_OFFSET_CAPTURE)) {
            $blokPotongan = substr($sebelumTotal, $mHead[0][1] + strlen($mHead[0][0]));
            $blokPotongan = preg_replace('/Jumlah\s+Potongan[\s\S]*$/i', '', $blokPotongan);
        }

        if ($blokPotongan !== null) {
            foreach (preg_split('/\r\n|\r|\n/', $blokPotongan) as $line) {
                $line = trim(preg_replace('/^[|\s]+/', '', $line));
                if ($line === '' || ! preg_match('/^(\S{2,12})\s+([\d.,]+,\d{2})\s*$/', $line, $m)) {
                    continue;
                }
                $akun = preg_match('/(4\d{5})/', preg_replace('/\D/', '', $m[1]) ?: $m[1], $mAkun) ? $mAkun[1] : '';
                if ($akun === '') {
                    $warnings[] = 'Kode akun potongan "' . $m[1] . '" tidak terbaca jelas — isi manual (nominal Rp ' . number_format($this->angka($m[2]), 0, ',', '.') . ' sudah terisi).';
                }
                $f['potongan'][] = ['nama' => $akun, 'nominal' => $this->angka($m[2])];
            }
        }

        // Cadangan (header POTONGAN tidak ter-OCR): pola ketat akun 41xxxx.
        if ($f['potongan'] === []
            && preg_match_all('/^\W*(41\d{4})[^\S\n]+([\d.,]+)\s*$/m', $sebelumTotal, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $f['potongan'][] = ['nama' => $row[1], 'nominal' => $this->angka($row[2])];
            }
        }

        if (preg_match('/Nama Supplier\s*:?\s*(.+?)(?:\s{2,}|Bank\/Pos|$)/m', $teks, $m)) {
            $f['pihak_nama'] = $this->bersihkanNama($m[1]);
        }
        if (preg_match('/NPWP\s*:?\s*([\d\.\-]{10,25})/', $teks, $m)) {
            $f['pihak_npwp'] = preg_replace('/\D/', '', $m[1]);
        }

        // Blok bank pada SPP: Bank/Pos, Rekening, Nama (pemilik rekening), Alamat.
        if (preg_match('/Bank\/?\s?Pos\s*:?\s*([^\n:]+?)\s*$/m', $teks, $m)) {
            $f['pihak_bank'] = trim($m[1]);
        }
        if (preg_match('/Rekening\s*:?\s*([0-9][0-9 .\-]{5,24})/', $teks, $m)) {
            $f['pihak_rekening'] = preg_replace('/\D/', '', $m[1]);
        }
        if (preg_match('/\bNama\s*:\s*([^\n]+)/', $teks, $m) && stripos($m[1], 'terlampir') === false) {
            $f['pihak_nama_rekening'] = $this->bersihkanNama($m[1]);
        }
        if (preg_match('/Alamat\s*:?\s*(.+?)(?:\s+Uraian\b|\s*$)/m', $teks, $m)) {
            $f['pihak_alamat'] = trim($m[1]);
        }

        // Nama direktur vendor — dicari pada halaman BAPP/BAST/kuitansi.
        // Pola diurutkan dari yang paling bersih ter-OCR:
        // 1) "Nama : YUARNO ARBI" diikuti "Jabatan : Direktur ..." (':' kadang terbaca '1').
        // 2) "2. AHMAD, dalam hal ini sebagai Penyedia, ..."
        // 3) Nama sendirian tepat di atas baris "Direktur" (blok tanda tangan).
        if (preg_match('/Nama\s*[:1]?\s*([A-Z][A-Za-z .\'\-]{2,40})\s*\n\s*Jabatan\s*[:1]?\s*Direktur/u', $teks, $m)) {
            $f['pihak_direktur'] = $this->bersihkanNama($m[1]);
        } elseif (preg_match('/\b([A-Z][A-Za-z .\'\-]{2,40}),\s*dalam hal ini sebagai Penyedia/u', $teks, $m)) {
            $f['pihak_direktur'] = $this->bersihkanNama($m[1]);
        } elseif (preg_match_all('/\n\s*([A-Z][A-Za-z .\'\-]{2,40})\s*\n\s*Direktur(?!at)\b/u', $teks, $mm)) {
            foreach ($mm[1] as $kandidat) {
                $kandidat = $this->bersihkanNama($kandidat);
                if ($kandidat && ! preg_match('/NIP|KANTOR|BADAN|UPBU|PRANOTO|Jabatan|Penyedia/i', $kandidat)) {
                    $f['pihak_direktur'] = $kandidat;
                    break;
                }
            }
        }

        if (preg_match('/Uraian\s*:?\s*([\s\S]{5,180}?)(?:\n[^a-z]|$)/', $teks, $m)) {
            $f['deskripsi'] = Str::limit(trim(preg_replace('/\s+/', ' ', $m[1])), 490, '');
        }

        if (preg_match('/(NPI-BLU\/[A-Z0-9\/\-\.]+)/', $teks, $m)) {
            $f['nomor_npi'] = trim($m[1]);
        }

        $uraian = strtoupper((string) ($f['deskripsi'] ?? ''));
        if (str_contains($uraian, 'HONORARIUM')) {
            $f['tipe_tagihan'] = 'HONORARIUM';
        } elseif (str_contains($uraian, 'PERJALANAN DINAS')) {
            $f['tipe_tagihan'] = 'PERJALDIN';
        }

        return $f;
    }

    /** @return array{urut: ?int, deskripsi: ?string, vendor: ?string, bruto: ?float} */
    public function parseNamaFile(string $nama): array
    {
        $nama = preg_replace('/\.pdf$/i', '', basename($nama));
        $out = ['urut' => null, 'deskripsi' => null, 'vendor' => null, 'bruto' => null];

        // "0135." atau rentang bundel gabungan "0135-0136." → ambil urut pertama.
        if (preg_match('/^(\d{3,4})(?:\s*-\s*\d{3,4})?\.\s*/', $nama, $m)) {
            $out['urut'] = (int) $m[1];
            $nama = trim(substr($nama, strlen($m[0])));
        }

        if (preg_match('/_Rp\.?\s*([\d.,]+)$/i', $nama, $m)) {
            $out['bruto'] = $this->angka($m[1]);
            $nama = trim(preg_replace('/_Rp\.?\s*[\d.,]+$/i', '', $nama));
        }

        $bagian = explode('_', $nama);
        $out['deskripsi'] = trim($bagian[0]) ?: null;
        if (count($bagian) > 1) {
            $out['vendor'] = trim($bagian[1]) ?: null;
        }

        return $out;
    }

    // ── OCR & ekstraksi gambar ───────────────────────────────────────

    public function tesseractTersedia(): bool
    {
        $path = (string) config('services.tesseract.path');

        return $path !== '' && (is_file($path) || $path === 'tesseract');
    }

    private function ocr(string $imagePath, string $psm = '6'): string
    {
        $bin = (string) config('services.tesseract.path');
        $tessdata = (string) config('services.tesseract.tessdata');
        $lang = is_file($tessdata . DIRECTORY_SEPARATOR . 'ind.traineddata') ? 'ind+eng' : 'eng';

        $cmd = [
            $bin, $imagePath, 'stdout',
            '--tessdata-dir', $tessdata,
            '-l', $lang,
            '--psm', $psm,
        ];

        $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($proc)) {
            return '';
        }

        $out = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        return $out;
    }

    /** @return array<int, string> path JPEG per halaman (temp) */
    private function ekstrakJpegHalaman(string $pdfPath): array
    {
        $raw = (string) @file_get_contents($pdfPath);
        if ($raw === '') {
            return [];
        }

        $dir = storage_path('app/historis-arsip/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $files = [];
        $pos = 0;
        $n = 0;
        while (($start = strpos($raw, "\xFF\xD8\xFF", $pos)) !== false && $n < self::MAX_HALAMAN) {
            $end = strpos($raw, "\xFF\xD9", $start + 3);
            if ($end === false) {
                break;
            }
            $jpg = substr($raw, $start, $end - $start + 2);
            if (strlen($jpg) > 50000) { // abaikan thumbnail kecil
                $n++;
                $file = $dir . '/' . Str::uuid() . '.jpg';
                file_put_contents($file, $jpg);
                $files[] = $file;
            }
            $pos = $end + 2;
        }

        return $files;
    }

    // ── Util ─────────────────────────────────────────────────────────

    /** Buang artefak OCR di sekeliling nama (©:, |, dsb.) & rapikan spasi. */
    private function bersihkanNama(string $nama): ?string
    {
        $nama = preg_replace('/^[^A-Za-z]+|[^A-Za-z.\d]+$/u', '', trim($nama));

        return trim(preg_replace('/\s+/', ' ', $nama)) ?: null;
    }

    /** Kunci pembanding nama pihak: huruf/angka kapital saja. */
    public static function kunciNama(?string $nama): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $nama));
    }

    private function angka(string $s): float
    {
        // Format berkas: 196.697.855,00 (titik ribuan, koma desimal).
        $s = str_replace('.', '', trim($s));
        $s = str_replace(',', '.', $s);

        return (float) $s;
    }

    private function normalisasiTanggal(string $hari, string $bulan, string $tahun): ?string
    {
        $peta = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'mei' => 5,
            'jun' => 6, 'jul' => 7, 'aug' => 8, 'agu' => 8, 'sep' => 9, 'oct' => 10,
            'okt' => 10, 'nov' => 11, 'dec' => 12, 'des' => 12,
        ];
        $b = $peta[strtolower(substr($bulan, 0, 3))] ?? null;

        return $b ? sprintf('%04d-%02d-%02d', (int) $tahun, $b, (int) $hari) : null;
    }

    public function cocokkanCoa(string $kode): ?int
    {
        $items = DipaBudgetOptionService::groupedOptions()
            ->flatMap(fn (array $group) => collect($group['items']));

        // Exact match dulu.
        $exact = $items->first(fn (array $item) => $item['coa_label'] === $kode);
        if ($exact) {
            return (int) $exact['id'];
        }

        // Segmen kd_item dibandingkan numerik: berkas SILABI menulis 6 digit
        // (".000006") sedangkan master hasil impor POK 5 digit (".00006") —
        // keduanya item yang sama.
        if (preg_match('/^(.*)\.(\d{4,6})$/', $kode, $m)) {
            $prefix = $m[1];
            $urut = (int) $m[2];

            $samaUrut = $items->filter(function (array $item) use ($prefix, $urut) {
                return str_starts_with($item['coa_label'], $prefix . '.')
                    && preg_match('/\.(\d{4,6})$/', $item['coa_label'], $mi)
                    && (int) $mi[1] === $urut;
            });

            if ($samaUrut->count() === 1) {
                return (int) $samaUrut->first()['id'];
            }

            // Terakhir: prefix unik tanpa memandang urutan item.
            $sePrefix = $items->filter(
                fn (array $item) => str_starts_with($item['coa_label'], $prefix . '.')
            );
            if ($sePrefix->count() === 1) {
                return (int) $sePrefix->first()['id'];
            }
        }

        return null;
    }
}
