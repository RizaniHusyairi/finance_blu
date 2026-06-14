<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaKonsesi;
use App\Models\MitraJasaPenjualan;
use App\Models\MitraJasaPjp2u;
use App\Services\AdminJasaDashboardService;
use App\Services\EmailNotificationService;
use App\Services\WhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MonitoringPelaporanController extends Controller
{
    /**
     * Prioritas status laporan: makin tinggi = makin "maju" alurnya.
     */
    private const STATUS_RANK = [
        'ditagihkan' => 5,
        'diverifikasi' => 4,
        'diajukan' => 3,
        'draft' => 2,
        'ditolak' => 1,
    ];

    public function index(Request $request, AdminJasaDashboardService $service)
    {
        $user = Auth::user();

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $jenisFilter = in_array($request->input('jenis'), ['konsesi', 'pjp2u'], true) ? $request->input('jenis') : null;
        $statusFilter = $request->input('status');
        $mitraFilter = $request->input('mitra_jasa_id');
        $layananFilter = $request->input('layanan_jasa_id');

        $canViewAll = $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);
        $allowedLayananIds = $service->getAllowedItemIds($user);

        $rows = $this->buildRows($bulan, $tahun, $jenisFilter, $mitraFilter, $layananFilter, $canViewAll, $allowedLayananIds);

        // Ringkasan dihitung sebelum filter status agar tetap menggambarkan seluruh periode.
        $summary = [
            'total' => $rows->count(),
            'belum' => $rows->where('status', 'belum')->count(),
            'sudah' => $rows->where('status', '!=', 'belum')->count(),
            'diajukan' => $rows->where('status', 'diajukan')->count(),
        ];

        if ($statusFilter) {
            $rows = $statusFilter === 'sudah'
                ? $rows->filter(fn ($r) => $r['status'] !== 'belum')
                : $rows->filter(fn ($r) => $r['status'] === $statusFilter);
        }

        $rows = $rows
            ->sortBy(fn ($r) => [$r['status'] === 'belum' ? 0 : 1, $r['mitra_nama'], $r['layanan_nama']])
            ->values();

        return view('jasa.monitoring_pelaporan.index', [
            'items' => $this->paginate($rows, 20),
            'summary' => $summary,
            'filters' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'jenis' => $jenisFilter,
                'status' => $statusFilter,
                'mitra_jasa_id' => $mitraFilter,
                'layanan_jasa_id' => $layananFilter,
            ],
            'filterOptions' => $this->filterOptions($canViewAll, $allowedLayananIds),
        ]);
    }

    /**
     * Export daftar pelaporan (mengikuti filter aktif) ke PDF / Excel.
     */
    public function export(Request $request, AdminJasaDashboardService $service, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $user = Auth::user();
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $jenisFilter = in_array($request->input('jenis'), ['konsesi', 'pjp2u'], true) ? $request->input('jenis') : null;
        $statusFilter = $request->input('status');
        $mitraFilter = $request->input('mitra_jasa_id');
        $layananFilter = $request->input('layanan_jasa_id');

        $canViewAll = $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);
        $allowedLayananIds = $service->getAllowedItemIds($user);

        $rows = $this->buildRows($bulan, $tahun, $jenisFilter, $mitraFilter, $layananFilter, $canViewAll, $allowedLayananIds);

        $summary = [
            'total' => $rows->count(),
            'belum' => $rows->where('status', 'belum')->count(),
            'sudah' => $rows->where('status', '!=', 'belum')->count(),
            'diajukan' => $rows->where('status', 'diajukan')->count(),
        ];

        if ($statusFilter) {
            $rows = $statusFilter === 'sudah'
                ? $rows->filter(fn ($r) => $r['status'] !== 'belum')
                : $rows->filter(fn ($r) => $r['status'] === $statusFilter);
        }
        $rows = $rows->sortBy(fn ($r) => [$r['status'] === 'belum' ? 0 : 1, $r['mitra_nama'], $r['layanan_nama']])->values();

        $payload = [
            'rows' => $rows,
            'summary' => $summary,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'generatedAt' => now(),
        ];

        $filename = 'monitoring-pelaporan-' . $tahun . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) . '-' . now()->format('Ymd-His');

        if ($format === 'pdf') {
            return Pdf::loadView('jasa.monitoring_pelaporan.export', $payload)
                ->setPaper('a4', 'landscape')
                ->download($filename . '.pdf');
        }

        return response("\xEF\xBB\xBF" . view('jasa.monitoring_pelaporan.export', $payload)->render(), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'max-age=0, must-revalidate',
        ]);
    }

    /**
     * Kirim pengingat ke satu mitra untuk satu jenis laporan.
     */
    public function remind(Request $request, WhatsappService $whatsapp, EmailNotificationService $email)
    {
        $validated = $request->validate([
            'mitra_jasa_id' => ['required', 'exists:mitra_jasa,id'],
            'jenis' => ['required', 'in:konsesi,pjp2u'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2000'],
            'layanan_jasa_id' => ['required', 'exists:layanan_jasas,id'],
        ]);

        $this->authorizeLayanan((int) $validated['layanan_jasa_id']);

        $mitra = MitraJasa::findOrFail($validated['mitra_jasa_id']);
        $sent = $this->sendReminder($mitra, [$validated['jenis']], (int) $validated['bulan'], (int) $validated['tahun'], $whatsapp, $email);

        return $sent
            ? back()->with('success', "Pengingat pelaporan dikirim ke {$mitra->nama_mitra}.")
            : back()->with('error', "Mitra {$mitra->nama_mitra} belum punya nomor WhatsApp maupun email.");
    }

    /**
     * Kirim pengingat ke semua mitra yang belum lapor pada periode & filter saat ini.
     */
    public function remindAll(Request $request, AdminJasaDashboardService $service, WhatsappService $whatsapp, EmailNotificationService $email)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2000'],
            'jenis' => ['nullable', 'in:konsesi,pjp2u'],
            'mitra_jasa_id' => ['nullable', 'exists:mitra_jasa,id'],
            'layanan_jasa_id' => ['nullable', 'exists:layanan_jasas,id'],
        ]);

        $user = Auth::user();
        $canViewAll = $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);
        $allowedLayananIds = $service->getAllowedItemIds($user);

        $belum = $this->buildRows(
            (int) $validated['bulan'],
            (int) $validated['tahun'],
            $validated['jenis'] ?? null,
            $validated['mitra_jasa_id'] ?? null,
            $validated['layanan_jasa_id'] ?? null,
            $canViewAll,
            $allowedLayananIds
        )->where('status', 'belum');

        if ($belum->isEmpty()) {
            return back()->with('error', 'Tidak ada mitra yang berstatus belum lapor pada periode/filter ini.');
        }

        // Satu mitra cukup dikirimi sekali, menggabungkan jenis yang belum dilaporkan.
        $sentCount = 0;
        $skipped = 0;
        foreach ($belum->groupBy('mitra_id') as $mitraId => $group) {
            $mitra = MitraJasa::find($mitraId);
            if (! $mitra) {
                continue;
            }

            $jenisList = $group->pluck('jenis')->unique()->values()->all();
            $this->sendReminder($mitra, $jenisList, (int) $validated['bulan'], (int) $validated['tahun'], $whatsapp, $email)
                ? $sentCount++
                : $skipped++;
        }

        $pesan = "Pengingat dikirim ke {$sentCount} mitra.";
        if ($skipped > 0) {
            $pesan .= " {$skipped} mitra dilewati (tanpa nomor WA/email).";
        }

        return back()->with('success', $pesan);
    }

    /* ───────────────────────── Helpers ───────────────────────── */

    private function buildRows(int $bulan, int $tahun, ?string $jenisFilter, $mitraFilter, $layananFilter, bool $canViewAll, array $allowedLayananIds): Collection
    {
        $periodStart = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $rows = collect();

        if ($jenisFilter !== 'pjp2u') {
            $rows = $rows->concat(
                MitraJasaKonsesi::query()
                    ->with(['mitraJasa', 'layananJasa'])
                    ->where('status_aktif', true)
                    ->where(fn ($q) => $q->whereNull('tanggal_mulai')->orWhereDate('tanggal_mulai', '<=', $periodEnd))
                    ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $periodStart))
                    ->when(! $canViewAll, fn ($q) => $q->whereIn('layanan_jasa_id', $allowedLayananIds))
                    ->when($mitraFilter, fn ($q) => $q->where('mitra_jasa_id', $mitraFilter))
                    ->when($layananFilter, fn ($q) => $q->where('layanan_jasa_id', $layananFilter))
                    ->get()
                    ->map(fn ($k) => $this->toRow($k, 'konsesi'))
            );
        }

        if ($jenisFilter !== 'konsesi') {
            $rows = $rows->concat(
                MitraJasaPjp2u::query()
                    ->with(['mitraJasa', 'layananJasa'])
                    ->where('status_aktif', true)
                    ->where(fn ($q) => $q->whereNull('tanggal_mulai')->orWhereDate('tanggal_mulai', '<=', $periodEnd))
                    ->where(fn ($q) => $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $periodStart))
                    ->when(! $canViewAll, fn ($q) => $q->whereIn('layanan_jasa_id', $allowedLayananIds))
                    ->when($mitraFilter, fn ($q) => $q->where('mitra_jasa_id', $mitraFilter))
                    ->when($layananFilter, fn ($q) => $q->where('layanan_jasa_id', $layananFilter))
                    ->get()
                    ->map(fn ($p) => $this->toRow($p, 'pjp2u'))
            );
        }

        $reportMap = MitraJasaPenjualan::query()
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->when($mitraFilter, fn ($q) => $q->where('mitra_jasa_id', $mitraFilter))
            ->when($layananFilter, fn ($q) => $q->where('layanan_jasa_id', $layananFilter))
            ->get()
            ->groupBy(fn ($p) => $p->mitra_jasa_id . '|' . $p->layanan_jasa_id);

        return $rows->map(function (array $row) use ($reportMap) {
            $reports = $reportMap->get($row['mitra_id'] . '|' . $row['layanan_id']);

            if (! $reports || $reports->isEmpty()) {
                $row['status'] = 'belum';
                $row['report_id'] = null;
            } else {
                $best = $reports->sortByDesc(fn ($p) => self::STATUS_RANK[$p->status] ?? 0)->first();
                $row['status'] = $best->status;
                $row['report_id'] = $best->id;
            }

            return $row;
        });
    }

    private function toRow($assignment, string $jenis): array
    {
        return [
            'mitra_id' => (int) $assignment->mitra_jasa_id,
            'mitra_nama' => $assignment->mitraJasa?->nama_mitra ?? '-',
            'layanan_id' => (int) $assignment->layanan_jasa_id,
            'layanan_nama' => $assignment->layananJasa?->nama_layanan ?? '-',
            'jenis' => $jenis,
        ];
    }

    private function authorizeLayanan(int $layananId): void
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            return;
        }

        $allowed = app(AdminJasaDashboardService::class)->getAllowedItemIds($user);
        abort_unless(in_array($layananId, $allowed, true), 403, 'Layanan ini di luar kewenangan Anda.');
    }

    private function sendReminder(MitraJasa $mitra, array $jenisList, int $bulan, int $tahun, WhatsappService $whatsapp, EmailNotificationService $email): bool
    {
        $periode = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');
        $jenisText = collect($jenisList)
            ->map(fn ($j) => $j === 'konsesi' ? 'omzet konsesi' : 'jumlah penumpang (PAX) PJP2U')
            ->unique()
            ->implode(' dan ');
        $portal = url('/login');

        $message = "*PENGINGAT PELAPORAN*\n\n"
            . 'Yth. ' . ($mitra->nama_mitra ?? 'Mitra') . ",\n\n"
            . "Mohon segera menyampaikan laporan {$jenisText} untuk periode *{$periode}* melalui portal mitra.\n\n"
            . "Portal: {$portal}\n\n"
            . "Terima kasih.\n_SIKEREN-BLU_";

        $sentAny = false;

        if (filled($mitra->no_telepon)) {
            $whatsapp->sendMessage($mitra->no_telepon, $message);
            $sentAny = true;
        }

        if (filled($mitra->email)) {
            $email->sendNotification(
                $mitra->email,
                "Pengingat Pelaporan {$jenisText} Periode {$periode}",
                $message,
                $mitra,
                'send_report_reminder_email'
            );
            $sentAny = true;
        }

        return $sentAny;
    }

    private function paginate(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    private function filterOptions(bool $canViewAll, array $allowedLayananIds): array
    {
        $konsesi = MitraJasaKonsesi::query()->where('status_aktif', true)
            ->when(! $canViewAll, fn ($q) => $q->whereIn('layanan_jasa_id', $allowedLayananIds));
        $pjp2u = MitraJasaPjp2u::query()->where('status_aktif', true)
            ->when(! $canViewAll, fn ($q) => $q->whereIn('layanan_jasa_id', $allowedLayananIds));

        $mitraIds = (clone $konsesi)->pluck('mitra_jasa_id')
            ->merge((clone $pjp2u)->pluck('mitra_jasa_id'))->unique()->values();
        $layananIds = (clone $konsesi)->pluck('layanan_jasa_id')
            ->merge((clone $pjp2u)->pluck('layanan_jasa_id'))->unique()->values();

        return [
            'mitras' => MitraJasa::whereIn('id', $mitraIds)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'layanans' => LayananJasa::whereIn('id', $layananIds)->orderBy('nama_layanan')->get(['id', 'nama_layanan']),
        ];
    }
}
