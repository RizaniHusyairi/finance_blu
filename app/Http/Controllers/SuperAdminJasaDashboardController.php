<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\KontrakMitraJasa;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SuperAdminJasaDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isKoordinatorJasa = $user?->hasRole('Koordinator Jasa') === true
            && ! $user->hasAnyRole(['Super Admin', 'Super Admin Jasa']);

        $stats = [
            'mitra_total' => MitraJasa::count(),
            'mitra_aktif' => MitraJasa::where('status_aktif', true)->count(),
            'mitra_akun' => MitraJasa::whereHas('user')->count(),
            'mitra_tanpa_akun' => MitraJasa::whereDoesntHave('user')->count(),
            'kontrak_aktif' => KontrakMitraJasa::where('status_kontrak', 'AKTIF')->count(),
            'kontrak_akan_berakhir' => KontrakMitraJasa::where('status_kontrak', 'AKTIF')
                ->whereBetween('tanggal_selesai', [now()->toDateString(), now()->addDays(60)->toDateString()])
                ->count(),
            'layanan_billable' => LayananJasa::where('is_active', true)->where('is_leaf', true)->count(),
            'admin_jasa' => User::role('Admin Jasa')->count(),
            'tagihan_bulan_ini' => TagihanJasa::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'tagihan_lunas' => TagihanJasa::where('status', 'LUNAS')->count(),
        ];

        $mitraTanpaLayanan = MitraJasa::query()
            ->where('status_aktif', true)
            ->whereDoesntHave('layananJasa', fn ($query) => $query->where('mitra_jasa_layanan.status_aktif', true))
            ->orderBy('nama_mitra')
            ->limit(8)
            ->get();

        $adminTanpaLayanan = User::role('Admin Jasa')
            ->whereDoesntHave('layananJasaDikelola', fn ($query) => $query->where('admin_jasa_layanan.status_aktif', true))
            ->with('profilable')
            ->limit(8)
            ->get();

        $tagihanByStatus = TagihanJasa::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $pendingVerifikasiTagihans = collect();
        $verifiedByKoordinatorRows = collect();
        $recentTagihans = collect();
        $koordinatorStats = [];

        if ($isKoordinatorJasa) {
            $workflowService = app(\App\Services\WorkflowService::class);
            $pendingRaw = TagihanJasa::with(['mitra', 'mitraLegacy', 'creator', 'workflowInstance.approvals'])
                ->whereHas('workflowInstance', function ($query) use ($user) {
                    $query->where('status', 'IN_PROGRESS')
                        ->whereHas('approvals', function ($approval) use ($user) {
                            $approval->where('status', 'PENDING')
                                ->whereIn('role_code', $user->getRoleNames());
                        });
                })
                ->latest()
                ->limit(12)
                ->get();

            $pendingVerifikasiTagihans = $pendingRaw
                ->filter(fn ($tagihan) => $workflowService->hasPendingApprovalForUser($tagihan, $user->id))
                ->values();

            $recentTagihans = TagihanJasa::with(['mitra', 'mitraLegacy'])
                ->latest()
                ->limit(10)
                ->get();

            $verifiedByKoordinatorRows = \App\Models\WorkflowApproval::with('instance.workflowable')
                ->where('acted_by_user_id', $user->id)
                ->where('role_code', 'Koordinator Jasa')
                ->whereIn('status', ['APPROVED', 'REJECTED'])
                ->whereHas('instance', function ($query) {
                    $query->where('workflowable_type', TagihanJasa::class);
                })
                ->latest('acted_at')
                ->limit(8)
                ->get()
                ->map(function ($approval) {
                    $tagihan = $approval->instance?->workflowable;
                    $mitra = $tagihan instanceof TagihanJasa ? ($tagihan->mitra ?? $tagihan->mitraLegacy) : null;

                    return [
                        'approval' => $approval,
                        'tagihan' => $tagihan instanceof TagihanJasa ? $tagihan : null,
                        'tagihan_id' => $tagihan instanceof TagihanJasa ? $tagihan->id : null,
                        'nomor_tagihan' => $tagihan instanceof TagihanJasa ? ($tagihan->nomor_tagihan ?? '-') : '-',
                        'mitra_nama' => $mitra?->nama_pihak ?? '-',
                        'acted_at_label' => $approval->acted_at ? $approval->acted_at->format('d/m/Y H:i') : '-',
                        'is_approved' => $approval->status === 'APPROVED',
                        'status_label' => $approval->status === 'APPROVED' ? 'Disetujui' : 'Ditolak',
                        'status_class' => $approval->status === 'APPROVED' ? 'bg-success' : 'bg-danger',
                        'text_class' => $approval->status === 'APPROVED' ? 'text-success' : 'text-danger',
                    ];
                })
                ->filter(fn ($row) => $row['tagihan'])
                ->values();

            $koordinatorStats = [
                'menunggu_verifikasi' => $pendingVerifikasiTagihans->count(),
                'sudah_diverifikasi' => $verifiedByKoordinatorRows->count(),
                'total_tagihan' => TagihanJasa::count(),
                'terbit' => TagihanJasa::where('status', 'PUBLISHED')->count(),
                'lunas' => TagihanJasa::where(function ($query) {
                    $query->where('status', 'LUNAS')
                        ->orWhere('status_pembayaran', 'lunas');
                })->count(),
                'jatuh_tempo' => TagihanJasa::whereNotNull('tanggal_jatuh_tempo')
                    ->whereDate('tanggal_jatuh_tempo', '<=', now()->toDateString())
                    ->whereNotIn('status', ['LUNAS'])
                    ->where('status_pembayaran', '!=', 'lunas')
                    ->count(),
            ];
        }

        // ========================
        // Chart datasets
        // ========================
        // 1) Tagihan per bulan (12 bulan terakhir): jumlah & nominal
        $start = now()->copy()->startOfMonth()->subMonths(11);
        $monthlyRaw = TagihanJasa::query()
            ->select(
                DB::raw("DATE_FORMAT(tanggal_tagihan, '%Y-%m') as ym"),
                DB::raw('COUNT(*) as jml'),
                DB::raw('SUM(total_tagihan) as nominal')
            )
            ->whereNotNull('tanggal_tagihan')
            ->where('tanggal_tagihan', '>=', $start->toDateString())
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $bulanLabels = [];
        $bulanJumlah = [];
        $bulanNominal = [];
        for ($i = 0; $i < 12; $i++) {
            $cur = $start->copy()->addMonths($i);
            $key = $cur->format('Y-m');
            $bulanLabels[] = $cur->translatedFormat('M Y');
            $row = $monthlyRaw->get($key);
            $bulanJumlah[] = (int) ($row->jml ?? 0);
            $bulanNominal[] = (float) ($row->nominal ?? 0);
        }
        $chartTagihanBulanan = [
            'labels' => $bulanLabels,
            'jumlah' => $bulanJumlah,
            'nominal' => $bulanNominal,
        ];

        // 2) Status tagihan (donut)
        $chartStatus = [
            'labels' => $tagihanByStatus->pluck('status')->map(fn ($s) => str_replace('_', ' ', $s))->toArray(),
            'data' => $tagihanByStatus->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];

        // 3) Top mitra by nominal (6 teratas)
        $topMitraRows = TagihanJasa::query()
            ->select('mitra_jasa_id', DB::raw('SUM(total_tagihan) as nominal'))
            ->whereNotNull('mitra_jasa_id')
            ->groupBy('mitra_jasa_id')
            ->orderByDesc('nominal')
            ->limit(6)
            ->with('mitra:id,nama_mitra')
            ->get();
        $chartTopMitra = [
            'labels' => $topMitraRows->map(fn ($r) => $r->mitra->nama_mitra ?? '-')->toArray(),
            'data' => $topMitraRows->map(fn ($r) => (float) $r->nominal)->toArray(),
        ];

        // 4) Distribusi mitra: aktif/non-aktif/tanpa akun
        $chartMitra = [
            'labels' => ['Aktif', 'Non-Aktif', 'Tanpa Akun'],
            'data' => [
                $stats['mitra_aktif'],
                max(0, $stats['mitra_total'] - $stats['mitra_aktif']),
                $stats['mitra_tanpa_akun'],
            ],
        ];

        // 5) Persentase tagihan lunas (gauge)
        $totalTagihan = TagihanJasa::count();
        $persentaseLunas = $totalTagihan > 0
            ? round(($stats['tagihan_lunas'] / $totalTagihan) * 100, 1)
            : 0;

        // 6) Calendar: tanggal jatuh tempo + tanggal tagihan dalam bulan ini
        $calMonth = now()->month;
        $calYear = now()->year;
        $calRange = [
            Carbon::create($calYear, $calMonth, 1)->startOfMonth()->toDateString(),
            Carbon::create($calYear, $calMonth, 1)->endOfMonth()->toDateString(),
        ];

        $eventsRaw = TagihanJasa::query()
            ->select('id', 'nomor_tagihan', 'tanggal_tagihan', 'tanggal_jatuh_tempo', 'status', 'mitra_jasa_id', 'total_tagihan')
            ->where(function ($q) use ($calRange) {
                $q->whereBetween('tanggal_tagihan', $calRange)
                  ->orWhereBetween('tanggal_jatuh_tempo', $calRange);
            })
            ->with('mitra:id,nama_mitra')
            ->limit(150)
            ->get();

        $calendarEvents = [];
        foreach ($eventsRaw as $t) {
            if ($t->tanggal_tagihan && $t->tanggal_tagihan->month === $calMonth && $t->tanggal_tagihan->year === $calYear) {
                $d = (int) $t->tanggal_tagihan->format('j');
                $calendarEvents[$d][] = [
                    'type' => 'terbit',
                    'label' => 'Tagihan terbit',
                    'nomor' => $t->nomor_tagihan,
                    'mitra' => $t->mitra->nama_mitra ?? '-',
                    'status' => $t->status,
                ];
            }
            if ($t->tanggal_jatuh_tempo && $t->tanggal_jatuh_tempo->month === $calMonth && $t->tanggal_jatuh_tempo->year === $calYear) {
                $d = (int) $t->tanggal_jatuh_tempo->format('j');
                $calendarEvents[$d][] = [
                    'type' => $t->status === 'LUNAS' ? 'lunas' : 'jatuh_tempo',
                    'label' => $t->status === 'LUNAS' ? 'Lunas' : 'Jatuh tempo',
                    'nomor' => $t->nomor_tagihan,
                    'mitra' => $t->mitra->nama_mitra ?? '-',
                    'status' => $t->status,
                ];
            }
        }

        $calendar = [
            'month' => $calMonth,
            'year' => $calYear,
            'monthLabel' => Carbon::create($calYear, $calMonth, 1)->translatedFormat('F Y'),
            'firstWeekday' => (int) Carbon::create($calYear, $calMonth, 1)->dayOfWeek, // 0=Sun
            'daysInMonth' => Carbon::create($calYear, $calMonth, 1)->daysInMonth,
            'today' => now()->day === 1 || now()->month === $calMonth ? (int) now()->day : null,
            'events' => $calendarEvents,
        ];

        $dashboardView = $isKoordinatorJasa ? 'koordinator_jasa.dashboard' : 'super_admin_jasa.dashboard';

        return view($dashboardView, compact(
            'stats',
            'mitraTanpaLayanan',
            'adminTanpaLayanan',
            'tagihanByStatus',
            'chartTagihanBulanan',
            'chartStatus',
            'chartTopMitra',
            'chartMitra',
            'persentaseLunas',
            'calendar',
            'isKoordinatorJasa',
            'pendingVerifikasiTagihans',
            'verifiedByKoordinatorRows',
            'recentTagihans',
            'koordinatorStats'
        ));
    }
}
