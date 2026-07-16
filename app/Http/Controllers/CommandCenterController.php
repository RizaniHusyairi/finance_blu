<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Command Center — pusat audit seluruh aktivitas user, ditampilkan sebagai
 * layar penuh (mode TV display, tanpa scroll). Akses dibatasi role
 * Super Admin lewat middleware route (routes/web.php); dibuka dari tombol
 * di dashboard Super Admin.
 */
class CommandCenterController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'total' => ActivityLog::count(),
            'hari_ini' => ActivityLog::whereDate('created_at', $today)->count(),
            'user_aktif' => ActivityLog::whereDate('created_at', $today)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'login_gagal_7d' => ActivityLog::where('event', 'login_gagal')->where('created_at', '>=', now()->subDays(7))->count(),
            'mutasi_hari_ini' => ActivityLog::whereDate('created_at', $today)->whereIn('event', ['create', 'update', 'delete'])->count(),
            'online' => ActivityLog::where('created_at', '>=', now()->subMinutes(5))->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        // Tren aktivitas 14 hari terakhir (lengkapi hari kosong dengan 0).
        $rawTrend = ActivityLog::where('created_at', '>=', $today->copy()->subDays(13))
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->pluck('jumlah', 'tanggal');

        $trend = collect(CarbonPeriod::create($today->copy()->subDays(13), $today))
            ->map(fn ($d) => [
                'label' => $d->translatedFormat('d M'),
                'jumlah' => (int) ($rawTrend[$d->toDateString()] ?? 0),
            ])->values();

        // Distribusi per jenis event (30 hari).
        $byEvent = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('event, COUNT(*) as jumlah')
            ->groupBy('event')
            ->orderByDesc('jumlah')
            ->get();

        // Top 7 user teraktif (30 hari).
        $topUsers = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MAX(user_name) as user_name, COUNT(*) as jumlah')
            ->groupBy('user_id')
            ->orderByDesc('jumlah')
            ->limit(7)
            ->get();

        return view('command_center.index', compact('stats', 'trend', 'byEvent', 'topUsers'));
    }

    /** Live feed: kembalikan log terbaru setelah id tertentu (dipoll berkala). */
    public function feed(Request $request): JsonResponse
    {
        $afterId = (int) $request->query('after_id', 0);

        $logs = ActivityLog::query()
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->latest('id')
            ->limit($afterId > 0 ? 15 : 30)
            ->get();

        $meta = ActivityLog::eventMeta();

        return response()->json([
            'last_id' => $logs->max('id') ?? $afterId,
            'items' => $logs->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'user' => $log->user_name ?? 'Sistem',
                'role' => $log->user_role,
                'event' => $log->event,
                'label' => $meta[$log->event]['label'] ?? $log->event,
                'color' => $meta[$log->event]['color'] ?? 'secondary',
                'icon' => $meta[$log->event]['icon'] ?? 'bolt',
                'description' => $log->description,
                'modul' => $log->modul,
                'ip' => $log->ip,
                'waktu' => $log->created_at->format('H:i:s'),
                'relatif' => $log->created_at->diffForHumans(),
            ]),
            'stats' => [
                'hari_ini' => ActivityLog::whereDate('created_at', Carbon::today())->count(),
                'online' => ActivityLog::where('created_at', '>=', now()->subMinutes(5))->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            ],
        ]);
    }
}
