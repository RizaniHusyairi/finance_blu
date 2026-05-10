<?php

namespace App\Http\Controllers;

use App\Models\MasterCoa;
use App\Models\Tagihan;
use App\Models\TransaksiPenerimaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class CoaController extends Controller
{
    public function create()
    {
        return view('coas.create');
    }

    public function index(Request $request)
    {
        $baseQuery = MasterCoa::query()->withCount('dipaRevisionItems');

        $coas = (clone $baseQuery)
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = trim((string) $request->string('search'));

                $builder->where(function ($query) use ($search) {
                    $query->where('kode_mak_lengkap', 'like', '%' . $search . '%')
                        ->orWhere('kd_akun', 'like', '%' . $search . '%')
                        ->orWhere('nama_akun', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('jenis_akun'), function ($builder) use ($request) {
                $builder->where('jenis_akun', $request->string('jenis_akun'));
            })
            ->when($request->filled('status_aktif'), function ($builder) use ($request) {
                $builder->where('status_aktif', $request->string('status_aktif') === 'aktif');
            })
            ->orderBy('kd_akun')
            ->orderBy('kode_mak_lengkap')
            ->paginate(15)
            ->withQueryString();

        $allCoas = $baseQuery->get();

        $summary = [
            'total_coa' => $allCoas->count(),
            'coa_aktif' => $allCoas->where('status_aktif', true)->count(),
            'kode_akun_unik' => $allCoas->pluck('kd_akun')->filter()->unique()->count(),
            'coa_dipakai_di_dipa' => $allCoas->where('dipa_revision_items_count', '>', 0)->count(),
        ];

        $jenisAkunOptions = MasterCoa::query()
            ->whereNotNull('jenis_akun')
            ->distinct()
            ->orderBy('jenis_akun')
            ->pluck('jenis_akun');

        return view('coas.index', compact('coas', 'summary', 'jenisAkunOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $kodeMakLengkap = $this->buildKodeMakLengkap($validated);

        if ($kodeMakLengkap === '') {
            return back()
                ->withInput()
                ->withErrors(['kode_mak_lengkap' => 'Kode COA lengkap tidak boleh kosong. Lengkapi minimal Kode Akun dan struktur kode yang diperlukan.']);
        }

        if (MasterCoa::query()->where('kode_mak_lengkap', $kodeMakLengkap)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['kode_mak_lengkap' => 'Kode COA lengkap sudah digunakan.']);
        }

        $coa = MasterCoa::create([
            'kd_program' => $this->normalizeCode($validated['kd_program'] ?? null),
            'kd_giat' => $this->normalizeCode($validated['kd_giat'] ?? null),
            'kd_output' => $this->normalizeCode($validated['kd_output'] ?? null),
            'kd_suboutput' => $this->normalizeCode($validated['kd_suboutput'] ?? null),
            'kd_komponen' => $this->normalizeCode($validated['kd_komponen'] ?? null),
            'kd_subkomponen' => $this->normalizeCode($validated['kd_subkomponen'] ?? null),
            'kd_akun' => $this->normalizeCode($validated['kd_akun']),
            'kd_item' => $this->normalizeCode($validated['kd_item'] ?? null),
            'kode_mak_lengkap' => $kodeMakLengkap,
            'nama_akun' => trim($validated['nama_akun']),
            'jenis_akun' => $this->normalizeText($validated['jenis_akun'] ?? null),
            'status_aktif' => (bool) ($validated['status_aktif'] ?? false),
        ]);

        $message = 'COA ' . $coa->kode_mak_lengkap . ' berhasil ditambahkan.';

        if (($validated['redirect_action'] ?? 'save') === 'save_and_create') {
            return redirect()
                ->route('coas.create')
                ->with('success', $message . ' Silakan tambahkan COA berikutnya.');
        }

        return redirect()
            ->route('coas.index')
            ->with('success', $message);
    }

    public function show(MasterCoa $coa)
    {
        $coa->load([
            'dipaRevisionItems' => function ($query) {
                $query->with(['dipaRevision.masterDipa', 'realisasiAnggarans'])
                    ->orderByDesc('updated_at');
            },
        ]);

        $usageItems = $coa->dipaRevisionItems
            ->filter(fn ($item) => $item->dipaRevision && $item->dipaRevision->masterDipa)
            ->values();

        $uniqueRevisions = $usageItems
            ->pluck('dipa_revision_id')
            ->filter()
            ->unique();

        $uniqueDipas = $usageItems
            ->pluck('dipaRevision.master_dipa_id')
            ->filter()
            ->unique();

        $totalNilaiPagu = (float) $usageItems->sum('nilai_pagu');
        $totalRealisasi = (float) $usageItems->sum(fn ($item) => (float) $item->total_realisasi);

        $statistics = [
            'jumlah_item_dipa' => $usageItems->count(),
            'jumlah_revisi_dipa' => $uniqueRevisions->count(),
            'jumlah_dipa' => $uniqueDipas->count(),
            'total_nilai_pagu' => $totalNilaiPagu,
            'total_realisasi' => $totalRealisasi,
            'total_sisa_pagu' => $totalNilaiPagu - $totalRealisasi,
        ];

        $billingUsages = $this->billingUsageRows($coa);

        $billingStatistics = [
            'jumlah_tagihan' => $billingUsages->count(),
            'jumlah_pengeluaran' => $billingUsages->where('kategori', 'Pengeluaran')->count(),
            'jumlah_penerimaan' => $billingUsages->where('kategori', 'Penerimaan')->count(),
            'total_nominal' => (float) $billingUsages->sum('nominal'),
        ];

        return view('coas.show', compact('coa', 'usageItems', 'statistics', 'billingUsages', 'billingStatistics'));
    }

    public function edit(MasterCoa $coa)
    {
        return view('coas.edit', compact('coa'));
    }

    public function update(Request $request, MasterCoa $coa)
    {
        $validated = $this->validatePayload($request, $coa);

        $kodeMakLengkap = $this->buildKodeMakLengkap($validated);

        if ($kodeMakLengkap === '') {
            return back()
                ->withInput()
                ->withErrors(['kode_mak_lengkap' => 'Kode COA lengkap tidak boleh kosong. Lengkapi minimal Kode Akun dan struktur kode yang diperlukan.']);
        }

        if (MasterCoa::query()
            ->where('kode_mak_lengkap', $kodeMakLengkap)
            ->whereKeyNot($coa->id)
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors(['kode_mak_lengkap' => 'Kode COA lengkap sudah digunakan oleh COA lain.']);
        }

        $coa->update([
            'kd_program' => $this->normalizeCode($validated['kd_program'] ?? null),
            'kd_giat' => $this->normalizeCode($validated['kd_giat'] ?? null),
            'kd_output' => $this->normalizeCode($validated['kd_output'] ?? null),
            'kd_suboutput' => $this->normalizeCode($validated['kd_suboutput'] ?? null),
            'kd_komponen' => $this->normalizeCode($validated['kd_komponen'] ?? null),
            'kd_subkomponen' => $this->normalizeCode($validated['kd_subkomponen'] ?? null),
            'kd_akun' => $this->normalizeCode($validated['kd_akun']),
            'kd_item' => $this->normalizeCode($validated['kd_item'] ?? null),
            'kode_mak_lengkap' => $kodeMakLengkap,
            'nama_akun' => trim($validated['nama_akun']),
            'jenis_akun' => $this->normalizeText($validated['jenis_akun'] ?? null),
            'status_aktif' => (bool) ($validated['status_aktif'] ?? false),
        ]);

        return redirect()
            ->route('coas.show', $coa)
            ->with('success', 'COA ' . $coa->kode_mak_lengkap . ' berhasil diperbarui.');
    }

    public function toggle(MasterCoa $coa)
    {
        $coa->update([
            'status_aktif' => ! $coa->status_aktif,
        ]);

        return redirect()
            ->route('coas.index')
            ->with('success', 'Status COA ' . ($coa->kode_mak_lengkap ?: $coa->nama_akun) . ' berhasil diperbarui.');
    }

    public function destroy(MasterCoa $coa)
    {
        $coa->loadCount('dipaRevisionItems');

        if ($coa->dipa_revision_items_count > 0) {
            return redirect()
                ->route('coas.show', $coa)
                ->with('error', 'COA tidak dapat dihapus karena sudah dipakai pada item DIPA.');
        }

        $label = $coa->kode_mak_lengkap ?: $coa->nama_akun;
        $coa->delete();

        return redirect()
            ->route('coas.index')
            ->with('success', 'COA ' . $label . ' berhasil dihapus.');
    }

    private function billingUsageRows(MasterCoa $coa)
    {
        $pengeluaranRows = Tagihan::query()
            ->with([
                'pihak',
                'dipaRevisionItem.dipaRevision.masterDipa',
                'detailKontrak.termin.kontrak.vendor',
                'spps.dipaRevisionItem',
                'komponenPerjaldin.dipaRevisionItem',
            ])
            ->where(function ($query) use ($coa) {
                $query->whereHas('dipaRevisionItem', fn ($itemQuery) => $itemQuery->where('coa_id', $coa->id))
                    ->orWhereHas('spps.dipaRevisionItem', fn ($itemQuery) => $itemQuery->where('coa_id', $coa->id))
                    ->orWhereHas('komponenPerjaldin.dipaRevisionItem', fn ($itemQuery) => $itemQuery->where('coa_id', $coa->id));
            })
            ->latest()
            ->get()
            ->map(fn (Tagihan $tagihan) => $this->mapTagihanUsage($tagihan, $coa));

        $penerimaanRows = TransaksiPenerimaan::query()
            ->with('mitra')
            ->where('coa_id', $coa->id)
            ->orderByDesc('tanggal_invoice')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TransaksiPenerimaan $penerimaan) => [
                'kategori' => 'Penerimaan',
                'tipe' => 'PIUTANG',
                'nomor' => $penerimaan->nomor_invoice ?: 'INV-' . $penerimaan->id,
                'tanggal' => $penerimaan->tanggal_invoice ?? $penerimaan->created_at,
                'uraian' => $penerimaan->keterangan ?: 'Tagihan penerimaan / piutang',
                'pihak' => $penerimaan->mitra?->nama_pihak ?: '-',
                'referensi' => null,
                'nominal' => (float) $penerimaan->nominal_tagihan,
                'status' => $penerimaan->status_pembayaran ?: '-',
                'detail_url' => null,
            ]);

        return $pengeluaranRows
            ->merge($penerimaanRows)
            ->sortByDesc(fn ($row) => optional($row['tanggal'])->timestamp ?? 0)
            ->values();
    }

    private function mapTagihanUsage(Tagihan $tagihan, MasterCoa $coa): array
    {
        $matchingSpps = $tagihan->spps
            ->filter(fn ($spp) => (int) ($spp->dipaRevisionItem?->coa_id) === (int) $coa->id)
            ->values();

        $matchingComponents = $tagihan->komponenPerjaldin
            ->filter(fn ($component) => (int) ($component->dipaRevisionItem?->coa_id) === (int) $coa->id)
            ->values();

        $references = collect()
            ->merge($matchingComponents->pluck('nama_komponen')->filter())
            ->merge($matchingSpps->map(function ($spp) {
                return $spp->komponen_biaya
                    ? $spp->komponen_biaya . ' / ' . $spp->nomor_spp
                    : $spp->nomor_spp;
            })->filter())
            ->unique()
            ->values();

        $nominal = match (true) {
            $matchingSpps->isNotEmpty() => (float) $matchingSpps->sum('nominal_spp'),
            $matchingComponents->isNotEmpty() => (float) $matchingComponents->sum('total_nominal'),
            default => (float) ($tagihan->total_netto ?? $tagihan->total_bruto ?? 0),
        };

        return [
            'kategori' => 'Pengeluaran',
            'tipe' => $tagihan->tipe_tagihan ?: '-',
            'nomor' => $tagihan->nomor_tagihan ?: 'TAG-' . $tagihan->id,
            'tanggal' => $tagihan->detailKontrak?->tanggal_invoice ?? $tagihan->created_at,
            'uraian' => $tagihan->deskripsi ?: '-',
            'pihak' => $tagihan->pihak?->nama_pihak
                ?: $tagihan->detailKontrak?->termin?->kontrak?->vendor?->nama_pihak
                ?: '-',
            'referensi' => $references->isNotEmpty() ? $references->implode(', ') : 'Tagihan utama',
            'nominal' => $nominal,
            'status' => $tagihan->status ?: '-',
            'detail_url' => $this->tagihanDetailUrl($tagihan),
        ];
    }

    private function tagihanDetailUrl(Tagihan $tagihan): ?string
    {
        $user = auth()->user();

        if ($user?->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => $this->routeIfExists('verifikasi-tagihan-kontrak.show', $tagihan->id),
                'PERJALDIN' => $this->routeIfExists('verifikasi-kasubag.perjaldin.show', $tagihan->id),
                'HONORARIUM' => $this->latestSppRouteIfExists($tagihan, 'verifikasi-spp.honor.detail'),
                default => null,
            };
        }

        if ($user?->hasRole('Koordinator Keuangan')) {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => $this->routeIfExists('verifikasi-tagihan-kontrak.show', $tagihan->id),
                'PERJALDIN' => $this->routeIfExists('verifikasi-koordinator.perjaldin.show', $tagihan->id),
                'HONORARIUM' => $this->routeIfExists('verifikasi-koordinator.honorarium.show', $tagihan->id),
                default => null,
            };
        }

        if ($user?->hasRole('Bendahara Pengeluaran')) {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => $this->routeIfExists('verifikasi-tagihan-kontrak.show', $tagihan->id),
                'PERJALDIN' => $this->routeIfExists('verifikasi-bendahara.perjaldin.show', $tagihan->id),
                'HONORARIUM' => $this->routeIfExists('verifikasi-bendahara.honorarium.show', $tagihan->id),
                default => null,
            };
        }

        if ($user?->hasRole('Bendahara Penerimaan')) {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => $this->routeIfExists('verifikasi-tagihan-kontrak.show', $tagihan->id),
                'PERJALDIN' => $this->routeIfExists('verifikasi-bendahara-penerimaan.perjaldin.show', $tagihan->id),
                default => null,
            };
        }

        if ($user?->hasRole('PPSPM')) {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => $this->routeIfExists('verifikasi-tagihan-kontrak.show', $tagihan->id),
                'PERJALDIN' => $this->routeIfExists('verifikasi-ppspm.perjaldin.show', $tagihan->id),
                default => null,
            };
        }

        if ($user?->hasRole('PPK')) {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => $this->routeIfExists('tagihan.kontrak.show', $tagihan->id),
                'PERJALDIN' => $this->routeIfExists('verifikasi-ppk.perjaldin.show', $tagihan->id),
                'HONORARIUM' => $this->routeIfExists('verifikasi-ppk.honorarium.show', $tagihan->id),
                default => null,
            };
        }

        return match ($tagihan->tipe_tagihan) {
            'KONTRAK' => $this->routeIfExists('tagihan.kontrak.show', $tagihan->id),
            'HONORARIUM' => $this->routeIfExists('honorarium.show', $tagihan->id),
            'PERJALDIN' => $this->routeIfExists('perjaldins.show', $tagihan->id),
            default => null,
        };
    }

    private function latestSppRouteIfExists(Tagihan $tagihan, string $routeName): ?string
    {
        $spp = $tagihan->relationLoaded('spps')
            ? $tagihan->spps->sortByDesc('created_at')->first()
            : $tagihan->spps()->latest()->first();

        return $spp ? $this->routeIfExists($routeName, $spp->id) : null;
    }

    private function routeIfExists(string $routeName, mixed $parameters = []): ?string
    {
        return Route::has($routeName) ? route($routeName, $parameters) : null;
    }

    private function buildKodeMakLengkap(array $payload): string
    {
        $segments = collect([
            $payload['kd_program'] ?? null,
            $payload['kd_giat'] ?? null,
            $payload['kd_output'] ?? null,
            $payload['kd_suboutput'] ?? null,
            $payload['kd_komponen'] ?? null,
            $payload['kd_subkomponen'] ?? null,
            $payload['kd_akun'] ?? null,
            $payload['kd_item'] ?? null,
        ])->map(fn ($value) => $this->normalizeCode($value))
            ->filter(fn ($value) => $value !== null && $value !== '');

        return $segments->implode('.');
    }

    private function normalizeCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function validatePayload(Request $request, ?MasterCoa $coa = null): array
    {
        return $request->validate([
            'kd_program' => 'nullable|string|max:10',
            'kd_giat' => 'nullable|string|max:10',
            'kd_output' => 'nullable|string|max:10',
            'kd_suboutput' => 'nullable|string|max:10',
            'kd_komponen' => 'nullable|string|max:10',
            'kd_subkomponen' => 'nullable|string|max:10',
            'kd_akun' => 'required|string|max:20',
            'kd_item' => 'nullable|string|max:20',
            'nama_akun' => 'required|string|max:150',
            'jenis_akun' => 'nullable|string|max:50',
            'status_aktif' => 'nullable|boolean',
            'redirect_action' => [
                'nullable',
                'string',
                Rule::in($coa ? ['save'] : ['save', 'save_and_create']),
            ],
        ]);
    }
}
