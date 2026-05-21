<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Services\AdminJasaDashboardService;
use Illuminate\Http\Request;

class AdminJasaDashboardController extends Controller
{
    public function index(Request $request, AdminJasaDashboardService $service)
    {
        $admin = $request->user();
        $filters = $this->resolveFilters($request);
        $allowedItemIds = $service->getAllowedItemIds($admin);

        $summaryCards = $service->getSummaryCards($admin, $filters);
        $verificationSummary = $service->getVerificationSummary($admin, $filters);
        $mitraSummary = $service->getMitraSummary($admin, $filters);
        $layananSummary = $service->getLayananSummary($admin, $filters);
        $latestTagihan = $service->getLatestTagihan($admin, $filters);
        $unpaidTagihan = $service->getUnpaidTagihan($admin, $filters);
        $overdueTagihan = $service->getOverdueTagihan($admin, $filters);
        $latestNotifications = $service->getLatestNotifications($admin, $filters);
        $chartTagihanBulanan = $service->getChartTagihanBulanan($admin, $filters);
        $chartTagihanByStatus = $service->getChartTagihanByStatus($admin, $filters);
        $chartTopMitra = $service->getChartTopMitra($admin, $filters);
        $chartTopLayanan = $service->getChartTopLayanan($admin, $filters);
        $persentaseLunas = $service->getPersentaseLunas($admin, $filters);
        $calendar = $service->getCalendar($admin, $filters);

        $filterOptions = [
            'mitras' => MitraJasa::query()
                ->whereHas('layananJasa', fn ($query) => $query->whereIn('layanan_jasas.id', $allowedItemIds))
                ->orderBy('nama_mitra')
                ->get(['id', 'nama_mitra']),
            'layanans' => LayananJasa::query()
                ->whereIn('id', $allowedItemIds)
                ->orderBy('nama_layanan')
                ->get(['id', 'nama_layanan']),
        ];

        return view('admin_jasa.dashboard', compact(
            'summaryCards',
            'verificationSummary',
            'mitraSummary',
            'layananSummary',
            'latestTagihan',
            'unpaidTagihan',
            'overdueTagihan',
            'latestNotifications',
            'chartTagihanBulanan',
            'chartTagihanByStatus',
            'chartTopMitra',
            'chartTopLayanan',
            'persentaseLunas',
            'calendar',
            'filters',
            'filterOptions'
        ));
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
}
