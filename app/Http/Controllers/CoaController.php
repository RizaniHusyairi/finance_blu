<?php

namespace App\Http\Controllers;

use App\Models\MasterCoa;
use Illuminate\Http\Request;
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
                $query->with(['dipaRevision.masterDipa'])
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

        $statistics = [
            'jumlah_item_dipa' => $usageItems->count(),
            'jumlah_revisi_dipa' => $uniqueRevisions->count(),
            'jumlah_dipa' => $uniqueDipas->count(),
            'total_nilai_pagu' => (float) $usageItems->sum('nilai_pagu'),
        ];

        return view('coas.show', compact('coa', 'usageItems', 'statistics'));
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
