<?php

namespace App\Http\Controllers;

use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperAdminJasaLaporanController extends Controller
{
    private const REPORTS = [
        'rekap-tagihan' => 'Rekap Tagihan',
        'rekap-layanan' => 'Rekap Tagihan per Layanan',
        'rekap-terima-setor' => 'Rekap Terima Setor',
        'rekap-pembayaran' => 'Rekap Pembayaran',
        'rekap-piutang' => 'Rekap Piutang',
        'performa-mitra' => 'Rekap Performa Pembayaran Mitra',
    ];

    public function rekapTagihan(Request $request)
    {
        $data = $this->reportData('rekap-tagihan', $this->resolveFilters($request));

        return view('super_admin_jasa.laporan.rekap-tagihan', $data + [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function rekapLayanan(Request $request)
    {
        $data = $this->reportData('rekap-layanan', $this->resolveFilters($request));

        return view('super_admin_jasa.laporan.rekap-layanan', $data + [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function rekapTerimaSetor(Request $request)
    {
        $data = $this->reportData('rekap-terima-setor', $this->resolveFilters($request));

        return view('super_admin_jasa.laporan.rekap-terima-setor', $data + [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function rekapPembayaran(Request $request)
    {
        $data = $this->reportData('rekap-pembayaran', $this->resolveFilters($request));

        return view('super_admin_jasa.laporan.rekap-pembayaran', $data + [
            'filterOptions' => $this->filterOptions() + ['channels' => $this->paymentChannelOptions()],
        ]);
    }

    public function rekapPiutang(Request $request)
    {
        $data = $this->reportData('rekap-piutang', $this->resolveFilters($request));

        return view('super_admin_jasa.laporan.rekap-piutang', $data + [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function performaMitra(Request $request)
    {
        $data = $this->reportData('performa-mitra', $this->resolveFilters($request));

        return view('super_admin_jasa.laporan.performa-mitra', $data + [
            'filterOptions' => $this->filterOptions(),
        ]);
    }

    public function export(Request $request, string $report, string $format)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $filters = $this->resolveFilters($request);
        $payload = $this->reportData($report, $filters, true) + [
            'report' => $report,
            'title' => self::REPORTS[$report],
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters, $report),
            'generatedAt' => now(),
            'exportFormat' => $format,
        ];

        $filename = $this->exportFilename($report, $filters);

        if ($format === 'pdf') {
            return Pdf::loadView('super_admin_jasa.laporan.export', $payload)
                ->setPaper('a4', 'landscape')
                ->download($filename . '.pdf');
        }

        return $this->excelResponse('super_admin_jasa.laporan.export', $payload, $filename);
    }

    private function reportData(string $report, array $filters, bool $export = false): array
    {
        return match ($report) {
            'rekap-tagihan' => $this->rekapTagihanData($filters),
            'rekap-layanan' => $this->rekapLayananData($filters),
            'rekap-terima-setor' => $this->rekapTerimaSetorData($filters, $export),
            'rekap-pembayaran' => $this->rekapPembayaranData($filters, $export),
            'rekap-piutang' => $this->rekapPiutangData($filters, $export),
            'performa-mitra' => $this->performaMitraData($filters),
            default => abort(404),
        };
    }

    private function rekapTagihanData(array $filters): array
    {
        $base = $this->baseTagihanQuery($filters);

        $perBulan = (clone $base)
            ->selectRaw('MONTH(tanggal_tagihan) as bulan')
            ->selectRaw('COUNT(*) as jumlah_tagihan')
            ->selectRaw('SUM(total_tagihan) as total_nominal')
            ->selectRaw('SUM(CASE WHEN status_pembayaran = "lunas" THEN jumlah_dibayar ELSE 0 END) as nominal_lunas')
            ->selectRaw('SUM(sisa_tagihan) as nominal_sisa')
            ->selectRaw('SUM(CASE WHEN status_pembayaran = "lunas" THEN 1 ELSE 0 END) as jumlah_lunas')
            ->selectRaw('SUM(CASE WHEN status_pembayaran != "lunas" THEN 1 ELSE 0 END) as jumlah_belum_lunas')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return [
            'perBulan' => $perBulan,
            'summary' => [
                'count' => (clone $base)->count(),
                'nominal' => (float) (clone $base)->sum('total_tagihan'),
                'lunas' => (float) (clone $base)->where('status_pembayaran', 'lunas')->sum('jumlah_dibayar'),
                'sisa' => (float) (clone $base)->sum('sisa_tagihan'),
            ],
            'filters' => $filters,
        ];
    }

    private function rekapLayananData(array $filters): array
    {
        $months = $filters['month'] ? [(int) $filters['month']] : range(1, 12);

        $rows = DB::table('tagihan_jasa_details as d')
            ->join('tagihan_jasas as t', 't.id', '=', 'd.tagihan_jasa_id')
            ->leftJoin('layanan_jasas as l', 'l.id', '=', 'd.layanan_jasa_id')
            ->whereNull('t.deleted_at')
            ->whereYear('t.tanggal_tagihan', (int) $filters['year'])
            ->when($filters['month'], fn ($q, $v) => $q->whereMonth('t.tanggal_tagihan', (int) $v))
            ->when($filters['tipe_pnbp'], fn ($q, $v) => $q->where('t.tipe_pnbp', $v))
            ->when($filters['mitra_jasa_id'], fn ($q, $v) => $q->where('t.mitra_jasa_id', $v))
            ->selectRaw('d.layanan_jasa_id')
            ->selectRaw('COALESCE(l.nama_layanan, "Layanan Tidak Diketahui") as nama_layanan')
            ->selectRaw('COALESCE(l.kode_layanan, "") as kode_layanan')
            ->selectRaw('MONTH(t.tanggal_tagihan) as bulan')
            ->selectRaw('COUNT(DISTINCT t.id) as jumlah_tagihan')
            ->selectRaw('SUM(d.subtotal) as total_nominal')
            ->selectRaw('SUM(CASE WHEN t.status_pembayaran = "lunas" THEN d.subtotal ELSE 0 END) as nominal_lunas')
            ->selectRaw('SUM(CASE WHEN t.status_pembayaran != "lunas" OR t.status_pembayaran IS NULL THEN d.subtotal ELSE 0 END) as nominal_belum_lunas')
            ->groupBy('d.layanan_jasa_id', 'nama_layanan', 'kode_layanan', 'bulan')
            ->orderBy('nama_layanan')
            ->orderBy('bulan')
            ->get();

        $layanans = $rows
            ->groupBy('layanan_jasa_id')
            ->map(function ($items) use ($months) {
                $first = $items->first();
                $byMonth = $items->keyBy('bulan');

                return (object) [
                    'layanan_jasa_id' => $first->layanan_jasa_id,
                    'nama_layanan' => $first->nama_layanan,
                    'kode_layanan' => $first->kode_layanan,
                    'months' => collect($months)->mapWithKeys(fn ($month) => [
                        $month => (object) [
                            'jumlah_tagihan' => (int) ($byMonth->get($month)->jumlah_tagihan ?? 0),
                            'total_nominal' => (float) ($byMonth->get($month)->total_nominal ?? 0),
                            'nominal_lunas' => (float) ($byMonth->get($month)->nominal_lunas ?? 0),
                            'nominal_belum_lunas' => (float) ($byMonth->get($month)->nominal_belum_lunas ?? 0),
                        ],
                    ]),
                    'jumlah_tagihan' => $items->sum('jumlah_tagihan'),
                    'total_nominal' => $items->sum('total_nominal'),
                    'nominal_lunas' => $items->sum('nominal_lunas'),
                    'nominal_belum_lunas' => $items->sum('nominal_belum_lunas'),
                ];
            })
            ->sortByDesc('total_nominal')
            ->values();

        $perBulan = collect($months)->mapWithKeys(fn ($month) => [
            $month => [
                'jumlah_tagihan' => (int) $rows->where('bulan', $month)->sum('jumlah_tagihan'),
                'total_nominal' => (float) $rows->where('bulan', $month)->sum('total_nominal'),
                'nominal_lunas' => (float) $rows->where('bulan', $month)->sum('nominal_lunas'),
                'nominal_belum_lunas' => (float) $rows->where('bulan', $month)->sum('nominal_belum_lunas'),
            ],
        ]);

        $rankingBulanan = collect($months)->mapWithKeys(fn ($month) => [
            $month => $rows
                ->where('bulan', $month)
                ->sortByDesc('total_nominal')
                ->values(),
        ]);

        return [
            'layanans' => $layanans,
            'months' => $months,
            'perBulan' => $perBulan,
            'rankingBulanan' => $rankingBulanan,
            'summary' => [
                'layanan_count' => $layanans->count(),
                'jumlah_tagihan' => (int) $rows->sum('jumlah_tagihan'),
                'nominal' => (float) $rows->sum('total_nominal'),
                'nominal_lunas' => (float) $rows->sum('nominal_lunas'),
                'nominal_belum_lunas' => (float) $rows->sum('nominal_belum_lunas'),
            ],
            'filters' => $filters,
        ];
    }

    private function rekapTerimaSetorData(array $filters, bool $export = false): array
    {
        $base = TagihanJasa::query()
            ->with(['mitra', 'kontrakMitraJasa'])
            ->where('status_pembayaran', 'lunas')
            ->whereYear('tanggal_lunas', (int) $filters['year'])
            ->when($filters['month'], fn ($q, $v) => $q->whereMonth('tanggal_lunas', (int) $v))
            ->when($filters['tipe_pnbp'], fn ($q, $v) => $q->where('tipe_pnbp', $v))
            ->when($filters['mitra_jasa_id'], fn ($q, $v) => $q->where('mitra_jasa_id', $v))
            ->orderByDesc('tanggal_lunas')
            ->orderByDesc('id');

        $perBulan = (clone $base)
            ->reorder()
            ->selectRaw('MONTH(tanggal_lunas) as bulan')
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw('SUM(jumlah_dibayar) as nominal')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        return [
            'tagihans' => $export ? (clone $base)->get() : (clone $base)->paginate(20)->withQueryString(),
            'summary' => [
                'count' => (clone $base)->count(),
                'nominal_diterima' => (float) (clone $base)->sum('jumlah_dibayar'),
            ],
            'perBulan' => $perBulan,
            'filters' => $filters,
        ];
    }

    private function rekapPembayaranData(array $filters, bool $export = false): array
    {
        $base = TagihanJasa::query()
            ->with(['mitra'])
            ->where('status_pembayaran', 'lunas')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', (int) $filters['year'])
            ->when($filters['month'], fn ($q, $v) => $q->whereMonth('paid_at', (int) $v))
            ->when($filters['tipe_pnbp'], fn ($q, $v) => $q->where('tipe_pnbp', $v))
            ->when($filters['mitra_jasa_id'], fn ($q, $v) => $q->where('mitra_jasa_id', $v))
            ->when($filters['payment_channel'], fn ($q, $v) => $q->where('payment_channel', $v))
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        $perChannel = (clone $base)
            ->reorder()
            ->selectRaw('COALESCE(payment_channel, "-") as channel')
            ->selectRaw('COUNT(*) as jumlah')
            ->selectRaw('SUM(jumlah_dibayar) as nominal')
            ->groupBy('payment_channel')
            ->orderByDesc('nominal')
            ->get();

        return [
            'tagihans' => $export ? (clone $base)->get() : (clone $base)->paginate(20)->withQueryString(),
            'summary' => [
                'count' => (clone $base)->count(),
                'nominal' => (float) (clone $base)->sum('jumlah_dibayar'),
            ],
            'perChannel' => $perChannel,
            'filters' => $filters,
        ];
    }

    private function rekapPiutangData(array $filters, bool $export = false): array
    {
        $base = TagihanJasa::query()
            ->with(['mitra'])
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->whereYear('tanggal_tagihan', (int) $filters['year'])
            ->when($filters['month'], fn ($q, $v) => $q->whereMonth('tanggal_tagihan', (int) $v))
            ->when($filters['tipe_pnbp'], fn ($q, $v) => $q->where('tipe_pnbp', $v))
            ->when($filters['mitra_jasa_id'], fn ($q, $v) => $q->where('mitra_jasa_id', $v));

        $today = now()->startOfDay()->toDateString();
        $aging = [
            'belum_jatuh_tempo' => (clone $base)
                ->where(function ($q) use ($today) {
                    $q->whereNull('tanggal_jatuh_tempo')->orWhereDate('tanggal_jatuh_tempo', '>=', $today);
                }),
            '1_30' => (clone $base)
                ->whereDate('tanggal_jatuh_tempo', '<', $today)
                ->whereRaw('DATEDIFF(?, tanggal_jatuh_tempo) BETWEEN 1 AND 30', [$today]),
            '31_60' => (clone $base)
                ->whereRaw('DATEDIFF(?, tanggal_jatuh_tempo) BETWEEN 31 AND 60', [$today]),
            '61_90' => (clone $base)
                ->whereRaw('DATEDIFF(?, tanggal_jatuh_tempo) BETWEEN 61 AND 90', [$today]),
            'lebih_90' => (clone $base)
                ->whereRaw('DATEDIFF(?, tanggal_jatuh_tempo) > 90', [$today]),
        ];

        $agingSummary = [];
        foreach ($aging as $key => $q) {
            $agingSummary[$key] = [
                'count' => (clone $q)->count(),
                'nominal' => (float) (clone $q)->sum('sisa_tagihan'),
            ];
        }

        $piutangQuery = (clone $base)
            ->orderBy('tanggal_jatuh_tempo')
            ->orderByDesc('sisa_tagihan');

        return [
            'piutangs' => $export ? (clone $piutangQuery)->get() : (clone $piutangQuery)->paginate(20)->withQueryString(),
            'summary' => [
                'count' => (clone $base)->count(),
                'nominal_sisa' => (float) (clone $base)->sum('sisa_tagihan'),
                'nominal_tagihan' => (float) (clone $base)->sum('total_tagihan'),
                'nominal_dibayar' => (float) (clone $base)->sum('jumlah_dibayar'),
            ],
            'agingSummary' => $agingSummary,
            'filters' => $filters,
        ];
    }

    private function performaMitraData(array $filters): array
    {
        $today = now()->startOfDay();

        $mitras = MitraJasa::query()
            ->with(['tagihanJasas' => function ($q) use ($filters) {
                $q->when($filters['tipe_pnbp'], fn ($qq, $v) => $qq->where('tipe_pnbp', $v))
                    ->where('status', 'PUBLISHED');
            }])
            ->when($filters['mitra_jasa_id'], fn ($q, $v) => $q->where('id', $v))
            ->orderBy('nama_mitra')
            ->get();

        $rows = $mitras->map(function ($mitra) use ($today) {
            $tagihans = $mitra->tagihanJasas;
            $jumlahTagihan = $tagihans->count();
            $jumlahLunas = $tagihans->where('status_pembayaran', 'lunas')->count();
            $jumlahOutstanding = $jumlahTagihan - $jumlahLunas;

            $outstandingMaxOverdue = 0;
            $sisaOutstanding = 0.0;
            foreach ($tagihans->where('status_pembayaran', '!=', 'lunas') as $t) {
                $sisaOutstanding += (float) $t->sisa_tagihan;
                if ($t->tanggal_jatuh_tempo) {
                    $overdue = (int) $today->diffInDays($t->tanggal_jatuh_tempo->copy()->startOfDay(), false);
                    $overdue = -$overdue;
                    if ($overdue > $outstandingMaxOverdue) {
                        $outstandingMaxOverdue = $overdue;
                    }
                }
            }

            $historisMaxLateDays = 0;
            $totalLateDays = 0;
            $countWithDueDate = 0;
            foreach ($tagihans->where('status_pembayaran', 'lunas') as $t) {
                if ($t->tanggal_jatuh_tempo && $t->tanggal_lunas) {
                    $late = (int) $t->tanggal_jatuh_tempo->copy()->startOfDay()
                        ->diffInDays($t->tanggal_lunas->copy()->startOfDay(), false);
                    if ($late > $historisMaxLateDays) {
                        $historisMaxLateDays = $late;
                    }
                    $totalLateDays += max(0, $late);
                    $countWithDueDate++;
                }
            }

            $rataLateDays = $countWithDueDate > 0 ? round($totalLateDays / $countWithDueDate, 1) : 0;
            $status = match (true) {
                $jumlahTagihan === 0 => 'BARU',
                $outstandingMaxOverdue > 30 => 'MACET',
                $outstandingMaxOverdue > 7 => 'PERLU_PERHATIAN',
                $outstandingMaxOverdue > 0 || $historisMaxLateDays > 7 => 'CUKUP_LANCAR',
                default => 'LANCAR',
            };

            return (object) [
                'id' => $mitra->id,
                'nama_mitra' => $mitra->nama_mitra,
                'jumlah_tagihan' => $jumlahTagihan,
                'jumlah_lunas' => $jumlahLunas,
                'jumlah_outstanding' => $jumlahOutstanding,
                'sisa_outstanding' => $sisaOutstanding,
                'outstanding_max_overdue' => $outstandingMaxOverdue,
                'historis_max_late' => $historisMaxLateDays,
                'rata_late' => $rataLateDays,
                'status_performa' => $status,
            ];
        });

        if ($filters['status_performa']) {
            $rows = $rows->where('status_performa', $filters['status_performa'])->values();
        }

        $statusCount = collect(['BARU', 'LANCAR', 'CUKUP_LANCAR', 'PERLU_PERHATIAN', 'MACET'])
            ->mapWithKeys(fn ($s) => [$s => $mitras->count() ? $rows->where('status_performa', $s)->count() : 0])
            ->all();

        return [
            'rows' => $rows,
            'statusCount' => $statusCount,
            'filters' => $filters,
        ];
    }

    private function baseTagihanQuery(array $filters)
    {
        return TagihanJasa::query()
            ->whereYear('tanggal_tagihan', (int) $filters['year'])
            ->when($filters['month'], fn ($q, $v) => $q->whereMonth('tanggal_tagihan', (int) $v))
            ->when($filters['tipe_pnbp'], fn ($q, $v) => $q->where('tipe_pnbp', $v))
            ->when($filters['mitra_jasa_id'], fn ($q, $v) => $q->where('mitra_jasa_id', $v));
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'year' => (int) $request->input('year', now()->year),
            'month' => $request->input('month'),
            'mitra_jasa_id' => $request->input('mitra_jasa_id'),
            'tipe_pnbp' => $request->input('tipe_pnbp'),
            'payment_channel' => $request->input('payment_channel'),
            'status_performa' => $request->input('status_performa'),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'mitras' => MitraJasa::orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'tahuns' => $this->availableYears(),
            'tipe_pnbps' => ['FUNGSI', 'NON_FUNGSI', 'KONSESI'],
        ];
    }

    private function availableYears(): array
    {
        $years = TagihanJasa::query()
            ->selectRaw('YEAR(tanggal_tagihan) as y')
            ->distinct()
            ->orderByDesc('y')
            ->pluck('y')
            ->filter()
            ->all();

        $currentYear = (int) now()->year;
        if (! in_array($currentYear, $years, true)) {
            array_unshift($years, $currentYear);
        }

        return $years;
    }

    private function paymentChannelOptions()
    {
        return TagihanJasa::query()
            ->whereNotNull('payment_channel')
            ->distinct()
            ->orderBy('payment_channel')
            ->pluck('payment_channel');
    }

    private function filterLabels(array $filters, string $report): array
    {
        $labels = ['Tahun' => $filters['year'] ?: '-'];

        if (in_array($report, ['rekap-tagihan', 'rekap-layanan', 'rekap-terima-setor', 'rekap-pembayaran', 'rekap-piutang'], true)) {
            $labels['Bulan'] = $filters['month']
                ? ($this->monthLabels()[(int) $filters['month']] ?? $filters['month'])
                : 'Semua Bulan';
        }

        if (! empty($filters['tipe_pnbp'])) {
            $labels['Tipe PNBP'] = $filters['tipe_pnbp'];
        }

        if (! empty($filters['mitra_jasa_id'])) {
            $labels['Mitra'] = MitraJasa::find($filters['mitra_jasa_id'])?->nama_mitra ?? $filters['mitra_jasa_id'];
        }

        if (! empty($filters['payment_channel'])) {
            $labels['Kanal Bayar'] = $filters['payment_channel'];
        }

        if (! empty($filters['status_performa'])) {
            $labels['Status Performa'] = str_replace('_', ' ', $filters['status_performa']);
        }

        return $labels;
    }

    private function monthLabels(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    private function excelResponse(string $view, array $payload, string $filename)
    {
        return response("\xEF\xBB\xBF" . view($view, $payload)->render(), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'max-age=0, must-revalidate',
        ]);
    }

    private function exportFilename(string $report, array $filters): string
    {
        $parts = [$report, $filters['year'] ?? now()->year];

        if (! empty($filters['month'])) {
            $parts[] = str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT);
        }

        $parts[] = now()->format('Ymd-His');

        return Str::slug(implode('-', $parts), '-');
    }
}
