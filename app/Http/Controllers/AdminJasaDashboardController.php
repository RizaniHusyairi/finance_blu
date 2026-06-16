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
        $todayActivity = $this->getTodayActivity($admin, $service);

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
            'todayActivity',
            'filters',
            'filterOptions'
        ));
    }

    private function getTodayActivity($admin, AdminJasaDashboardService $service): array
    {
        $today = now()->toDateString();
        $emptyFilters = [
            'date_from' => null,
            'date_to' => null,
            'month' => null,
            'year' => null,
            'mitra_jasa_id' => null,
            'layanan_jasa_id' => null,
            'status' => null,
            'status_pembayaran' => null,
        ];
        $base = fn () => $service->baseQuery($admin, $emptyFilters);

        $createdToday = $base()
            ->with('mitra')
            ->whereDate('created_at', $today);

        $manualPaymentProofs = $base()
            ->with('mitra')
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', 'menunggu_verifikasi');

        $dueToday = $base()
            ->with('mitra')
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->whereDate('tanggal_jatuh_tempo', $today);

        $overdue = $base()
            ->with('mitra')
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', $today);

        $draftCount = $base()->where('status', 'DRAFT')->count();
        $revisionCount = $base()->whereIn('status', ['DITOLAK', 'REVISI'])->count();
        $manualPaymentProofCount = (clone $manualPaymentProofs)->count();
        $dueTodayCount = (clone $dueToday)->count();
        $overdueCount = (clone $overdue)->count();

        return [
            'storage_key' => 'admin_jasa_activity_seen_' . ($admin?->id ?? 'guest') . '_' . $today,
            'date_label' => now()->isoFormat('dddd, D MMMM Y'),
            'created_today_count' => (clone $createdToday)->count(),
            'created_today_nominal' => (float) (clone $createdToday)->sum('total_tagihan'),
            'manual_payment_proof_count' => $manualPaymentProofCount,
            'due_today_count' => $dueTodayCount,
            'overdue_count' => $overdueCount,
            'draft_count' => $draftCount,
            'revision_count' => $revisionCount,
            'needs_attention' => $manualPaymentProofCount > 0 || $dueTodayCount > 0 || $overdueCount > 0 || $draftCount > 0 || $revisionCount > 0,
            'latest_manual_payment_proofs' => (clone $manualPaymentProofs)
                ->orderByDesc('updated_at')
                ->limit(3)
                ->get(),
            'latest_due_today' => (clone $dueToday)
                ->orderBy('tanggal_jatuh_tempo')
                ->limit(3)
                ->get(),
            'latest_overdue' => (clone $overdue)
                ->orderBy('tanggal_jatuh_tempo')
                ->limit(3)
                ->get(),
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
}
