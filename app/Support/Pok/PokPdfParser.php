<?php

namespace App\Support\Pok;

use Smalot\PdfParser\Parser;

/**
 * Parser POK (Rincian Kertas Kerja Satker) hasil cetak aplikasi anggaran.
 *
 * Membaca hierarki Program → Kegiatan → KRO → RO → Komponen → Subkomponen →
 * Akun → Detil dari teks PDF, dan mengembalikan baris detil siap impor:
 * satu baris = satu kode MAK lengkap + volume/satuan/harga satuan/jumlah +
 * sumber dana. Baris yang tidak dikenali dilaporkan sebagai warning, tidak
 * menggagalkan keseluruhan parse.
 */
class PokPdfParser
{
    /**
     * @return array{tahun: ?int, alokasi: ?float, satker: ?string, rows: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function parse(string $filePath): array
    {
        $pdf = (new Parser())->parseFile($filePath);

        $tahun = null;
        $alokasi = null;
        $satker = null;
        $kodeKemen = null;
        $kodeUnit = null;
        $kodeSatker = null;
        $tanggalTtd = null;
        $rows = [];
        $warnings = [];

        $ctx = [
            'program' => null, 'giat' => null, 'kro' => null, 'ro' => null,
            'komponen' => null, 'subkomp' => null, 'akun' => null, 'sd' => null,
        ];
        $itemCounters = [];
        $pendingDetail = null;
        $pendingNumbers = null; // baris "JUMLAHHARGA" yang volume+satuannya jatuh ke baris berikut

        foreach ($pdf->getPages() as $page) {
            $lines = preg_split('/\r\n|\r|\n/', $page->getText());

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if ($tahun === null && preg_match('/RINCIAN KERTAS KERJA SATKER T\.A\.?\s*(\d{4})/', $line, $m)) {
                    $tahun = (int) $m[1];
                    continue;
                }
                if ($alokasi === null && preg_match('/^([\d,]+)Rp\.?$/', $line, $m)) {
                    $alokasi = (float) str_replace(',', '', $m[1]);
                    continue;
                }
                if ($satker === null && preg_match('/^KANTOR .+/', $line)) {
                    $satker = $line;
                    continue;
                }
                if ($kodeKemen === null && preg_match('/^\((\d{3})\)$/', $line, $m)) {
                    $kodeKemen = $m[1];
                    continue;
                }
                if ($kodeUnit === null && preg_match('/^\((\d{2})\)$/', $line, $m)) {
                    $kodeUnit = $m[1];
                    continue;
                }
                if ($kodeSatker === null && preg_match('/^\((\d{6})\)$/', $line, $m)) {
                    $kodeSatker = $m[1];
                    continue;
                }
                // Tanggal tanda tangan: "Samarinda, 15 Desember 2025".
                if (preg_match('/^[A-Za-z .]+,\s*(\d{1,2})\s+(Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})$/u', $line, $m)) {
                    $bulan = array_search($m[2], [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true);
                    $tanggalTtd = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $bulan, (int) $m[1]);
                    continue;
                }

                // Lanjutan nama detil yang terpenggal: kumpulkan baris nama
                // sampai bertemu terminator "-" (mandiri / di ujung baris).
                if ($pendingDetail !== null) {
                    $bersih = preg_replace('/\tSBM$/', '', $line);

                    if ($bersih === '-' || preg_match('/^(?:SBM\s*)?-$/', $bersih)) {
                        $rows[] = $this->finalizeDetail($pendingDetail, $ctx, $itemCounters);
                        $pendingDetail = null;
                        continue;
                    }

                    if (! $this->looksLikeStructure($bersih)
                        && ! preg_match('/^[\d,\.]+ \S/', $bersih)
                        && $pendingDetail['baris_nama'] < 4) {
                        $selesai = (bool) preg_match('/ -\s*(?:SBM)?$/', $bersih);
                        $potongan = trim(preg_replace('/\s*-\s*(?:SBM)?$/', '', $bersih));
                        if ($potongan !== '') {
                            $pendingDetail['nama'] = trim($pendingDetail['nama'] . ' ' . $potongan);
                        }
                        $pendingDetail['baris_nama']++;

                        if ($selesai) {
                            $rows[] = $this->finalizeDetail($pendingDetail, $ctx, $itemCounters);
                            $pendingDetail = null;
                        }
                        continue;
                    }

                    if ($pendingDetail['nama'] === '') {
                        $warnings[] = 'Baris detil tanpa nama terdeteksi di sekitar akun ' . ($ctx['akun'] ?? '?') . ' — periksa hasil impor.';
                        $pendingDetail['nama'] = '(tanpa nama — periksa POK)';
                    }
                    $rows[] = $this->finalizeDetail($pendingDetail, $ctx, $itemCounters);
                    $pendingDetail = null;
                    // jatuh ke pengenalan pola di bawah
                }

                // Program: "...022.05.GA<TAB>35,891,087,000"
                if (preg_match('/\d{3}\.\d{2}\.([A-Z]{2})\t/', $line, $m)) {
                    $ctx['program'] = $m[1];
                    continue;
                }
                // RO: "...4646.CBE.002<TAB>..." (diuji sebelum KRO karena lebih spesifik)
                if (preg_match('/(\d{4})\.([A-Z]{2,3})\.(\d{3})\t/', $line, $m)) {
                    $ctx['giat'] = $m[1];
                    $ctx['kro'] = $m[2];
                    $ctx['ro'] = $m[3];
                    continue;
                }
                // KRO: "...4646.CBE<TAB>..."
                if (preg_match('/(\d{4})\.([A-Z]{2,3})\t/', $line, $m)) {
                    $ctx['giat'] = $m[1];
                    $ctx['kro'] = $m[2];
                    continue;
                }
                // Kegiatan: "Nama Kegiatan4646<TAB>..."
                if (preg_match('/(\d{4})\t[\d,]+$/', $line, $m)) {
                    $ctx['giat'] = $m[1];
                    continue;
                }
                // Komponen: "Nama Komponen<TAB>U051<TAB>..." atau (nama terpenggal
                // di baris sebelumnya) "U052<TAB>..." — U = utama, P = penunjang.
                if (preg_match('/(?:\t|^)[UP](\d{3})\t/', $line, $m)) {
                    $ctx['komponen'] = $m[1];
                    continue;
                }
                // Subkomponen: "A<TAB>Nama Subkomponen<TAB>..."
                if (preg_match('/^([A-Z])\t/', $line, $m)) {
                    $ctx['subkomp'] = $m[1];
                    continue;
                }
                // Sumber dana berdiri sendiri di atas blok akun (SABLU = saldo awal BLU).
                if (preg_match('/^(RM|BLU|SABLU|PNBP|PLN|SBSN|HLN)$/', $line, $m)) {
                    $ctx['sd'] = in_array($m[1], ['BLU', 'SABLU'], true) ? 'BLU' : 'RM';
                    continue;
                }
                // Akun: "525112 Belanja Barang<TAB>..." atau kode berdiri sendiri
                // "523123" saat nama akunnya terpenggal ke baris berikut.
                if (preg_match('/^(\d{6})\s+(.+?)\t/', $line, $m) || preg_match('/^(\d{6})$/', $line, $m)) {
                    $ctx['akun'] = $m[1];
                    continue;
                }

                // Baris "VOLUME SATUAN" lanjutan dari baris angka-saja sebelumnya.
                if ($pendingNumbers !== null) {
                    if (preg_match('/^(\d+\.\d+) (\S+)$/', $line, $m)) {
                        $detail = $this->parseDetailLine($pendingNumbers . $m[1], $m[2]);
                        $pendingNumbers = null;
                        if ($detail !== null) {
                            $pendingDetail = $detail; // nama menyusul di baris-baris berikutnya
                            continue;
                        }
                    }
                    $pendingNumbers = null;
                    // jatuh ke pengenalan pola di bawah
                }

                // Baris angka-saja "JUMLAHHARGA" — volume+satuan menyusul di baris berikut.
                if (preg_match('/^[\d,]{8,}$/', $line)) {
                    $pendingNumbers = $line;
                    continue;
                }

                // Detil: "JUMLAHHARGAVOLUME SATUANNama Detil -" (kolom menyatu dari kanan).
                if (preg_match('/^([\d,\.]+) (\S.*?)(?:\s+-\s*(?:SBM)?)?$/u', $line, $m)) {
                    $detail = $this->parseDetailLine($m[1], $m[2]);

                    if ($detail !== null) {
                        if (preg_match('/ -\s*(?:SBM)?$/', $line)) {
                            $rows[] = $this->finalizeDetail($detail, $ctx, $itemCounters);
                        } else {
                            $pendingDetail = $detail; // nama berlanjut ke baris berikutnya
                        }
                        continue;
                    }
                }
            }
        }

        if ($pendingDetail !== null) {
            $rows[] = $this->finalizeDetail($pendingDetail, $ctx, $itemCounters);
        }

        foreach ($rows as $row) {
            if ($row['selisih'] != 0.0) {
                $warnings[] = sprintf(
                    'Volume × harga tidak sama dengan jumlah pada "%s" (%s × %s ≠ %s).',
                    $row['nama'],
                    $row['volume'],
                    number_format($row['harga_satuan'], 0, ',', '.'),
                    number_format($row['jumlah'], 0, ',', '.')
                );
            }
        }

        return [
            'tahun' => $tahun,
            'alokasi' => $alokasi,
            'satker' => $satker,
            'kode_kemen' => $kodeKemen,
            'kode_unit' => $kodeUnit,
            'kode_satker' => $kodeSatker,
            'tanggal_ttd' => $tanggalTtd,
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    /** Satuan yang dikenal pada POK, dicoba dari yang terpanjang. */
    private const SATUAN_DIKENAL = [
        'Pg/TH', 'PAKET', 'PAket', 'Paket', 'paket', 'TAHUN', 'BULAN', 'Liter', 'layanan',
        'TEKN', 'STEL', 'Rute', 'UNIT', 'Unit', 'unit', 'THN', 'BLN', 'Bln', 'bln',
        'PKT', 'Pkt', 'pkt', 'PEG', 'Peg', 'OH', 'OT', 'OB', 'ob', 'M2', 'M3', 'm2', 'M',
    ];

