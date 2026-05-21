<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaPenjualan;
use App\Models\TagihanJasa;
use App\Services\MitraJasaKonsesiService;
use Illuminate\Http\Request;
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
        $user = auth()->user();
        if (! $user?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa', 'Admin Jasa'])) {
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

        $penjualans = $query->paginate(15)->withQueryString();
        $mitras = MitraJasa::query()->orderBy('nama_mitra')->get(['id', 'nama_mitra']);

        return view('super_admin_jasa.mitra.pjp2u-index', compact('penjualans', 'mitras', 'filters'));
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

        // Guard: verifikasi hanya bisa dilakukan setelah berganti bulan
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
}
