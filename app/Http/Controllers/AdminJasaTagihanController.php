<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Services\AdminJasaDashboardService;
use Illuminate\Http\Request;

class AdminJasaTagihanController extends Controller
{
    public function logBulanan(Request $request, AdminJasaDashboardService $service)
    {
        $filters = $this->resolveFilters($request);
        $query = $service->baseQuery($request->user(), $filters)
            ->with(['mitra', 'details.layananJasa'])
            ->latest('tanggal_tagihan')
            ->latest('id');

        return $this->renderTagihanList($request, $service, $query, $filters, [
            'mode' => 'log',
            'title' => 'Log Tagihan Bulanan',
            'subtitle' => 'Daftar tagihan jasa per bulan sesuai layanan yang dikelola Admin Jasa.',
            'empty' => 'Belum ada tagihan jasa pada periode ini.',
        ]);
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
}