    /**
     * Urai baris detil yang kolom-kolomnya menyatu: blob angka berisi
     * JUMLAH·HARGA·VOLUME tanpa pemisah, lalu SATUAN menempel pada nama.
     * Titik potong dicari dari semua kandidat yang valid sebagai angka
     * ribuan, dan diputuskan oleh aturan POK jumlah = volume × harga.
     */
    private function parseDetailLine(string $blob, string $fused): ?array
    {
        $ribuan = '/^\d{1,3}(?:,\d{3})*$/';
        $terpilih = null;
        $cadangan = null;

        // Volume selalu dicetak berdesimal (mis. 6.0, 12.0, 30661.84).
        for ($i = strlen($blob) - 1; $i > 0; $i--) {
            $volRaw = substr($blob, $i);
            if (! preg_match('/^\d+\.\d+$/', $volRaw)) {
                continue;
            }

            $sisa = substr($blob, 0, $i);
            for ($j = 1; $j < strlen($sisa); $j++) {
                $jumlahRaw = substr($sisa, 0, $j);
                $hargaRaw = substr($sisa, $j);
                if (! preg_match($ribuan, $jumlahRaw) || ! preg_match($ribuan, $hargaRaw)) {
                    continue;
                }

                $kandidat = [
                    'volume' => (float) $volRaw,
                    'jumlah' => (float) str_replace(',', '', $jumlahRaw),
                    'harga' => (float) str_replace(',', '', $hargaRaw),
                ];
                $cadangan ??= $kandidat;

                if (abs($kandidat['jumlah'] - $kandidat['volume'] * $kandidat['harga']) < 1) {
                    $terpilih = $kandidat;
                    break 2;
                }
            }
        }

        $terpilih ??= $cadangan;
        if ($terpilih === null) {
            return null;
        }

        [$satuan, $nama] = $this->splitSatuanNama($fused);
        if ($nama === '' && $satuan === '') {
            return null;
        }

        return [
            'baris_nama' => 0,
            'nama' => $nama,
            'volume' => $terpilih['volume'],
            'satuan' => $satuan,
            'harga_satuan' => $terpilih['harga'],
            'jumlah' => $terpilih['jumlah'],
            'selisih' => round($terpilih['jumlah'] - $terpilih['volume'] * $terpilih['harga'], 2),
        ];
    }

