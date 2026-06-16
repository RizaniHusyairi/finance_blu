<?php

namespace App\Http\Controllers;

use App\Models\MitraJasa;
use App\Models\PemakaianGarbarata;
use App\Models\PengajuanPenagihanGarbarata;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengajuanPenagihanGarbarataController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $user = Auth::user();
        $baseQuery = PengajuanPenagihanGarbarata::query();

        $now = now();
        $statsCurrent = $this->computePeriodStats(clone $baseQuery, $now);
        $statsPrev = $this->computePeriodStats(clone $baseQuery, $now->copy()->subMonth());

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $trend[] = [
                'label' => $m->translatedFormat('M'),
                'nominal' => (float) $this->periodNominal(clone $baseQuery, $m),
            ];
        }

        $query = (clone $baseQuery)->with(['mitra', 'creator', 'reviewer', 'tagihan'])
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($mitraId = $request->input('mitra_jasa_id')) {
            $query->where('mitra_jasa_id', $mitraId);
        }
        if ($tahun = $request->input('periode_tahun')) {
            $query->where('periode_tahun', $tahun);
        }
        if ($bulan = $request->input('periode_bulan')) {
            $query->where('periode_bulan', $bulan);
        }

        $items = $query->paginate(15)->withQueryString();

        $nominalMap = DB::table('pemakaian_garbarata')
            ->whereIn('pengajuan_penagihan_garbarata_id', $items->pluck('id'))
            ->select(
                'pengajuan_penagihan_garbarata_id',
                DB::raw('SUM(tarif_garbarata * jumlah_rentang) as nominal'),
                DB::raw('COUNT(*) as total_batch')
            )
            ->groupBy('pengajuan_penagihan_garbarata_id')
            ->get()
            ->keyBy('pengajuan_penagihan_garbarata_id');

        $detailData = $items->mapWithKeys(function ($p) use ($nominalMap) {
            $row = $nominalMap[$p->id] ?? null;
            return [$p->id => [
                'id' => $p->id,
                'mitra_nama' => $p->mitra?->nama_mitra,
                'mitra_kode' => $p->mitra?->kode_mitra,
                'periode_label' => $p->periode_label,
                'periode_tahun' => (int) $p->periode_tahun,
                'periode_bulan' => (int) $p->periode_bulan,
                'status' => $p->status,
                'status_label' => $p->status_label,
                'status_badge' => $p->status_badge,
                'created_at' => optional($p->created_at)->format('d M Y H:i'),
                'created_by' => $p->creator?->name,
                'reviewed_at' => optional($p->reviewed_at)?->format('d M Y H:i'),
                'reviewer' => $p->reviewer?->name,
                'tagihan_id' => $p->tagihan_jasa_id,
                'tagihan_nomor' => $p->tagihan?->nomor_tagihan,
                'tagihan_at' => optional($p->tagihan?->created_at)?->format('d M Y H:i'),
                'jumlah_pemakaian' => (int) $p->jumlah_pemakaian,
                'total_rentang' => (int) $p->total_rentang,
                'total_batch' => (int) ($row->total_batch ?? $p->jumlah_pemakaian),
                'nominal' => (float) ($row->nominal ?? 0),
                'catatan_amc' => $p->catatan_amc,
                'catatan_admin' => $p->catatan_admin,
                'detail_url' => route('pengajuan-penagihan-garbarata.show', $p),
                'can_review' => $this->canReview() && $p->status === PengajuanPenagihanGarbarata::STATUS_DIAJUKAN,
                'can_cancel' => $this->canCancel($p),
            ]];
        })->all();

        return view('pengajuan_penagihan_garbarata.index', [
            'items' => $items,
            'detailData' => $detailData,
            'filters' => $request->only(['status', 'mitra_jasa_id', 'periode_tahun', 'periode_bulan']),
            'mitraOptions' => MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'statusOpt' => PengajuanPenagihanGarbarata::STATUS_LABEL,
            'bulanOpt' => PengajuanPenagihanGarbarata::BULAN_LABEL,
            'stats' => [
                'total' => $statsCurrent['total'],
                'menunggu' => $statsCurrent['menunggu'],
                'siap' => $statsCurrent['siap'],
                'nominal' => $statsCurrent['nominal'],
                'delta_total' => $this->deltaPercent($statsCurrent['total'], $statsPrev['total']),
                'delta_menunggu' => $this->deltaPercent($statsCurrent['menunggu'], $statsPrev['menunggu']),
                'delta_siap' => $this->deltaPercent($statsCurrent['siap'], $statsPrev['siap']),
                'delta_nominal' => $this->deltaPercent($statsCurrent['nominal'], $statsPrev['nominal']),
            ],
            'trend' => $trend,
        ]);
    }

    private function computePeriodStats($query, $date): array
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
        $scoped = (clone $query)->whereBetween('created_at', [$start, $end]);

        $total = (clone $scoped)->count();
        $menunggu = (clone $scoped)->where('status', PengajuanPenagihanGarbarata::STATUS_DIAJUKAN)->count();
        $siap = (clone $scoped)
            ->where('status', PengajuanPenagihanGarbarata::STATUS_DISETUJUI)
            ->whereNull('tagihan_jasa_id')
            ->count();

        $ids = (clone $scoped)->pluck('id');
        $nominal = $ids->isEmpty() ? 0 : (float) DB::table('pemakaian_garbarata')
            ->whereIn('pengajuan_penagihan_garbarata_id', $ids)
            ->sum(DB::raw('tarif_garbarata * jumlah_rentang'));

        return compact('total', 'menunggu', 'siap', 'nominal');
    }

    private function periodNominal($query, $date): float
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
        $ids = (clone $query)->whereBetween('created_at', [$start, $end])->pluck('id');
        if ($ids->isEmpty()) {
            return 0.0;
        }
        return (float) DB::table('pemakaian_garbarata')
            ->whereIn('pengajuan_penagihan_garbarata_id', $ids)
            ->sum(DB::raw('tarif_garbarata * jumlah_rentang'));
    }

    private function deltaPercent($current, $previous): ?float
    {
        $current = (float) $current;
        $previous = (float) $previous;
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function create(Request $request)
    {
        abort_unless($this->canCreate(), 403);

        $mitraId = (int) $request->input('mitra_jasa_id');
        $periode = trim((string) $request->input('periode_bulan'));
        $eligibleRows = collect();
        $tahun = null;
        $bulan = null;
        $periodeError = null;

        if ($periode !== '') {
            if (preg_match('/^(\d{4})-(\d{1,2})$/', $periode, $m)) {
                $tahun = (int) $m[1];
                $bulan = (int) $m[2];
            } elseif (preg_match('/^\d{1,2}$/', $periode)) {
                $bulan = (int) $periode;
                $tahun = (int) now()->year;
            } else {
                $periodeError = 'Format periode bulan tidak dikenal. Gunakan picker bulan atau ketik formatnya seperti 2026-06.';
            }

            if ($bulan !== null && ($bulan < 1 || $bulan > 12)) {
                $periodeError = 'Bulan harus antara 1 sampai 12.';
                $tahun = null;
                $bulan = null;
            }
        }

        if ($mitraId && $tahun && $bulan) {
            $eligibleRows = $this->eligibleRowsQuery($mitraId, $tahun, $bulan)
                ->with(['layanan', 'permohonan'])
                ->orderBy('tanggal')
                ->orderBy('docking_at')
                ->get();
        }

        return view('pengajuan_penagihan_garbarata.form', [
            'item' => new PengajuanPenagihanGarbarata(),
            'mitraOptions' => MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'bulanOpt' => PengajuanPenagihanGarbarata::BULAN_LABEL,
            'selectedMitraId' => $mitraId,
            'selectedPeriode' => $tahun && $bulan ? sprintf('%04d-%02d', $tahun, $bulan) : $periode,
            'selectedTahun' => $tahun,
            'selectedBulan' => $bulan,
            'eligibleRows' => $eligibleRows,
            'periodeError' => $periodeError,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreate(), 403);

        $validated = $request->validate([
            'mitra_jasa_id' => ['required', 'exists:mitra_jasa,id'],
            'periode_tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'periode_bulan' => ['required', 'integer', 'between:1,12'],
            'catatan_amc' => ['nullable', 'string', 'max:2000'],
            'pemakaian_ids' => ['required', 'array', 'min:1'],
            'pemakaian_ids.*' => ['integer'],
        ]);

        $hasOverlap = PengajuanPenagihanGarbarata::where('mitra_jasa_id', $validated['mitra_jasa_id'])
            ->where('periode_tahun', $validated['periode_tahun'])
            ->where('periode_bulan', $validated['periode_bulan'])
            ->whereIn('status', [
                PengajuanPenagihanGarbarata::STATUS_DIAJUKAN,
                PengajuanPenagihanGarbarata::STATUS_DISETUJUI,
            ])
            ->whereNull('tagihan_jasa_id')
            ->exists();
        if ($hasOverlap) {
            return back()->withInput()->withErrors([
                'periode_bulan' => 'Masih ada rekap Garbarata yang belum ditagihkan untuk mitra dan periode ini. Selesaikan dulu rekap tersebut.',
            ]);
        }

        $rows = PemakaianGarbarata::whereIn('id', $validated['pemakaian_ids'])
            ->where('mitra_jasa_id', $validated['mitra_jasa_id'])
            ->whereYear('tanggal', $validated['periode_tahun'])
            ->whereMonth('tanggal', $validated['periode_bulan'])
            ->whereIn('status', [PemakaianGarbarata::STATUS_DRAFT, PemakaianGarbarata::STATUS_SIAP])
            ->whereNull('pengajuan_penagihan_garbarata_id')
            ->get();

        if ($rows->isEmpty()) {
            return back()->withInput()->withErrors([
                'pemakaian_ids' => 'Tidak ada pemakaian Garbarata valid yang bisa ditarik untuk periode tersebut.',
            ]);
        }

        $pengajuan = DB::transaction(function () use ($validated, $rows) {
            $pengajuan = PengajuanPenagihanGarbarata::create([
                'mitra_jasa_id' => $validated['mitra_jasa_id'],
                'periode_tahun' => $validated['periode_tahun'],
                'periode_bulan' => $validated['periode_bulan'],
                'jumlah_pemakaian' => $rows->count(),
                'total_rentang' => (int) $rows->sum('jumlah_rentang'),
                'status' => PengajuanPenagihanGarbarata::STATUS_DISETUJUI,
                'catatan_amc' => $validated['catatan_amc'] ?? null,
                'catatan_admin' => 'Rekap Garbarata dikunci oleh Admin Jasa dari data pemakaian AMC.',
                'created_by' => Auth::id(),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            PemakaianGarbarata::whereIn('id', $rows->pluck('id'))->update([
                'status' => PemakaianGarbarata::STATUS_DIAJUKAN,
                'pengajuan_penagihan_garbarata_id' => $pengajuan->id,
            ]);

            return $pengajuan;
        });

        return redirect()->route('tagihan-jasa.create', [
            'amc_garbarata_pengajuan_id' => $pengajuan->id,
        ])->with('success', 'Rekap Garbarata sudah dikunci dan siap dibuat Tagihan Jasa.');
    }

    public function show(PengajuanPenagihanGarbarata $pengajuan_penagihan_garbarata)
    {
        abort_unless($this->canView(), 403);

        $pengajuan_penagihan_garbarata->load([
            'mitra', 'creator', 'reviewer', 'tagihan',
            'pemakaian.layanan', 'pemakaian.permohonan',
        ]);

        $rows = $pengajuan_penagihan_garbarata->pemakaian;
        $totalNominal = $rows->sum(fn ($r) => (float) ($r->tarif_garbarata ?? 0) * (int) $r->jumlah_rentang);

        return view('pengajuan_penagihan_garbarata.show', [
            'item' => $pengajuan_penagihan_garbarata,
            'rows' => $rows,
            'totalNominal' => $totalNominal,
            'canReview' => $this->canReview() && $pengajuan_penagihan_garbarata->status === PengajuanPenagihanGarbarata::STATUS_DIAJUKAN,
            'canCancel' => $this->canCancel($pengajuan_penagihan_garbarata),
        ]);
    }

    public function review(Request $request, PengajuanPenagihanGarbarata $pengajuan_penagihan_garbarata)
    {
        abort_unless($this->canReview() && $pengajuan_penagihan_garbarata->status === PengajuanPenagihanGarbarata::STATUS_DIAJUKAN, 403);

        $validated = $request->validate([
            'keputusan' => ['required', Rule::in(['DISETUJUI', 'DITOLAK'])],
            'catatan_admin' => ['nullable', 'string', 'max:2000', 'required_if:keputusan,DITOLAK'],
        ]);

        DB::transaction(function () use ($pengajuan_penagihan_garbarata, $validated) {
            if ($validated['keputusan'] === 'DISETUJUI') {
                $pengajuan_penagihan_garbarata->update([
                    'status' => PengajuanPenagihanGarbarata::STATUS_DISETUJUI,
                    'catatan_admin' => $validated['catatan_admin'] ?? null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
            } else {
                $pengajuan_penagihan_garbarata->update([
                    'status' => PengajuanPenagihanGarbarata::STATUS_DITOLAK,
                    'catatan_admin' => $validated['catatan_admin'],
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);
                PemakaianGarbarata::where('pengajuan_penagihan_garbarata_id', $pengajuan_penagihan_garbarata->id)
                    ->update([
                        'status' => PemakaianGarbarata::STATUS_DRAFT,
                        'pengajuan_penagihan_garbarata_id' => null,
                    ]);
            }
        });

        $msg = $validated['keputusan'] === 'DISETUJUI'
            ? 'Rekap disetujui. Admin Jasa dapat lanjut membuat Tagihan Jasa.'
            : 'Rekap ditolak. Pemakaian dikembalikan ke status Draft.';

        return redirect()->route('pengajuan-penagihan-garbarata.show', $pengajuan_penagihan_garbarata)
            ->with('success', $msg);
    }

    public function destroy(PengajuanPenagihanGarbarata $pengajuan_penagihan_garbarata)
    {
        abort_unless($this->canCancel($pengajuan_penagihan_garbarata), 403);

        DB::transaction(function () use ($pengajuan_penagihan_garbarata) {
            PemakaianGarbarata::where('pengajuan_penagihan_garbarata_id', $pengajuan_penagihan_garbarata->id)
                ->update([
                    'status' => PemakaianGarbarata::STATUS_DRAFT,
                    'pengajuan_penagihan_garbarata_id' => null,
                ]);
            $pengajuan_penagihan_garbarata->delete();
        });

        return redirect()->route('pengajuan-penagihan-garbarata.index')
            ->with('success', 'Rekap dibatalkan dan pemakaian dikembalikan ke Draft.');
    }

    private function eligibleRowsQuery(int $mitraId, int $tahun, int $bulan)
    {
        return PemakaianGarbarata::where('mitra_jasa_id', $mitraId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->whereIn('status', [PemakaianGarbarata::STATUS_DRAFT, PemakaianGarbarata::STATUS_SIAP])
            ->whereNull('pengajuan_penagihan_garbarata_id');
    }

    private function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa', 'Operator BLU']);
    }

    private function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa']);
    }

    private function canReview(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);
    }

    private function canCancel(PengajuanPenagihanGarbarata $item): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if (! in_array($item->status, [
            PengajuanPenagihanGarbarata::STATUS_DIAJUKAN,
            PengajuanPenagihanGarbarata::STATUS_DISETUJUI,
        ], true)) return false;
        if ($item->tagihan_jasa_id) return false;
        if ($user->hasAnyRole(['Super Admin', 'Super Admin Jasa'])) return true;
        return $user->hasRole('Admin Jasa') && $item->created_by === $user->id;
    }
}
