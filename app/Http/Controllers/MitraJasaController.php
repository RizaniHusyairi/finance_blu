<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MitraJasaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canManageMitraMaster = $this->canManageMitraMaster();
        $adminLayananIds = $canManageMitraMaster
            ? collect()
            : $user?->layananJasaDikelolaAktif()
                ->where('layanan_jasas.is_active', true)
                ->pluck('layanan_jasas.id')
                ->map(fn ($id) => (int) $id)
                ->values();

        $mitras = MitraJasa::query()
            ->with([
                'user',
                'kontrakAktif',
                'layananJasa' => fn ($query) => $query->wherePivot('status_aktif', true),
            ])
            ->when(! $canManageMitraMaster, function ($query) use ($adminLayananIds) {
                $query->whereHas('layananJasa', function ($subQuery) use ($adminLayananIds) {
                    $subQuery->where('mitra_jasa_layanan.status_aktif', true)
                        ->whereIn('layanan_jasas.id', $adminLayananIds);
                });
            })
            ->withCount([
                'konsesi as konsesi_aktif_count' => fn ($query) => $query->where('status_aktif', true),
                'penjualan as laporan_penjualan_count',
                'penjualan as laporan_menunggu_count' => fn ($query) => $query->where('status', 'diajukan'),
            ])
            ->orderBy('nama_mitra')
            ->paginate(15)
            ->withQueryString();

        return view('super_admin_jasa.mitra.index', compact('mitras'));
    }

    public function create()
    {
        $this->abortUnlessCanManageMitraMaster();

        return view('super_admin_jasa.mitra.form', ['mitra' => new MitraJasa()]);
    }

    public function store(Request $request)
    {
        $this->abortUnlessCanManageMitraMaster();

        $validated = $this->validateMitra($request);
        $validated['kode_mitra'] = $validated['kode_mitra'] ?: null;
        $validated['status_aktif'] = $request->boolean('status_aktif', true);
        $validated['created_by'] = auth()->id();

        $mitra = MitraJasa::create($validated);

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Mitra jasa berhasil dibuat.');
    }

    public function show(MitraJasa $mitra)
    {
        $this->abortUnlessCanViewMitra($mitra);

        $mitra->load([
            'user',
            'kontrak' => fn ($query) => $query->with('layananJasa')->latest(),
            'konsesi.kontrakMitraJasa',
            'konsesi.layananJasa.parent.parent.parent.parent.parent',
            'pjp2u.kontrakMitraJasa',
            'pjp2u.layananJasa.parent.parent.parent.parent.parent',
            'penjualan.konsesi',
            'penjualan.layananJasa',
            'penjualan.tagihanJasa',
            'laporanUtilitas.layananJasa',
            'laporanUtilitas.tagihanJasa.details.layananJasa',
            'tagihanJasas.details.layananJasa',
            'tagihanJasas.kontrakMitraJasa',
            'layananJasa' => fn ($query) => $query->wherePivot('status_aktif', true)->orderBy('nama_layanan'),
        ]);

        $layananTreeItems = LayananJasa::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        $selectedLayananIds = $mitra->layananJasa->pluck('id')->all();
        $visibleLayananIds = [];
        $layananById = $layananTreeItems->keyBy('id');

        foreach ($selectedLayananIds as $layananId) {
            $current = $layananById->get($layananId);
            while ($current) {
                $visibleLayananIds[] = $current->id;
                $current = $current->parent_id ? $layananById->get($current->parent_id) : null;
            }
        }

        $visibleLayananIds = array_values(array_unique($visibleLayananIds));
        $tagihanJasas = $this->tagihanJasasForMitra($mitra);
        $mitra->setRelation('tagihanJasas', $tagihanJasas);

        $tagihanKonsesiIds = $mitra->penjualan
            ->pluck('tagihan_jasa_id')
            ->filter()
            ->unique()
            ->values();
        $tagihanUtilitasIds = $mitra->laporanUtilitas
            ->pluck('tagihan_jasa_id')
            ->filter()
            ->unique()
            ->values();
        $tagihanKonsesiDetailIds = $mitra->tagihanJasas
            ->filter(function ($tagihan) {
                return $tagihan->details->contains(function ($detail) {
                    return stripos((string) $detail->keterangan, 'konsesi') !== false
                        || stripos((string) $detail->layananJasa?->nama_layanan, 'konsesi') !== false;
                });
            })
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();
        $tagihanPjp2uDetailIds = $mitra->tagihanJasas
            ->filter(function ($tagihan) {
                return $tagihan->details->contains(function ($detail) {
                    $text = strtolower(trim(($detail->keterangan ?? '') . ' ' . ($detail->layananJasa?->nama_lengkap ?? '') . ' ' . ($detail->layananJasa?->nama_layanan ?? '')));

                    return str_contains($text, 'pjp2u')
                        || str_contains($text, 'penumpang pesawat')
                        || str_contains($text, 'pax');
                });
            })
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();
        $tagihanUtilitasDetailIds = $mitra->tagihanJasas
            ->filter(function ($tagihan) {
                return $tagihan->details->contains(function ($detail) {
                    $text = strtolower(trim(($detail->keterangan ?? '') . ' ' . ($detail->layananJasa?->nama_lengkap ?? '') . ' ' . ($detail->layananJasa?->nama_layanan ?? '')));

                    return str_contains($text, 'utilitas')
                        || str_contains($text, 'penggunaan listrik')
                        || str_contains($text, 'listrik bandar')
                        || str_contains($text, 'penggunaan air')
                        || str_contains($text, 'air bandar');
                });
            })
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();
        $tagihanPnbp = $tagihanJasas
            ->whereNotIn('id', $tagihanKonsesiIds
                ->merge($tagihanUtilitasIds)
                ->merge($tagihanKonsesiDetailIds)
                ->merge($tagihanPjp2uDetailIds)
                ->merge($tagihanUtilitasDetailIds)
                ->unique()
                ->all())
            ->sortByDesc(fn ($tagihan) => $tagihan->tanggal_tagihan ?? $tagihan->created_at)
            ->values();

        return view('super_admin_jasa.mitra.show', compact(
            'mitra',
            'layananTreeItems',
            'selectedLayananIds',
            'visibleLayananIds',
            'tagihanPnbp',
        ));
    }

    private function tagihanJasasForMitra(MitraJasa $mitra): Collection
    {
        $linkedIds = $mitra->penjualan
            ->pluck('tagihan_jasa_id')
            ->merge($mitra->penjualan->pluck('source_tagihan_jasa_id'))
            ->merge($mitra->laporanUtilitas->pluck('tagihan_jasa_id'))
            ->filter()
            ->unique()
            ->values();

        return TagihanJasa::query()
            ->with(['details.layananJasa.parent.parent.parent.parent.parent', 'kontrakMitraJasa'])
            ->where(function ($query) use ($mitra, $linkedIds) {
                $query->where('mitra_jasa_id', $mitra->id);

                if ($linkedIds->isNotEmpty()) {
                    $query->orWhereIn('id', $linkedIds->all());
                }

            })
            ->latest('tanggal_tagihan')
            ->latest('id')
            ->get();
    }

    public function edit(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        return view('super_admin_jasa.mitra.form', compact('mitra'));
    }

    public function update(Request $request, MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        $validated = $this->validateMitra($request, $mitra->id);
        $validated['status_aktif'] = $request->boolean('status_aktif');
        $validated['kode_mitra'] = $validated['kode_mitra'] ?: null;
        $validated['updated_by'] = auth()->id();

        $mitra->update($validated);

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Mitra jasa berhasil diperbarui.');
    }

    public function destroy(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        $blocked = collect([
            'kontrak/dokumen' => $mitra->kontrak()->exists(),
            'hak konsesi' => $mitra->konsesi()->exists(),
            'hak PJP2U' => $mitra->pjp2u()->exists(),
            'laporan penjualan/PJP2U' => $mitra->penjualan()->exists(),
            'laporan utilitas' => $mitra->laporanUtilitas()->exists(),
            'tagihan jasa' => $mitra->tagihanJasas()->exists(),
        ])->filter();

        if ($blocked->isNotEmpty()) {
            return back()->with('error', 'Mitra tidak dapat dihapus karena sudah memiliki data: ' . $blocked->keys()->join(', ') . '.');
        }

        $mitra->layananJasa()->detach();

        if ($mitra->user) {
            $mitra->user->delete();
        }

        $mitra->delete();

        return redirect()
            ->route('jasa.mitra.index')
            ->with('success', 'Mitra jasa berhasil dihapus.');
    }

    private function validateMitra(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'kode_mitra' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('mitra_jasa', 'kode_mitra')->ignore($ignoreId),
            ],
            'jenis_mitra' => ['nullable', 'in:BADAN_USAHA,PERORANGAN,INSTANSI,MASKAPAI,TENANT,OPERATOR,LAINNYA'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'nama_mitra' => ['required', 'string', 'max:150'],
            'nama_penanggung_jawab' => ['nullable', 'string', 'max:150'],
            'jabatan_penanggung_jawab' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:150'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
            'status_aktif' => ['nullable', 'boolean'],
        ]);
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            $this->canManageMitraMaster(),
            403
        );
    }

    private function abortUnlessCanViewMitra(MitraJasa $mitra): void
    {
        if ($this->canManageMitraMaster()) {
            return;
        }

        $allowedIds = auth()->user()?->layananJasaDikelolaAktif()
            ->where('layanan_jasas.is_active', true)
            ->pluck('layanan_jasas.id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        abort_unless(
            $mitra->layananJasaAktif()
                ->whereIn('layanan_jasas.id', $allowedIds)
                ->exists(),
            403
        );
    }

    private function canManageMitraMaster(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true;
    }
}
