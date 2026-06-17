<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\PnbpUmumItem;
use App\Models\PnbpUmumRealisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Manajemen / Monitoring PNBP untuk Bendahara Penerimaan.
 *
 * Satu halaman berisi:
 *  - Rekap & grafik realisasi per kelompok: AERO, NON-AERO, dan PNBP Umum.
 *  - Daftar tiap layanan beserta data keuangannya (nilai tagihan, dibayar, sisa).
 *
 * Sumber data:
 *  - AERO / NON-AERO  : tagihan_jasa_details -> layanan_jasas.kelompok_pnbp.
 *  - PNBP Umum        : pnbp_umum_items + pnbp_umum_realisasis (di-input Bendahara
 *                       Penerimaan; bukan dari modul jasa).
 */
class ManajemenPnbpController extends Controller
{
    /** Nama bulan singkat untuk label grafik. */
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public function index(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);

        return view('manajemen_pnbp.index', array_merge($this->aggregate($tahun), [
            'tahun' => $tahun,
            'tahunOptions' => $this->tahunOptions(),
            'bulanLabels' => self::BULAN,
        ]));
    }

    /** Daftar tahun yang tersedia untuk filter. */
    private function tahunOptions()
    {
        $tahunTagihan = DB::table('tagihan_jasas')->whereNull('deleted_at')
            ->selectRaw('DISTINCT YEAR(tanggal_tagihan) as th')->pluck('th');
        $tahunUmum = PnbpUmumRealisasi::distinct()->pluck('tahun');

        return $tahunTagihan->merge($tahunUmum)->filter()
            ->push(now()->year)->unique()->sortDesc()->values();
    }

    /** Agregasi seluruh data PNBP (AERO/NON-AERO dari jasa + PNBP Umum) untuk satu tahun. */
    private function aggregate(int $tahun): array
    {
        // ── Realisasi (penerimaan) AERO / NON-AERO per bulan ──
        // Realisasi = subtotal detail dari tagihan berstatus lunas, per bulan tanggal_lunas.
        $paid = DB::table('tagihan_jasa_details as d')
            ->join('tagihan_jasas as t', 't.id', '=', 'd.tagihan_jasa_id')
            ->join('layanan_jasas as l', 'l.id', '=', 'd.layanan_jasa_id')
            ->whereNull('t.deleted_at')
            ->where('t.status_pembayaran', 'lunas')
            ->whereYear('t.tanggal_lunas', $tahun)
            ->groupBy('l.kelompok_pnbp', DB::raw('MONTH(t.tanggal_lunas)'))
            ->selectRaw('l.kelompok_pnbp as kelompok, MONTH(t.tanggal_lunas) as bln, SUM(d.subtotal) as total')
            ->get();

        $seriesAero = array_fill(1, 12, 0.0);
        $seriesNonAero = array_fill(1, 12, 0.0);

        foreach ($paid as $row) {
            $bln = (int) $row->bln;
            if ($row->kelompok === 'AERO') {
                $seriesAero[$bln] += (float) $row->total;
            } elseif ($row->kelompok === 'NON_AERO') {
                $seriesNonAero[$bln] += (float) $row->total;
            }
        }

        // ── Realisasi PNBP Umum per item per bulan (data input Bendahara) ──
        // Semua item master ditampilkan; bulan tanpa realisasi bernilai 0.
        $umumItems = PnbpUmumItem::where('is_active', true)->orderBy('urutan')->get();
        $umumNilai = PnbpUmumRealisasi::where('tahun', $tahun)->get()
            ->groupBy('pnbp_umum_item_id');

        $seriesUmum = array_fill(1, 12, 0.0);
        $umumRows = $umumItems->map(function ($item) use ($umumNilai, &$seriesUmum) {
            $bulan = array_fill(1, 12, 0.0);
            foreach ($umumNilai[$item->id] ?? [] as $r) {
                $bulan[(int) $r->bulan] += (float) $r->nilai;
                $seriesUmum[(int) $r->bulan] += (float) $r->nilai;
            }

            return (object) [
                'uraian' => $item->uraian,
                'bulan' => $bulan,
                'total' => array_sum($bulan),
            ];
        });

        $totalAero = array_sum($seriesAero);
        $totalNonAero = array_sum($seriesNonAero);
        $totalUmum = array_sum($seriesUmum);
        $grandTotal = $totalAero + $totalNonAero + $totalUmum;

        // ── Matriks nilai tagihan tiap layanan per bulan (Jan–Des) ──
        // Semua layanan (leaf) ditampilkan; yang belum ada transaksi bernilai 0.
        $allLayanan = LayananJasa::select('id', 'parent_id', 'nama_layanan', 'is_leaf', 'is_active', 'kelompok_pnbp')
            ->get()->keyBy('id');

        // Nama hierarki dibangun dari peta in-memory (tanpa query per baris).
        // Segmen root (level 1, mis. "A. Tarif Jasa Kebandarudaraan ...") tidak
        // disertakan agar nama layanan lebih ringkas.
        $namaLengkapOf = function ($id) use ($allLayanan) {
            $parts = [];
            $cur = $allLayanan[$id] ?? null;
            $depth = 0;
            while ($cur && $depth < 10) {
                if ($cur->parent_id !== null) {
                    array_unshift($parts, $cur->nama_layanan);
                }
                $cur = $cur->parent_id ? ($allLayanan[$cur->parent_id] ?? null) : null;
                $depth++;
            }

            // Fallback bila node yang diminta ternyata root itu sendiri.
            return $parts === [] ? (string) ($allLayanan[$id]->nama_layanan ?? '') : implode(' > ', $parts);
        };

        // Tandai layanan yang berada di bawah root "Layanan Internasional" (root D),
        // karena teks pembedanya kini dihilangkan dari nama.
        $isIntlOf = function ($id) use ($allLayanan) {
            $cur = $allLayanan[$id] ?? null;
            $root = $cur;
            $depth = 0;
            while ($cur && $depth < 10) {
                $root = $cur;
                $cur = $cur->parent_id ? ($allLayanan[$cur->parent_id] ?? null) : null;
                $depth++;
            }

            return $root && stripos($root->nama_layanan, 'Internasional') !== false;
        };

        // Inisialisasi seluruh layanan leaf (AERO / NON-AERO) dengan nilai 0.
        $byLayanan = [];
        foreach ($allLayanan as $l) {
            if ($l->is_leaf && $l->is_active && in_array($l->kelompok_pnbp, ['AERO', 'NON_AERO'], true)) {
                $byLayanan[$l->id] = (object) [
                    'layanan_jasa_id' => $l->id,
                    'nama_layanan' => $l->nama_layanan,
                    'kelompok_pnbp' => $l->kelompok_pnbp,
                    'nama_lengkap' => $namaLengkapOf($l->id),
                    'is_intl' => $isIntlOf($l->id),
                    'bulan' => array_fill(1, 12, 0.0),
                    'total' => 0.0,
                ];
            }
        }

        $matrix = DB::table('tagihan_jasa_details as d')
            ->join('tagihan_jasas as t', 't.id', '=', 'd.tagihan_jasa_id')
            ->join('layanan_jasas as l', 'l.id', '=', 'd.layanan_jasa_id')
            ->whereNull('t.deleted_at')
            ->whereYear('t.tanggal_tagihan', $tahun)
            ->groupBy('d.layanan_jasa_id', 'l.nama_layanan', 'l.kelompok_pnbp', DB::raw('MONTH(t.tanggal_tagihan)'))
            ->selectRaw('d.layanan_jasa_id, l.nama_layanan, l.kelompok_pnbp,
                MONTH(t.tanggal_tagihan) as bln, SUM(d.subtotal) as nilai')
            ->get();

        foreach ($matrix as $r) {
            $id = $r->layanan_jasa_id;
            // Layanan dengan transaksi yang (mis.) sudah non-aktif tetap ikut ditampilkan.
            if (! isset($byLayanan[$id])) {
                $byLayanan[$id] = (object) [
                    'layanan_jasa_id' => $id,
                    'nama_layanan' => $r->nama_layanan,
                    'kelompok_pnbp' => $r->kelompok_pnbp,
                    'nama_lengkap' => $namaLengkapOf($id),
                    'is_intl' => $isIntlOf($id),
                    'bulan' => array_fill(1, 12, 0.0),
                    'total' => 0.0,
                ];
            }
            $byLayanan[$id]->bulan[(int) $r->bln] += (float) $r->nilai;
            $byLayanan[$id]->total += (float) $r->nilai;
        }

        // Urutan: layanan ber-transaksi (total terbesar) di atas, sisanya alfabetis.
        $layananList = collect($byLayanan)
            ->sortBy('nama_lengkap')
            ->sortByDesc('total')
            ->values();

        $layananGrouped = $layananList->groupBy('kelompok_pnbp');

        return [
            'seriesAero' => array_values($seriesAero),
            'seriesNonAero' => array_values($seriesNonAero),
            'seriesUmum' => array_values($seriesUmum),
            'totalAero' => $totalAero,
            'totalNonAero' => $totalNonAero,
            'totalUmum' => $totalUmum,
            'grandTotal' => $grandTotal,
            'layananList' => $layananList,
            'layananGrouped' => $layananGrouped,
            'umumRows' => $umumRows,
        ];
    }

    /** Form input/edit realisasi PNBP Umum (grid 8 item × 12 bulan) untuk satu tahun. */
    public function umumEdit(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $items = PnbpUmumItem::where('is_active', true)->orderBy('urutan')->get();

        // Nilai eksisting: [item_id][bulan] => nilai
        $nilai = PnbpUmumRealisasi::where('tahun', $tahun)->get()
            ->groupBy('pnbp_umum_item_id')
            ->map(fn ($g) => $g->keyBy('bulan')->map(fn ($r) => (float) $r->nilai));

        return view('manajemen_pnbp.umum_edit', [
            'tahun' => $tahun,
            'bulanLabels' => self::BULAN,
            'items' => $items,
            'nilai' => $nilai,
        ]);
    }

    /**
     * Simpan grid PNBP Umum sekaligus: tambah/edit/hapus uraian (master item)
     * dan nilai realisasi bulanannya.
     *
     * Input:
     *  - uraian[key]          : nama item (key = id existing, atau "new_N" untuk baru)
     *  - nilai[key][bulan]    : nilai realisasi
     *  - deleted[]            : id item yang dihapus
     */
    public function umumStore(Request $request)
    {
        $data = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'uraian' => ['array'],
            'uraian.*' => ['nullable', 'string', 'max:255'],
            'nilai' => ['array'],
            'nilai.*' => ['array'],
            'nilai.*.*' => ['nullable', 'numeric', 'min:0'],
            'deleted' => ['array'],
            'deleted.*' => ['integer'],
        ]);

        $tahun = (int) $data['tahun'];
        $userId = Auth::id();

        DB::transaction(function () use ($data, $tahun, $userId) {
            // 1. Hapus item yang ditandai (cascade menghapus realisasinya).
            $deleted = array_map('intval', $data['deleted'] ?? []);
            if ($deleted) {
                PnbpUmumItem::whereIn('id', $deleted)->delete();
            }

            // 2. Petakan key baris -> id item (buat baru / perbarui uraian existing).
            $keyToId = [];
            $urutan = 0;
            foreach ($data['uraian'] ?? [] as $key => $uraian) {
                $uraian = trim((string) $uraian);
                $urutan++;

                if (is_numeric($key)) {
                    if (in_array((int) $key, $deleted, true)) {
                        continue;
                    }
                    $item = PnbpUmumItem::find((int) $key);
                    if (! $item) {
                        continue;
                    }
                    // Uraian kosong → pertahankan nama lama, hanya perbarui urutan.
                    $item->update($uraian !== '' ? ['uraian' => $uraian, 'urutan' => $urutan] : ['urutan' => $urutan]);
                    $keyToId[$key] = $item->id;
                } elseif ($uraian !== '') {
                    // Baris baru (key "new_N"); abaikan bila uraian kosong.
                    $keyToId[$key] = PnbpUmumItem::create([
                        'uraian' => $uraian, 'urutan' => $urutan, 'is_active' => true,
                    ])->id;
                }
            }

            // 3. Simpan nilai realisasi (item yang dihapus / tak valid dilewati).
            foreach ($data['nilai'] ?? [] as $key => $bulanan) {
                if (! isset($keyToId[$key])) {
                    continue;
                }
                $itemId = $keyToId[$key];
                foreach ($bulanan as $bulan => $nilai) {
                    $bulan = (int) $bulan;
                    if ($bulan < 1 || $bulan > 12) {
                        continue;
                    }
                    $nilai = (float) ($nilai ?? 0);

                    if ($nilai <= 0) {
                        PnbpUmumRealisasi::where('pnbp_umum_item_id', $itemId)
                            ->where('tahun', $tahun)->where('bulan', $bulan)->delete();

                        continue;
                    }

                    PnbpUmumRealisasi::updateOrCreate(
                        ['pnbp_umum_item_id' => $itemId, 'tahun' => $tahun, 'bulan' => $bulan],
                        ['nilai' => $nilai, 'created_by' => $userId],
                    );
                }
            }
        });

        return redirect()
            ->route('manajemen-pnbp.index', ['tahun' => $tahun])
            ->with('success', "PNBP Umum tahun {$tahun} berhasil disimpan.");
    }

    /**
     * Export Excel mengikuti format "Rincian PNBP BLU Gabungan":
     *  - Sheet FUNGSIONAL : No | Uraian | Keterangan(AERO/NON AERO) | Jan..Des | Total
     *  - Sheet PNBP UMUM  : No | Uraian | Jan..Des | Total
     */
    public function export(Request $request)
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $data = $this->aggregate($tahun);

        $subtitle = "UPBU Kelas I A.P.T. Pranoto – Samarinda – Realisasi Januari s.d. Desember {$tahun} (Rp)";

        // Baris FUNGSIONAL: AERO dulu, lalu NON-AERO (dengan kolom Keterangan).
        $fungsionalRows = [];
        foreach (['AERO' => 'AERO', 'NON_AERO' => 'NON AERO'] as $kel => $ket) {
            foreach ($data['layananGrouped'][$kel] ?? [] as $r) {
                $fungsionalRows[] = ['uraian' => $r->nama_lengkap, 'keterangan' => $ket, 'bulan' => $r->bulan];
            }
        }

        $umumRows = $data['umumRows']->map(fn ($r) => [
            'uraian' => $r->uraian, 'keterangan' => null, 'bulan' => $r->bulan,
        ])->all();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle("Rincian PNBP BLU {$tahun}");

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('FUNGSIONAL');
        $this->writeSheet($sheet1, "RINCIAN PNBP FUNGSIONAL (AERO + NON AERO) – {$tahun}", $subtitle, true, $fungsionalRows);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('PNBP UMUM');
        $this->writeSheet($sheet2, "RINCIAN PNBP UMUM – {$tahun}", $subtitle, false, $umumRows);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = "Rincian_PNBP_BLU_{$tahun}_Gabungan.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Tulis satu sheet rekap dengan gaya dokumen asli.
     *
     * @param  array<int,array{uraian:string,keterangan:?string,bulan:array<int,float>}>  $rows
     */
    private function writeSheet($sheet, string $title, string $subtitle, bool $withKet, array $rows): void
    {
        $idx = fn (int $i) => Coordinate::stringFromColumnIndex($i);

        $colKet = $withKet ? 3 : null;
        $monthFirst = $withKet ? 4 : 3;
        $monthLast = $monthFirst + 11;
        $colTotal = $monthLast + 1;
        $L = $idx($colTotal);
        $mfL = $idx($monthFirst);
        $mlL = $idx($monthLast);

        // Judul & subjudul
        $sheet->mergeCells("A1:{$L}1");
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A2:{$L}2");
        $sheet->setCellValue('A2', $subtitle);

        // Header (baris 4)
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Uraian');
        if ($withKet) {
            $sheet->setCellValue($idx($colKet).'4', 'Keterangan');
        }
        for ($m = 0; $m < 12; $m++) {
            $sheet->setCellValue($idx($monthFirst + $m).'4', self::BULAN[$m]);
        }
        $sheet->setCellValue("{$L}4", 'Total (Rp)');

        // Data
        $row = 5;
        $no = 1;
        foreach ($rows as $r) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $r['uraian']);
            if ($withKet) {
                $sheet->setCellValue($idx($colKet).$row, $r['keterangan']);
            }
            for ($m = 1; $m <= 12; $m++) {
                $sheet->setCellValue($idx($monthFirst + $m - 1).$row, (float) $r['bulan'][$m]);
            }
            $sheet->setCellValue("{$L}{$row}", "=SUM({$mfL}{$row}:{$mlL}{$row})");
            $row++;
        }
        $hasData = $row > 5;
        $lastData = $hasData ? $row - 1 : 5;

        // Baris JUMLAH
        $sumRow = $row;
        $sheet->setCellValue("A{$sumRow}", 'JUMLAH');
        $sheet->mergeCells('A'.$sumRow.':'.$idx($monthFirst - 1).$sumRow);
        for ($c = $monthFirst; $c <= $colTotal; $c++) {
            $cl = $idx($c);
            $sheet->setCellValue("{$cl}{$sumRow}", $hasData ? "=SUM({$cl}5:{$cl}{$lastData})" : 0);
        }

        // ── Styling ──
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 9, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A4:{$L}4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E5496']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        // Border seluruh tabel + font dasar 10
        $sheet->getStyle("A4:{$L}{$sumRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFBFBF']]],
        ]);
        $sheet->getStyle("A5:{$L}{$sumRow}")->getFont()->setSize(10);
        // Format angka (nol tampil "-")
        $sheet->getStyle("{$mfL}5:{$L}{$sumRow}")->getNumberFormat()->setFormatCode('#,##0;;\-');
        // Kolom No rata tengah
        $sheet->getStyle("A5:A{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // Kolom Keterangan: bold, fill biru muda, center
        if ($withKet) {
            $k = $idx($colKet);
            $sheet->getStyle("{$k}5:{$k}{$lastData}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        // Baris JUMLAH bold
        $sheet->getStyle("A{$sumRow}:{$L}{$sumRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$sumRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Lebar kolom
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(46);
        if ($withKet) {
            $sheet->getColumnDimension($idx($colKet))->setWidth(11);
        }
        for ($c = $monthFirst; $c <= $monthLast; $c++) {
            $sheet->getColumnDimension($idx($c))->setWidth(13);
        }
        $sheet->getColumnDimension($L)->setWidth(17);

        $sheet->freezePane('A5');
    }
}
