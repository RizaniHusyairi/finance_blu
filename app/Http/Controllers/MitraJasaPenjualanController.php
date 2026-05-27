<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaPenjualan;
use App\Models\TagihanJasa;
use App\Services\MitraJasaKonsesiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MitraJasaPenjualanController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'mitra_jasa_id' => ['nullable', 'integer', 'exists:mitra_jasa,id'],
            'status' => ['nullable', 'in:draft,diajukan,diverifikasi,ditolak,ditagihkan'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $pjp2uLayananIds = LayananJasa::all()->filter(fn($l) => $l->isPjp2u())->pluck('id');
        
        $query = MitraJasaPenjualan::with(['mitraJasa', 'konsesi', 'layananJasa', 'tagihanJasa', 'sourceTagihanJasa'])
            ->whereNotIn('layanan_jasa_id', $pjp2uLayananIds)
            ->latest('periode_mulai')
            ->latest('id');

        $user = auth()->user();
        if (! $user?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            $allowedIds = auth()->user()
                ->layananJasaDikelolaAktif()
                ->where('layanan_jasas.is_active', true)
                ->pluck('layanan_jasas.id')
                ->all();

            if ($user?->hasRole('Admin Jasa')) {
                $query->where(function ($scope) use ($allowedIds, $user) {
                    if ($this->hasSourceTagihanColumn()) {
                        $scope->whereHas('sourceTagihanJasa', fn ($tagihanQuery) => $tagihanQuery->where('created_by', $user->id))
                            ->orWhere(function ($fallback) use ($allowedIds) {
                                $fallback->whereNull('source_tagihan_jasa_id')
                                    ->whereIn('layanan_jasa_id', $allowedIds);
                            });
                    } else {
                        $scope->whereIn('layanan_jasa_id', $allowedIds);
                    }
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $query
            ->when($filters['mitra_jasa_id'] ?? null, fn ($subQuery, $mitraId) => $subQuery->where('mitra_jasa_id', $mitraId))
            ->when($filters['status'] ?? null, fn ($subQuery, $status) => $subQuery->where('status', $status))
            ->when($filters['bulan'] ?? null, fn ($subQuery, $bulan) => $subQuery->where('bulan', $bulan))
            ->when($filters['tahun'] ?? null, fn ($subQuery, $tahun) => $subQuery->where('tahun', $tahun))
            ->when($filters['q'] ?? null, function ($subQuery, $q) {
                $subQuery->where(function ($nested) use ($q) {
                    $nested->whereHas('mitraJasa', fn ($mitraQuery) => $mitraQuery->where('nama_mitra', 'like', "%{$q}%"))
                        ->orWhereHas('layananJasa', fn ($layananQuery) => $layananQuery->where('nama_layanan', 'like', "%{$q}%"));
                });
            });

        $penjualans = $query->paginate(15)->withQueryString();
        $mitras = MitraJasa::query()->orderBy('nama_mitra')->get(['id', 'nama_mitra']);

        return view('super_admin_jasa.mitra.penjualan-index', compact('penjualans', 'mitras', 'filters'));
    }

    public function indexPjp2u(Request $request)
    {
        $filters = $request->validate([
            'mitra_jasa_id' => ['nullable', 'integer', 'exists:mitra_jasa,id'],
            'status' => ['nullable', 'in:draft,diajukan,diverifikasi,ditolak,ditagihkan'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $pjp2uLayananIds = LayananJasa::all()->filter(fn($l) => $l->isPjp2u())->pluck('id');

        $query = MitraJasaPenjualan::with(['mitraJasa', 'konsesi', 'layananJasa', 'tagihanJasa', 'sourceTagihanJasa'])
            ->whereIn('layanan_jasa_id', $pjp2uLayananIds)
            ->latest('periode_mulai')
            ->latest('id');

        // PJP2U: any Admin Jasa can see all PJP2U reports (shared responsibility)
        if (! $this->canViewPjp2uReports()) {
            $query->whereRaw('1 = 0');
        }

        $query
            ->when($filters['mitra_jasa_id'] ?? null, fn ($subQuery, $mitraId) => $subQuery->where('mitra_jasa_id', $mitraId))
            ->when($filters['status'] ?? null, fn ($subQuery, $status) => $subQuery->where('status', $status))
            ->when($filters['bulan'] ?? null, fn ($subQuery, $bulan) => $subQuery->where('bulan', $bulan))
            ->when($filters['tahun'] ?? null, fn ($subQuery, $tahun) => $subQuery->where('tahun', $tahun))
            ->when($filters['q'] ?? null, function ($subQuery, $q) {
                $subQuery->where(function ($nested) use ($q) {
                    $nested->whereHas('mitraJasa', fn ($mitraQuery) => $mitraQuery->where('nama_mitra', 'like', "%{$q}%"))
                        ->orWhereHas('layananJasa', fn ($layananQuery) => $layananQuery->where('nama_layanan', 'like', "%{$q}%"));
                });
            });

        $rekapPjp2u = $this->paginateCollection(
            $this->buildPjp2uRekapRows($query->get()),
            $request
        );
        $mitras = MitraJasa::query()->orderBy('nama_mitra')->get(['id', 'nama_mitra']);

        return view('super_admin_jasa.mitra.pjp2u-index', compact('rekapPjp2u', 'mitras', 'filters'));
    }

    private function buildPjp2uRekapRows(Collection $penjualans): Collection
    {
        return $penjualans
            ->groupBy(fn (MitraJasaPenjualan $penjualan) => implode('|', [
                $penjualan->mitra_jasa_id,
                $penjualan->layanan_jasa_id,
                $penjualan->tahun,
                $penjualan->bulan,
            ]))
            ->map(function (Collection $items) {
                /** @var MitraJasaPenjualan $latestReport */
                $latestReport = $items->first();
                $statusCounts = $items->groupBy('status')->map->count();
                [$statusLabel, $statusClass] = $this->resolvePjp2uRekapStatus($statusCounts);

                $createableReport = $items->first(fn (MitraJasaPenjualan $penjualan) => $penjualan->status === 'diverifikasi'
                    && ! $penjualan->tagihan_jasa_id
                    && $penjualan->layanan_jasa_id
                    && $penjualan->can_create_tagihan);

                $firstTagihan = $items
                    ->pluck('tagihanJasa')
                    ->filter()
                    ->first();

                return (object) [
                    'mitra_jasa_id' => $latestReport->mitra_jasa_id,
                    'layanan_jasa_id' => $latestReport->layanan_jasa_id,
                    'mitra' => $latestReport->mitraJasa,
                    'layanan' => $latestReport->layananJasa,
                    'bulan' => (int) $latestReport->bulan,
                    'tahun' => (int) $latestReport->tahun,
                    'periode_mulai' => $items->pluck('periode_mulai')->filter()->sort()->first(),
                    'periode_selesai' => $items->pluck('periode_selesai')->filter()->sortDesc()->first(),
                    'jumlah_laporan' => $items->count(),
                    'total_pax' => $items->sum(fn (MitraJasaPenjualan $penjualan) => (float) $penjualan->total_omzet),
                    'nilai_konsesi' => $items->sum(fn (MitraJasaPenjualan $penjualan) => (float) $penjualan->nilai_konsesi),
                    'nilai_tagihan' => $items->sum(fn (MitraJasaPenjualan $penjualan) => (float) $penjualan->nilai_tagihan),
                    'status_counts' => $statusCounts,
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                    'latest_report' => $latestReport,
                    'createable_report' => $createableReport,
                    'needs_verification_count' => $items->filter(fn (MitraJasaPenjualan $penjualan) => $penjualan->status === 'diajukan' && $penjualan->can_be_verified)->count(),
                    'waiting_verification_count' => $items->filter(fn (MitraJasaPenjualan $penjualan) => $penjualan->status === 'diajukan' && ! $penjualan->can_be_verified)->count(),
                    'can_create_tagihan_count' => $items->filter(fn (MitraJasaPenjualan $penjualan) => $penjualan->status === 'diverifikasi'
                        && ! $penjualan->tagihan_jasa_id
                        && $penjualan->layanan_jasa_id
                        && $penjualan->can_create_tagihan)->count(),
                    'tagihan_count' => $items->pluck('tagihan_jasa_id')->filter()->unique()->count(),
                    'first_tagihan' => $firstTagihan,
                    'file_count' => $items->filter(fn (MitraJasaPenjualan $penjualan) => filled($penjualan->file_laporan))->count(),
                ];
            })
            ->sortByDesc(fn ($row) => sprintf('%04d%02d%010d%010d', $row->tahun, $row->bulan, $row->mitra_jasa_id, $row->layanan_jasa_id))
            ->values();
    }

    private function resolvePjp2uRekapStatus(Collection $statusCounts): array
    {
        if (($statusCounts->get('diajukan') ?? 0) > 0) {
            return ['Menunggu Verifikasi', 'bg-warning text-dark'];
        }

        if (($statusCounts->get('ditolak') ?? 0) > 0) {
            return ['Ada Ditolak', 'bg-danger'];
        }

        if (($statusCounts->get('diverifikasi') ?? 0) > 0) {
            return ['Siap Tagih', 'bg-success'];
        }

        if (($statusCounts->get('ditagihkan') ?? 0) > 0) {
            return [$statusCounts->count() === 1 ? 'Ditagihkan' : 'Sebagian Ditagihkan', 'bg-primary'];
        }

        return ['Draft', 'bg-secondary'];
    }

    private function paginateCollection(Collection $items, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    public function showPjp2uRekap(Request $request, MitraJasa $mitra, LayananJasa $layanan, int $tahun, int $bulan)
    {
        abort_unless($this->canViewPjp2uReports(), 403);
        abort_unless($layanan->isPjp2u(), 404);
        abort_unless($bulan >= 1 && $bulan <= 12 && $tahun >= 2020 && $tahun <= 2100, 404);

        $baseQuery = MitraJasaPenjualan::with(['mitraJasa', 'layananJasa', 'tagihanJasa', 'sourceTagihanJasa'])
            ->where('mitra_jasa_id', $mitra->id)
            ->where('layanan_jasa_id', $layanan->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan);

        $summaryItems = (clone $baseQuery)
            ->orderByDesc('periode_mulai')
            ->orderByDesc('id')
            ->get();

        $rekap = $this->buildPjp2uRekapRows($summaryItems)->first() ?: (object) [
            'jumlah_laporan' => 0,
            'total_pax' => 0,
            'nilai_konsesi' => 0,
            'nilai_tagihan' => 0,
            'status_counts' => collect(),
            'status_label' => 'Kosong',
            'status_class' => 'bg-secondary',
            'tagihan_count' => 0,
            'file_count' => 0,
        ];

        $penjualans = (clone $baseQuery)
            ->orderBy('periode_mulai')
            ->orderBy('id')
            ->paginate(31)
            ->withQueryString();

        return view('super_admin_jasa.mitra.pjp2u-rekap-show', compact(
            'mitra',
            'layanan',
            'tahun',
            'bulan',
            'rekap',
            'penjualans'
        ));
    }

    public function show(MitraJasa $mitra, MitraJasaPenjualan $penjualan)
    {
        $this->ensureOwnedByMitra($mitra, $penjualan);

        $penjualan->load([
            'mitraJasa',
            'konsesi',
            'layananJasa.parent.parent.parent.parent.parent',
            'kontrakMitraJasa',
            'tagihanJasa',
            'verifiedByUser',
            'createdByUser',
            'details' => fn ($q) => $q->orderBy('periode_mulai'),
        ]);

        return view('super_admin_jasa.mitra.penjualan-show', compact('mitra', 'penjualan'));
    }

    public function create(MitraJasa $mitra)
    {
        $this->abortIfAdminJasaCreatesLaporan();

        return view('super_admin_jasa.mitra.penjualan-form', [
            'mitra' => $mitra,
            'penjualan' => new MitraJasaPenjualan([
                'periode_tipe' => 'harian',
                'periode_mulai' => now()->startOfMonth()->toDateString(),
                'periode_selesai' => now()->endOfMonth()->toDateString(),
                'status' => 'draft',
            ]),
            'konsesiContext' => $this->resolveKonsesiContext($mitra),
        ]);
    }

    public function store(Request $request, MitraJasa $mitra, MitraJasaKonsesiService $service)
    {
        $this->abortIfAdminJasaCreatesLaporan();

        $validated = $this->validatePenjualan($request, $mitra);
        $konsesiContext = $this->resolveKonsesiContext($mitra, (int) $validated['layanan_jasa_id']);
        if (! $konsesiContext['layanan']) {
            return back()
                ->withInput()
                ->with('error', 'Mitra belum memiliki layanan Konsesi aktif. Aktifkan layanan bertipe Konsesi terlebih dahulu.');
        }

        $hasil = $service->hitungKonsesiLayanan($konsesiContext['layanan'], (float) $validated['total_omzet']);

        if ($request->hasFile('file_laporan')) {
            $validated['file_laporan'] = $request->file('file_laporan')->store('mitra-jasa/penjualan', 'public');
        }

        MitraJasaPenjualan::create(array_merge($validated, [
            'mitra_jasa_id' => $mitra->id,
            'mitra_jasa_konsesi_id' => $konsesiContext['konsesi']?->id,
            'kontrak_mitra_jasa_id' => $konsesiContext['kontrak']?->id,
            'layanan_jasa_id' => $konsesiContext['layanan']->id,
            ...($this->hasSourceTagihanColumn() ? [
                'source_tagihan_jasa_id' => $this->sourceTagihanForLayanan($mitra, (int) $konsesiContext['layanan']->id)?->id,
            ] : []),
            'bulan' => (int) date('m', strtotime($validated['periode_mulai'])),
            'tahun' => (int) date('Y', strtotime($validated['periode_mulai'])),
            'persentase_konsesi' => $hasil['persentase_konsesi'],
            'nilai_konsesi' => $hasil['nilai_konsesi'],
            'nilai_minimum_guarantee' => $hasil['nilai_minimum_guarantee'],
            'nilai_tagihan' => $hasil['nilai_tagihan'],
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Laporan penjualan berhasil disimpan sebagai draft.');
    }

    public function edit(MitraJasa $mitra, MitraJasaPenjualan $penjualan)
    {
        $this->abortIfAdminJasaCreatesLaporan();

        $this->ensureOwnedByMitra($mitra, $penjualan);
        abort_unless(in_array($penjualan->status, ['draft', 'ditolak'], true), 403, 'Laporan hanya dapat diedit saat draft atau ditolak.');

        return view('super_admin_jasa.mitra.penjualan-form', [
            'mitra' => $mitra,
            'penjualan' => $penjualan,
            'konsesiContext' => $this->resolveKonsesiContext($mitra),
        ]);
    }

    public function update(Request $request, MitraJasa $mitra, MitraJasaPenjualan $penjualan, MitraJasaKonsesiService $service)
    {
        $this->abortIfAdminJasaCreatesLaporan();

        $this->ensureOwnedByMitra($mitra, $penjualan);
        abort_unless(in_array($penjualan->status, ['draft', 'ditolak'], true), 403, 'Laporan hanya dapat diedit saat draft atau ditolak.');

        $validated = $this->validatePenjualan($request, $mitra);
        $konsesiContext = $this->resolveKonsesiContext($mitra, (int) $validated['layanan_jasa_id']);
        if (! $konsesiContext['layanan']) {
            return back()
                ->withInput()
                ->with('error', 'Mitra belum memiliki layanan Konsesi aktif. Aktifkan layanan bertipe Konsesi terlebih dahulu.');
        }

        $hasil = $service->hitungKonsesiLayanan($konsesiContext['layanan'], (float) $validated['total_omzet']);

        if ($request->hasFile('file_laporan')) {
            if ($penjualan->file_laporan) {
                Storage::disk('public')->delete($penjualan->file_laporan);
            }
            $validated['file_laporan'] = $request->file('file_laporan')->store('mitra-jasa/penjualan', 'public');
        }

        $penjualan->update(array_merge($validated, [
            'mitra_jasa_konsesi_id' => $konsesiContext['konsesi']?->id,
            'kontrak_mitra_jasa_id' => $konsesiContext['kontrak']?->id,
            'layanan_jasa_id' => $konsesiContext['layanan']->id,
            ...($this->hasSourceTagihanColumn() ? [
                'source_tagihan_jasa_id' => $this->sourceTagihanForLayanan($mitra, (int) $konsesiContext['layanan']->id)?->id,
            ] : []),
            'bulan' => (int) date('m', strtotime($validated['periode_mulai'])),
            'tahun' => (int) date('Y', strtotime($validated['periode_mulai'])),
            'persentase_konsesi' => $hasil['persentase_konsesi'],
            'nilai_konsesi' => $hasil['nilai_konsesi'],
            'nilai_minimum_guarantee' => $hasil['nilai_minimum_guarantee'],
            'nilai_tagihan' => $hasil['nilai_tagihan'],
            'status' => 'draft',
            'updated_by' => auth()->id(),
        ]));

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Laporan penjualan berhasil diperbarui.');
    }

    public function submit(MitraJasa $mitra, MitraJasaPenjualan $penjualan)
    {
        $this->ensureOwnedByMitra($mitra, $penjualan);
        abort_unless(in_array($penjualan->status, ['draft', 'ditolak'], true), 403);

        $penjualan->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan penjualan berhasil diajukan.');
    }

    public function verify(MitraJasa $mitra, MitraJasaPenjualan $penjualan)
    {
        $this->ensureOwnedByMitra($mitra, $penjualan);
        $this->ensureCanVerifyPenjualan($mitra, $penjualan);
        abort_unless($penjualan->status === 'diajukan', 403);

        // Guard: konsesi menunggu bulan berakhir, PJP2U harian langsung bisa diverifikasi.
        if (! $penjualan->can_be_verified) {
            return back()->with('error', 'Laporan belum dapat diverifikasi. Verifikasi hanya dapat dilakukan setelah bulan pelaporan berakhir (berganti bulan).');
        }

        $penjualan->update([
            'status' => 'diverifikasi',
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan penjualan berhasil diverifikasi.');
    }

    public function reject(Request $request, MitraJasa $mitra, MitraJasaPenjualan $penjualan)
    {
        $this->ensureOwnedByMitra($mitra, $penjualan);
        $this->ensureCanVerifyPenjualan($mitra, $penjualan);
        abort_unless($penjualan->status === 'diajukan', 403);

        $validated = $request->validate([
            'catatan_verifikator' => ['required', 'string', 'max:1000'],
        ]);

        $penjualan->update([
            'status' => 'ditolak',
            'catatan_verifikator' => $validated['catatan_verifikator'],
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan penjualan berhasil ditolak dengan catatan.');
    }

    private function validatePenjualan(Request $request, MitraJasa $mitra): array
    {
        return $request->validate([
            'layanan_jasa_id' => ['required', Rule::in($this->tagihanKonsesiLayanans($mitra)->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'periode_tipe' => ['required', Rule::in(['harian', 'mingguan'])],
            'periode_mulai' => ['required', 'date'],
            'periode_selesai' => ['required', 'date', 'after_or_equal:periode_mulai'],
            'total_omzet' => ['required', 'numeric', 'min:0'],
            'total_transaksi' => ['nullable', 'integer', 'min:0'],
            'file_laporan' => [$request->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:pdf,xlsx,xls,csv,jpg,jpeg,png', 'max:5120'],
            'catatan_mitra' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function resolveKonsesiContext(MitraJasa $mitra, ?int $layananId = null): array
    {
        $layanans = $this->tagihanKonsesiLayanans($mitra);
        $konsesi = $mitra->konsesi()
            ->with(['kontrakMitraJasa', 'layananJasa'])
            ->where('status_aktif', true)
            ->when($layananId, fn ($query) => $query->where('layanan_jasa_id', $layananId))
            ->orderByDesc('tanggal_mulai')
            ->first();

        $layanan = $layananId
            ? $layanans->firstWhere('id', $layananId)
            : ($konsesi?->layananJasa ?: $layanans->first());

        $kontrak = $konsesi?->kontrakMitraJasa
            ?: $mitra->kontrak()->orderByDesc('tanggal_kontrak')->orderByDesc('id')->first();

        return [
            'konsesi' => $konsesi,
            'layanan' => $layanan,
            'layanans' => $layanans,
            'kontrak' => $kontrak,
            'persentase' => $layanan?->persentase_konsesi ?? MitraJasaKonsesiService::DEFAULT_PERSENTASE_KONSESI,
        ];
    }

    private function tagihanKonsesiLayanans(MitraJasa $mitra)
    {
        return $mitra->konsesiAktif()
            ->with('layananJasa')
            ->get()
            ->pluck('layananJasa')
            ->filter()
            ->unique('id')
            ->values();
    }

    private function ensureOwnedByMitra(MitraJasa $mitra, MitraJasaPenjualan $penjualan): void
    {
        abort_unless((int) $penjualan->mitra_jasa_id === (int) $mitra->id, 404);
    }

    private function abortIfAdminJasaCreatesLaporan(): void
    {
        $user = auth()->user();

        abort_if(
            $user?->hasRole('Admin Jasa') === true
                && ! $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']),
            403
        );
    }

    private function ensureCanVerifyPenjualan(MitraJasa $mitra, MitraJasaPenjualan $penjualan): void
    {
        $user = auth()->user();

        if ($user?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            return;
        }

        abort_unless($user?->hasRole('Admin Jasa'), 403);

        $sourceTagihan = $this->sourceTagihanForPenjualan($mitra, $penjualan);

        abort_unless($sourceTagihan && (int) $sourceTagihan->created_by === (int) $user->id, 403);
    }

    private function sourceTagihanForPenjualan(MitraJasa $mitra, MitraJasaPenjualan $penjualan): ?TagihanJasa
    {
        if ($this->hasSourceTagihanColumn() && $penjualan->source_tagihan_jasa_id) {
            return TagihanJasa::where('mitra_jasa_id', $mitra->id)->find($penjualan->source_tagihan_jasa_id);
        }

        return $this->sourceTagihanForLayanan($mitra, (int) $penjualan->layanan_jasa_id);
    }

    private function sourceTagihanForLayanan(MitraJasa $mitra, int $layananId): ?TagihanJasa
    {
        return $mitra->tagihanJasas()
            ->whereHas('details', fn ($query) => $query->where('layanan_jasa_id', $layananId))
            ->latest('tanggal_tagihan')
            ->latest('id')
            ->first();
    }

    private function hasSourceTagihanColumn(): bool
    {
        return Schema::hasColumn('mitra_jasa_penjualan', 'source_tagihan_jasa_id');
    }

    private function canViewPjp2uReports(): bool
    {
        return auth()->user()?->hasAnyRole([
            'Super Admin',
            'Super Admin Jasa',
            'Koordinator Jasa',
            'Admin Jasa',
        ]) === true;
    }
}
