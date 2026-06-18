<?php

namespace App\Services\Pembukuan;

use App\Enums\KodeBuku;
use App\Models\PembukuanSetup;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Pembuatan dokumen resmi BKU/buku pembantu & realisasi (PDF + Excel) sesuai
 * format dua Excel acuan: kop satker, periode, Saldo Awal/Akhir, baris "Saldo
 * Awal Bulan Berjalan", kolom Penerimaan | Pengeluaran | Saldo.
 *
 * Data dari [[BukuPembantuService]] & [[RealisasiPenerimaanService]]. Metode
 * render*Html / build*Spreadsheet sengaja dipisah agar mudah diuji tanpa stream.
 */
class DokumenPembukuanService
{
    public function __construct(
        private readonly BukuPembantuService $bukuService,
        private readonly RealisasiPenerimaanService $realisasiService,
    ) {
    }

    // ───────────────────────── BUKU (BKU / buku pembantu) ─────────────────────────

    public function bukuViewData(int $kodeBuku, string $peran, array $filters = []): array
    {
        return [
            'setup' => PembukuanSetup::current(),
            'buku' => $this->bukuService->buildBuku($kodeBuku, $peran, $filters),
            'periodeLabel' => $this->periodeLabel($filters),
            'filters' => $filters,
        ];
    }

    public function renderBukuHtml(int $kodeBuku, string $peran, array $filters = []): string
    {
        return view('pembukuan.dokumen.buku-pdf', $this->bukuViewData($kodeBuku, $peran, $filters))->render();
    }

    public function streamBukuPdf(int $kodeBuku, string $peran, array $filters = [])
    {
        $data = $this->bukuViewData($kodeBuku, $peran, $filters);
        $pdf = Pdf::loadView('pembukuan.dokumen.buku-pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->stream($this->namaFile($kodeBuku, $peran, 'pdf'));
    }

    public function buildBukuSpreadsheet(int $kodeBuku, string $peran, array $filters = []): Spreadsheet
    {
        $data = $this->bukuViewData($kodeBuku, $peran, $filters);
        $buku = $data['buku'];
        $setup = $data['setup'];
        $isPenerimaan = $peran === 'PENERIMAAN';

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle(mb_substr($buku['nama_buku'], 0, 28));

        $sheet->setCellValue('A1', $setup?->nama_satker ?: 'KANTOR BLU');
        $sheet->setCellValue('A2', 'BUKU KAS UMUM ' . ($isPenerimaan ? 'BENDAHARA PENERIMAAN' : 'BENDAHARA PENGELUARAN')
            . ($buku['kode_buku'] != 1 ? ' — ' . $buku['nama_buku'] : ''));
        $sheet->setCellValue('A3', 'PERIODE: ' . $data['periodeLabel']);
        $sheet->setCellValue('A4', 'Saldo Awal: Rp ' . number_format($buku['saldo_awal'], 0, ',', '.')
            . '   |   Saldo Akhir: Rp ' . number_format($buku['saldo_akhir'], 0, ',', '.'));
        foreach (['A1', 'A2'] as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true)->setSize($c === 'A1' ? 13 : 11);
        }
        foreach (range(1, 4) as $r) {
            $sheet->mergeCells("A{$r}:G{$r}");
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $hr = 6;
        $headers = ['No', 'Tanggal', $isPenerimaan ? 'Kode Akun & Jenis Pelayanan' : 'Kode Transaksi',
            'Uraian Transaksi', 'Penerimaan', 'Pengeluaran', 'Saldo'];
        $sheet->fromArray($headers, null, "A{$hr}");
        $sheet->getStyle("A{$hr}:G{$hr}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$hr}:G{$hr}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle("A{$hr}:G{$hr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = $hr + 1;
        $sheet->setCellValue("D{$row}", 'SALDO AWAL BULAN BERJALAN');
        $sheet->setCellValue("E{$row}", (float) $buku['saldo_awal']);
        $sheet->setCellValue("F{$row}", 0);
        $sheet->setCellValue("G{$row}", (float) $buku['saldo_awal']);
        $row++;

        $no = 1;
        foreach ($buku['entries'] as $e) {
            $masuk = $e->arus_kas === 'DEBIT_MASUK';
            $klas = $isPenerimaan
                ? trim(($e->akunPendapatan?->kode_gabungan ?? '') . ' ' . ($e->akunPendapatan?->uraian_jenis ?? ''))
                : ($e->kode_transaksi ?? '');
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", optional($e->tanggal_transaksi)->format('d/m/Y'));
            $sheet->setCellValue("C{$row}", $klas ?: '-');
            $sheet->setCellValue("D{$row}", $e->uraian);
            $sheet->setCellValue("E{$row}", $masuk ? (float) $e->nominal : null);
            $sheet->setCellValue("F{$row}", $masuk ? null : (float) $e->nominal);
            $sheet->setCellValue("G{$row}", (float) ($e->saldo_berjalan ?? 0));
            $row++;
        }

        $sheet->setCellValue("D{$row}", 'TOTAL');
        $sheet->setCellValue("E{$row}", (float) $buku['total_terima']);
        $sheet->setCellValue("F{$row}", (float) $buku['total_keluar']);
        $sheet->setCellValue("G{$row}", (float) $buku['saldo_akhir']);
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);

        $sheet->getStyle("E{$hr}:G{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$hr}:G{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (['A' => 5, 'B' => 12, 'C' => 30, 'D' => 50, 'E' => 16, 'F' => 16, 'G' => 18] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        return $ss;
    }

    public function streamBukuExcel(int $kodeBuku, string $peran, array $filters = [])
    {
        return $this->download($this->buildBukuSpreadsheet($kodeBuku, $peran, $filters), $this->namaFile($kodeBuku, $peran, 'xlsx'));
    }

    // ───────────────────────────── REALISASI ─────────────────────────────

    public function realisasiViewData(int $tahun, array $filters = []): array
    {
        return [
            'setup' => PembukuanSetup::current(),
            'realisasi' => $this->realisasiService->build($tahun, $filters),
        ];
    }

    public function renderRealisasiHtml(int $tahun, array $filters = []): string
    {
        return view('pembukuan.dokumen.realisasi-pdf', $this->realisasiViewData($tahun, $filters))->render();
    }

    public function streamRealisasiPdf(int $tahun, array $filters = [])
    {
        $pdf = Pdf::loadView('pembukuan.dokumen.realisasi-pdf', $this->realisasiViewData($tahun, $filters))
            ->setPaper('a4', 'landscape');

        return $pdf->stream("Realisasi_Penerimaan_{$tahun}.pdf");
    }

    private function periodeLabel(array $filters): string
    {
        $s = $filters['start_date'] ?? null;
        $e = $filters['end_date'] ?? null;
        if (! $s && ! $e) {
            return 'SELURUH PERIODE';
        }
        $fmt = fn ($d) => $d ? Carbon::parse($d)->translatedFormat('d F Y') : '…';

        return strtoupper($fmt($s) . ' - ' . $fmt($e));
    }

    private function namaFile(int $kodeBuku, string $peran, string $ext): string
    {
        $label = str_replace(' ', '_', KodeBuku::tryFrom($kodeBuku)?->label() ?? 'Buku');

        return "{$label}_{$peran}_" . now()->format('Ymd_His') . ".{$ext}";
    }

    private function download(Spreadsheet $ss, string $filename)
    {
        $writer = new Xlsx($ss);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
