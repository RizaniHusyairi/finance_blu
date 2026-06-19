<?php

namespace App\Services\Pembukuan;

use App\Models\DetailMutasiBank;
use App\Models\ImportMutasiBank;
use App\Models\RekeningBank;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

/**
 * Importer rekening koran format CMS BTN (Cash Management System, .xls).
 *
 * Struktur file: blok metadata (Account/Period/Saldo) di baris atas, baris
 * header kolom ("Posting Date / Description / Debit / Credit / Balance / Ref No."),
 * lalu baris transaksi (dengan baris kosong selang-seling yang dilewati).
 *
 * Setiap baris transaksi → satu `detail_mutasi_bank`:
 *   - Credit > 0 → arah MASUK (penerimaan), nilai di kolom kredit
 *   - Debit  > 0 → arah KELUAR, nilai di kolom debit
 *
 * Idempoten: baris dengan nomor referensi yang sudah ada untuk rekening tsb
 * dilewati, sehingga impor ulang file yang sama tidak menggandakan data.
 *
 * Hilir: PostingPenerimaanService (klasifikasi & posting ke BKU Penerimaan) dan
 * PiutangRekonsiliasiService (penyandingan ke piutang/BKU).
 */
class CmsKoranImportService
{
    /**
     * @return array{import_id:int, rekening_id:int, rekening_label:string, account:?string,
     *               period:?string, total:int, masuk:int, keluar:int, skipped:int}
     */
    public function import(UploadedFile $file, ?int $rekeningId, int $userId): array
    {
        $sheet = $this->loadSheet($file->getRealPath());

        [$accountNo, $period] = $this->readMeta($sheet);
        $rekening = $this->resolveRekening($rekeningId, $accountNo);

        [$headerRow, $cols] = $this->locateColumns($sheet);
        [$year, $periodMonth] = $this->parsePeriod($period);

        // Refs yang sudah tercatat untuk rekening ini → cegah dobel saat impor ulang.
        $seenRefs = DetailMutasiBank::query()
            ->whereHas('importMutasiBank', fn ($q) => $q->where('rekening_bank_id', $rekening->id))
            ->whereNotNull('nomor_referensi_bank')
            ->pluck('nomor_referensi_bank')
            ->flip();

        return DB::transaction(function () use ($file, $sheet, $rekening, $userId, $headerRow, $cols, $year, $periodMonth, $accountNo, $period, $seenRefs) {
            $stored = $file->store('rekening-koran', 'public');

            $import = ImportMutasiBank::create([
                'rekening_bank_id' => $rekening->id,
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $stored,
                'uploaded_by' => $userId,
                'uploaded_at' => now(),
                'status_import' => 'PARSED',
            ]);

            $total = $masuk = $keluar = $duplikat = $tanpaNilai = 0;
            $minDate = null;
            $maxDate = null;
            $highestRow = $sheet->getHighestDataRow();

            for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                $debit = $this->num($sheet->getCell($cols['debit'] . $r)->getValue());
                $kredit = $this->num($sheet->getCell($cols['credit'] . $r)->getValue());
                $dateRaw = trim((string) $sheet->getCell($cols['date'] . $r)->getValue());

                // Baris kosong (penyela) — lewati diam-diam.
                if ($dateRaw === '' && $debit == 0.0 && $kredit == 0.0) {
                    continue;
                }

                $arah = $kredit > 0 ? 'MASUK' : ($debit > 0 ? 'KELUAR' : null);
                $tanggal = $this->parseDate($dateRaw, $year);

                if ($arah === null || $tanggal === null) {
                    $tanpaNilai++; // baris tanpa nilai/tanggal valid
                    continue;
                }

                $ref = trim((string) $sheet->getCell($cols['ref'] . $r)->getValue());
                if ($ref !== '' && $seenRefs->has($ref)) {
                    $duplikat++; // sudah pernah diimpor (nomor referensi bank sama)
                    continue;
                }

                DetailMutasiBank::create([
                    'import_mutasi_bank_id' => $import->id,
                    'tanggal_transaksi' => $tanggal,
                    'deskripsi' => trim((string) $sheet->getCell($cols['desc'] . $r)->getValue()) ?: null,
                    'nomor_referensi_bank' => $ref ?: null,
                    'debit' => $debit,
                    'kredit' => $kredit,
                    'saldo' => $this->num($sheet->getCell($cols['balance'] . $r)->getValue()) ?: null,
                    'arah_mutasi' => $arah,
                    'status_rekonsiliasi' => 'BELUM',
                ]);

                if ($ref !== '') {
                    $seenRefs->put($ref, true);
                }

                $total++;
                $arah === 'MASUK' ? $masuk++ : $keluar++;
                $minDate = ($minDate === null || $tanggal < $minDate) ? $tanggal : $minDate;
                $maxDate = ($maxDate === null || $tanggal > $maxDate) ? $tanggal : $maxDate;
            }

            $meta = [
                'rekening_id' => $rekening->id,
                'rekening_label' => $rekening->nama_bank . ' - ' . $rekening->nomor_rekening,
                'account' => $accountNo,
                'period' => $period,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'duplikat' => $duplikat,
                'tanpa_nilai' => $tanpaNilai,
                'skipped' => $duplikat + $tanpaNilai,
            ];

            // Tidak ada baris baru (mis. impor ulang berkas yang sama) → buang
            // catatan impor kosong + berkasnya agar tidak menumpuk.
            if ($total === 0) {
                $import->delete();
                Storage::disk('public')->delete($stored);

                return array_merge($meta, ['import_id' => null, 'total' => 0]);
            }

            $import->update(['periode_awal' => $minDate, 'periode_akhir' => $maxDate]);

            return array_merge($meta, ['import_id' => $import->id, 'total' => $total]);
        });
    }

    private function loadSheet(string $path): Worksheet
    {
        $type = IOFactory::identify($path);
        $reader = IOFactory::createReader($type);
        $reader->setReadDataOnly(true);

        return $reader->load($path)->getActiveSheet();
    }

    /**
     * Baca nomor rekening & periode dari blok metadata (label di satu kolom,
     * nilai di kolom setelahnya pada baris yang sama).
     *
     * @return array{0: ?string, 1: ?string} [nomor_rekening, periode]
     */
    private function readMeta(Worksheet $sheet): array
    {
        $account = null;
        $period = null;
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($r = 1; $r <= 24; $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                $label = strtolower(trim((string) $sheet->getCell([$c, $r])->getValue()));
                if ($label !== 'account' && $label !== 'period') {
                    continue;
                }
                // Nilai = sel non-kosong pertama di kanan label.
                for ($cc = $c + 1; $cc <= $maxCol; $cc++) {
                    $val = trim((string) $sheet->getCell([$cc, $r])->getValue());
                    if ($val === '' || $val === ':') {
                        continue;
                    }
                    if ($label === 'account') {
                        // "0002001302887451 - RPL 046 BLU ..." → ambil token nomor rekening.
                        $account = trim(explode(' - ', $val)[0]);
                    } else {
                        $period = $val; // "06-2026"
                    }
                    break;
                }
            }
        }

        return [$account, $period];
    }

    private function resolveRekening(?int $rekeningId, ?string $accountNo): RekeningBank
    {
        $rekening = $rekeningId ? RekeningBank::find($rekeningId) : null;

        if (! $rekening && $accountNo) {
            $rekening = RekeningBank::where('nomor_rekening', $accountNo)->first();
        }

        if (! $rekening) {
            $rekening = RekeningBank::query()
                ->where('status_aktif', true)
                ->where('jenis_rekening', \App\Enums\JenisRekening::PENERIMAAN->value)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        if (! $rekening) {
            throw new RuntimeException(
                'Rekening tujuan tidak ditemukan. Pilih rekening, atau samakan nomor rekening sistem '
                . 'dengan nomor pada file CMS' . ($accountNo ? " ({$accountNo})" : '') . '.'
            );
        }

        return $rekening;
    }

    /**
     * Cari baris header + petakan huruf kolom dari labelnya (toleran terhadap
     * pergeseran kolom). Fallback ke tata letak baku BTN bila label tak lengkap.
     *
     * @return array{0:int, 1:array<string,string>}
     */
    private function locateColumns(Worksheet $sheet): array
    {
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $headerRow = null;
        $cols = [];

        for ($r = 1; $r <= 40; $r++) {
            $found = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $letter = Coordinate::stringFromColumnIndex($c);
                $v = strtolower(trim((string) $sheet->getCell($letter . $r)->getValue()));
                match ($v) {
                    'description' => $found['desc'] = $letter,
                    'debit' => $found['debit'] = $letter,
                    'credit' => $found['credit'] = $letter,
                    'balance' => $found['balance'] = $letter,
                    'ref no.', 'ref no' => $found['ref'] = $letter,
                    'eff date' => $found['effdate'] = $letter,
                    'posting date' => $found['postdate'] = $letter,
                    default => null,
                };
            }

            if (isset($found['desc'], $found['debit'], $found['credit'])) {
                $headerRow = $r;
                $cols = $found;
                break;
            }
        }

        if ($headerRow === null) {
            throw new RuntimeException('Header kolom (Description/Debit/Credit) tidak ditemukan — format file bukan CMS BTN yang dikenali.');
        }

        return [$headerRow, [
            'date' => $cols['effdate'] ?? $cols['postdate'] ?? 'H',
            'desc' => $cols['desc'] ?? 'M',
            'debit' => $cols['debit'] ?? 'N',
            'credit' => $cols['credit'] ?? 'O',
            'balance' => $cols['balance'] ?? 'P',
            'ref' => $cols['ref'] ?? 'R',
        ]];
    }

    /**
     * "06-2026" → [tahun, bulan]. Default tahun = tahun berjalan bila tak terbaca.
     *
     * @return array{0:int, 1:?int}
     */
    private function parsePeriod(?string $period): array
    {
        if ($period && preg_match('/(\d{1,2})\D+(\d{4})/', $period, $m)) {
            return [(int) $m[2], (int) $m[1]];
        }
        if ($period && preg_match('/(\d{4})\D+(\d{1,2})/', $period, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [(int) date('Y'), null];
    }

    /** "01/06" atau "01/06/2026" → "Y-m-d" (tahun dari periode bila tak ada). */
    private function parseDate(string $raw, int $year): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $parts = preg_split('#[/\-.]#', $raw);
        if (count($parts) < 2) {
            return null;
        }

        $d = (int) $parts[0];
        $mo = (int) $parts[1];
        $y = (isset($parts[2]) && (int) $parts[2] > 1900) ? (int) $parts[2] : $year;

        if ($d < 1 || $d > 31 || $mo < 1 || $mo > 12) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    /** "386,804.00" / "0.00" / null → float. */
    private function num(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }

        $s = str_replace([',', ' '], '', (string) $v);

        return is_numeric($s) ? (float) $s : 0.0;
    }
}
