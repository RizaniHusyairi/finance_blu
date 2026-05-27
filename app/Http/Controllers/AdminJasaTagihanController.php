<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Services\AdminJasaDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminJasaTagihanController extends Controller
{
    public function logBulanan(Request $request, AdminJasaDashboardService $service)
    {
        $filters = $this->resolveFilters($request);
        $query = $this->logBulananQuery($request, $service, $filters);

        return $this->renderTagihanList($request, $service, $query, $filters, [
            'mode' => 'log',
            'title' => 'Log Tagihan Bulanan',
            'subtitle' => 'Daftar tagihan jasa per bulan sesuai layanan yang dikelola Admin Jasa.',
            'empty' => 'Belum ada tagihan jasa pada periode ini.',
        ]);
    }

    public function exportLogBulanan(Request $request, AdminJasaDashboardService $service, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $filters = $this->resolveFilters($request);
        $query = $this->logBulananQuery($request, $service, $filters);

        $payload = [
            'tagihans' => (clone $query)->get(),
            'summary' => [
                'count' => (clone $query)->count(),
                'nominal' => (float) (clone $query)->sum('total_tagihan'),
                'sisa' => (float) (clone $query)->sum('sisa_tagihan'),
            ],
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters),
            'title' => 'Log Tagihan Bulanan',
            'generatedAt' => now(),
            'exportFormat' => $format,
        ];

        $filename = $this->exportFilename($filters);

        if ($format === 'pdf') {
            return Pdf::loadView('admin_jasa.tagihan.export', $payload)
                ->setPaper('a4', 'landscape')
                ->download($filename . '.pdf');
        }

        return $this->excelResponse('admin_jasa.tagihan.export', $payload, $filename);
    }

    public function jatuhTempo(Request $request, AdminJasaDashboardService $service)
    {
        $filters = $this->resolveFilters($request);
        $query = $service->baseQuery($request->user(), $filters)
            ->with(['mitra'])
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString())
            ->orderBy('tanggal_jatuh_tempo')
            ->latest('id');

        return $this->renderTagihanList($request, $service, $query, $filters, [
            'mode' => 'jatuh_tempo',
            'title' => 'Tagihan Lewat Jatuh Tempo',
            'subtitle' => 'Prioritas penagihan untuk tagihan jasa yang melewati tanggal jatuh tempo.',
            'empty' => 'Tidak ada tagihan lewat jatuh tempo pada periode ini.',
        ]);
    }

    public function mitra(Request $request, AdminJasaDashboardService $service)
    {
        $filters = $this->resolveFilters($request);
        $allowedItemIds = $service->getAllowedItemIds($request->user());
        $canViewAll = $request->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);

        $mitras = MitraJasa::query()
            ->when(! $canViewAll, function ($query) use ($allowedItemIds) {
                $query->whereHas('layananJasa', fn ($layanan) => $layanan->whereIn('layanan_jasas.id', $allowedItemIds))
                    ->orWhereHas('tagihanJasas.details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedItemIds));
            })
            ->withCount([
                'tagihanJasas as total_tagihan_jasa' => fn ($query) => $this->applyDateFilters($query, $filters)
                    ->whereHas('details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedItemIds)),
                'tagihanJasas as total_belum_lunas' => fn ($query) => $this->applyDateFilters($query, $filters)
                    ->whereHas('details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedItemIds))
                    ->where('status', 'PUBLISHED')
                    ->where('status_pembayaran', '!=', 'lunas'),
                'konsesi as konsesi_aktif_count' => fn ($query) => $query
                    ->whereIn('layanan_jasa_id', $allowedItemIds)
                    ->where('status_aktif', true),
                'penjualan as laporan_penjualan_count' => fn ($query) => $query
                    ->whereIn('layanan_jasa_id', $allowedItemIds),
                'penjualan as laporan_menunggu_count' => fn ($query) => $query
                    ->whereIn('layanan_jasa_id', $allowedItemIds)
                    ->where('status', 'diajukan'),
            ])
            ->withSum([
                'tagihanJasas as nominal_tagihan_jasa' => fn ($query) => $this->applyDateFilters($query, $filters)
                    ->whereHas('details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedItemIds)),
            ], 'total_tagihan')
            ->orderBy('nama_mitra')
            ->paginate(15)
            ->withQueryString();

        $filterOptions = $this->filterOptions($request, $service);

        return view('admin_jasa.mitra.index', compact('mitras', 'filters', 'filterOptions'));
    }

    private function logBulananQuery(Request $request, AdminJasaDashboardService $service, array $filters)
    {
        return $service->baseQuery($request->user(), $filters)
            ->with(['mitra', 'details.layananJasa'])
            ->latest('tanggal_tagihan')
            ->latest('id');
    }

    private function renderTagihanList(Request $request, AdminJasaDashboardService $service, $query, array $filters, array $page)
    {
        $summary = [
            'count' => (clone $query)->count(),
            'nominal' => (float) (clone $query)->sum('total_tagihan'),
            'sisa' => (float) (clone $query)->sum('sisa_tagihan'),
        ];

        $tagihans = $query->paginate(15)->withQueryString();
        $filterOptions = $this->filterOptions($request, $service);

        return view('admin_jasa.tagihan.index', compact('tagihans', 'filters', 'filterOptions', 'summary', 'page'));
    }

    private function filterOptions(Request $request, AdminJasaDashboardService $service): array
    {
        $allowedItemIds = $service->getAllowedItemIds($request->user());
        $canViewAll = $request->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);

        return [
            'mitras' => MitraJasa::query()
                ->when(! $canViewAll, function ($query) use ($allowedItemIds) {
                    $query->whereHas('layananJasa', fn ($layanan) => $layanan->whereIn('layanan_jasas.id', $allowedItemIds))
                        ->orWhereHas('tagihanJasas.details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedItemIds));
                })
                ->orderBy('nama_mitra')
                ->get(['id', 'nama_mitra']),
            'layanans' => LayananJasa::query()
                ->whereIn('id', $allowedItemIds)
                ->orderBy('nama_layanan')
                ->get(['id', 'nama_layanan']),
        ];
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'month' => $request->input('month', now()->month),
            'year' => $request->input('year', now()->year),
            'mitra_jasa_id' => $request->input('mitra_jasa_id'),
            'layanan_jasa_id' => $request->input('layanan_jasa_id'),
            'status' => $request->input('status'),
            'status_pembayaran' => $request->input('status_pembayaran'),
        ];
    }

    private function applyDateFilters($query, array $filters)
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('tanggal_tagihan', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('tanggal_tagihan', '<=', $filters['date_to']);
        }

        if (! empty($filters['month'])) {
            $query->whereMonth('tanggal_tagihan', (int) $filters['month']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('tanggal_tagihan', (int) $filters['year']);
        }

        return $query;
    }

    private function filterLabels(array $filters): array
    {
        $labels = [
            'Bulan' => $filters['month'] ? (($this->monthLabels()[(int) $filters['month']] ?? $filters['month']) . ' ' . $filters['year']) : (string) $filters['year'],
        ];

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $labels['Rentang Tanggal'] = ($filters['date_from'] ?: 'Awal') . ' s.d. ' . ($filters['date_to'] ?: 'Akhir');
        }

        if (! empty($filters['mitra_jasa_id'])) {
            $labels['Mitra'] = MitraJasa::find($filters['mitra_jasa_id'])?->nama_mitra ?? $filters['mitra_jasa_id'];
        }

        if (! empty($filters['layanan_jasa_id'])) {
            $labels['Layanan'] = LayananJasa::find($filters['layanan_jasa_id'])?->nama_layanan ?? $filters['layanan_jasa_id'];
        }

        if (! empty($filters['status'])) {
            $labels['Status'] = str_replace('_', ' ', $filters['status']);
        }

        if (! empty($filters['status_pembayaran'])) {
            $labels['Status Pembayaran'] = str_replace('_', ' ', $filters['status_pembayaran']);
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

    private function exportFilename(array $filters): string
    {
        $parts = ['log-tagihan-bulanan', $filters['year'] ?? now()->year];

        if (! empty($filters['month'])) {
            $parts[] = str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT);
        }

        $parts[] = now()->format('Ymd-His');

        return Str::slug(implode('-', $parts), '-');
    }
}