    /** Pisahkan "RuteSubsidi Angkutan…" menjadi satuan + nama detil. */
    private function splitSatuanNama(string $fused): array
    {
        foreach (self::SATUAN_DIKENAL as $satuan) {
            if ($fused === $satuan) {
                return [$satuan, '']; // nama detil berlanjut di baris berikutnya
            }
            if (str_starts_with($fused, $satuan)) {
                $nama = trim(substr($fused, strlen($satuan)));
                if ($nama !== '') {
                    return [$satuan, $nama];
                }
            }
        }

        // Cadangan: batas huruf kecil/angka → huruf besar (blnLatihan, M3Drainase),
        // atau huruf besar terakhir sebelum huruf kecil pada deret kapital (PKTPenyelenggaraan).
        if (preg_match('/^(.+?[a-z0-9])([A-Z].*)$/u', $fused, $m) && ! str_contains($m[1], ' ')) {
            return [$m[1], trim($m[2])];
        }
        if (preg_match('/^([A-Z\/0-9]+)([A-Z][a-z].*)$/u', $fused, $m)) {
            return [$m[1], trim($m[2])];
        }

        return ['', trim($fused)];
    }

    private function finalizeDetail(array $detail, array $ctx, array &$itemCounters): array
    {
        $prefix = implode('.', array_filter([
            $ctx['program'], $ctx['giat'], $ctx['kro'], $ctx['ro'],
            $ctx['komponen'], $ctx['subkomp'], $ctx['akun'],
        ], fn ($v) => $v !== null && $v !== ''));

        $itemCounters[$prefix] = ($itemCounters[$prefix] ?? 0) + 1;
        $kdItem = str_pad((string) $itemCounters[$prefix], 5, '0', STR_PAD_LEFT);

        return array_merge($detail, [
            'kd_program' => $ctx['program'],
            'kd_giat' => $ctx['giat'],
            'kd_output' => $ctx['kro'],
            'kd_suboutput' => $ctx['ro'],
            'kd_komponen' => $ctx['komponen'],
            'kd_subkomponen' => $ctx['subkomp'],
            'kd_akun' => $ctx['akun'],
            'kd_item' => $kdItem,
            'kode_mak_lengkap' => $prefix . '.' . $kdItem,
            'sumber_dana' => $ctx['sd'] ?? (str_starts_with((string) $ctx['akun'], '525') ? 'BLU' : 'RM'),
        ]);
    }

    /** Baris lanjutan nama tidak boleh berupa pola struktur lain. */
    private function looksLikeStructure(string $line): bool
    {
        return preg_match('/^[\d,]{8,}$/', $line)
            || preg_match('/^\d{6}\s/', $line)
            || preg_match('/^\d{6}$/', $line)
            || preg_match('/^[A-Z]\t/', $line)
            || preg_match('/^(RM|BLU|PNBP|PLN|SBSN|HLN)$/', $line)
            || preg_match('/\t[UP]\d{3}\t/', $line)
            || preg_match('/\d{4}\.[A-Z]{2,3}/', $line)
            || str_starts_with($line, '(KPPN');
    }
}
