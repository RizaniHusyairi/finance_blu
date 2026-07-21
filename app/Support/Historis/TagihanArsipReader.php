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
    /** Maksimal halaman awal bundel yang di-OCR (SPP di hal 1, NPI di hal 2-4). */
    private const MAX_HALAMAN = 4;

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
        if ($ocrAktif) {
            foreach ($halaman as $img) {
                $teks .= $this->ocr($img) . "\n";
            }
        } else {
            $warnings[] = 'OCR tidak aktif (binary Tesseract tidak ditemukan) — hanya nama file yang dibaca.';
        }

        $dariNama = $this->parseNamaFile($namaFileAsli);
        $fields = $this->parseTeks($teks, $warnings);

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

        return [
            'fields' => $fields,
            'potongan' => $potongan,
            'preview_jpeg' => $previewJpeg,
            'warnings' => $warnings,
            'ocr_aktif' => $ocrAktif,
        ];
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

        // Potongan: baris "AKUN NOMINAL" — akun penerimaan pajak selalu 41xxxx.
        // (Header "POTONGAN" kerap gagal ter-OCR, jadi baris dicari langsung
        // di seluruh teks halaman SPP.)
        $sebelumTotal = $teks;
        if (($posTotal = stripos($teks, 'TOTAL PEMBAYARAN')) !== false) {
            $sebelumTotal = substr($teks, 0, $posTotal);
        }
        if (preg_match_all('/^\W*(41\d{4})[^\S\n]+([\d.,]+)\s*$/m', $sebelumTotal, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $row) {
                $f['potongan'][] = ['nama' => $row[1], 'nominal' => $this->angka($row[2])];
            }
        }

        if (preg_match('/Nama Supplier\s*:?\s*(.+?)(?:\s{2,}|Bank\/Pos|$)/m', $teks, $m)) {
            $f['pihak_nama'] = trim($m[1]);
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
            $f['pihak_nama_rekening'] = trim($m[1]);
        }
        if (preg_match('/Alamat\s*:?\s*(.+?)(?:\s+Uraian\b|\s*$)/m', $teks, $m)) {
            $f['pihak_alamat'] = trim($m[1]);
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

        if (preg_match('/^(\d{3,4})\.\s*/', $nama, $m)) {
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

    private function ocr(string $imagePath): string
    {
        $bin = (string) config('services.tesseract.path');
        $tessdata = (string) config('services.tesseract.tessdata');
        $lang = is_file($tessdata . DIRECTORY_SEPARATOR . 'ind.traineddata') ? 'ind+eng' : 'eng';

        $cmd = [
            $bin, $imagePath, 'stdout',
            '--tessdata-dir', $tessdata,
            '-l', $lang,
            '--psm', '6',
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
