<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaKonsesi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MitraJasaKonsesiController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'mitra_jasa_id' => ['nullable', 'integer', 'exists:mitra_jasa,id'],
            'status_aktif' => ['nullable', 'in:1,0'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $query = MitraJasaKonsesi::with(['mitraJasa', 'kontrakMitraJasa', 'layananJasa'])
            ->latest('tanggal_mulai')
            ->latest('id');

        if (! auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa', 'Admin Konsesi'])) {
            $allowedIds = auth()->user()
                ->layananJasaDikelolaAktif()
                ->where('layanan_jasas.is_active', true)
                ->pluck('layanan_jasas.id')
                ->all();

            $query->whereIn('layanan_jasa_id', $allowedIds);
        }

        $query
            ->when($filters['mitra_jasa_id'] ?? null, fn ($subQuery, $mitraId) => $subQuery->where('mitra_jasa_id', $mitraId))
            ->when(isset($filters['status_aktif']), fn ($subQuery) => $subQuery->where('status_aktif', (bool) $filters['status_aktif']))
            ->when($filters['q'] ?? null, function ($subQuery, $q) {
                $subQuery->where(function ($nested) use ($q) {
                    $nested->whereHas('mitraJasa', fn ($mitraQuery) => $mitraQuery->where('nama_mitra', 'like', "%{$q}%"))
                        ->orWhereHas('layananJasa', fn ($layananQuery) => $layananQuery->where('nama_layanan', 'like', "%{$q}%"))
                        ->orWhereHas('kontrakMitraJasa', fn ($kontrakQuery) => $kontrakQuery->where('nomor_kontrak', 'like', "%{$q}%"));
                });
            });

        $konsesis = $query->paginate(15)->withQueryString();
        $mitras = MitraJasa::query()->orderBy('nama_mitra')->get(['id', 'nama_mitra']);

        return view('super_admin_jasa.mitra.konsesi-index', compact('konsesis', 'mitras', 'filters'));
    }

    public function create(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        return view('super_admin_jasa.mitra.konsesi-form', [
            'mitra' => $mitra->load(['kontrak', 'layananJasa']),
            'konsesi' => new MitraJasaKonsesi([
                'periode_pelaporan' => 'bulanan',
                'tanggal_mulai' => now()->toDateString(),
                'status_aktif' => true,
            ]),
            'kontraks' => $mitra->kontrak()->with('layananJasa')->orderByDesc('tanggal_kontrak')->get(),
            'layanans' => $this->layananAktifMitra($mitra),
        ]);
    }

    public function store(Request $request, MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        $validated = $this->validateKonsesi($request, $mitra);
        $validated['mitra_jasa_id'] = $mitra->id;
        $validated['status_aktif'] = $request->boolean('status_aktif', true);
        $validated['created_by'] = auth()->id();

        MitraJasaKonsesi::create($validated);

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Konsesi/penjualan mitra berhasil ditambahkan.');
    }

    public function edit(MitraJasa $mitra, MitraJasaKonsesi $konsesi)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $konsesi);

        return view('super_admin_jasa.mitra.konsesi-form', [
            'mitra' => $mitra->load(['kontrak', 'layananJasa']),
            'konsesi' => $konsesi,
            'kontraks' => $mitra->kontrak()->with('layananJasa')->orderByDesc('tanggal_kontrak')->get(),
            'layanans' => $this->layananAktifMitra($mitra),
        ]);
    }

    public function update(Request $request, MitraJasa $mitra, MitraJasaKonsesi $konsesi)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $konsesi);

        $validated = $this->validateKonsesi($request, $mitra);
        $validated['status_aktif'] = $request->boolean('status_aktif');
        $validated['updated_by'] = auth()->id();

        $konsesi->update($validated);

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Konsesi/penjualan mitra berhasil diperbarui.');
    }

    public function deactivate(MitraJasa $mitra, MitraJasaKonsesi $konsesi)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $konsesi);

        $konsesi->update([
            'status_aktif' => false,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Konsesi/penjualan mitra berhasil dinonaktifkan.');
    }

    private function validateKonsesi(Request $request, MitraJasa $mitra): array
    {
        $validated = $request->validate([
            'kontrak_mitra_jasa_id' => ['nullable', 'integer', Rule::exists('kontrak_mitra_jasa', 'id')->where('mitra_jasa_id', $mitra->id)],
            'layanan_jasa_id' => ['nullable', 'integer', 'exists:layanan_jasas,id'],
            'jenis_konsesi' => ['required', Rule::in(['persen_omzet', 'nilai_tetap', 'minimum_guarantee', 'kombinasi'])],
            'persentase_konsesi' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_tetap' => ['nullable', 'numeric', 'min:0'],
            'nilai_minimum_guarantee' => ['nullable', 'numeric', 'min:0'],
            'periode_pelaporan' => ['required', Rule::in(['harian', 'mingguan', 'bulanan'])],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status_aktif' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string'],
        ]);

        if (! empty($validated['layanan_jasa_id'])) {
            $allowedLeafIds = $mitra->layananJasaAktif()
                ->where('layanan_jasas.is_active', true)
                ->where('layanan_jasas.is_leaf', true)
                ->where('layanan_jasas.mendukung_konsesi', true)
                ->pluck('layanan_jasas.id')
                ->all();

            $selected = LayananJasa::query()
                ->with('children.children.children.children.children')
                ->findOrFail($validated['layanan_jasa_id']);

            $selectedIds = $this->collectDescendantLeafIds($selected);
            $isActiveForMitra = count(array_intersect($selectedIds, $allowedLeafIds)) > 0;

            if (! $isActiveForMitra) {
                back()->withInput()->with('error', 'Layanan/kategori yang dipilih belum memiliki item Konsesi aktif untuk mitra ini.')->throwResponse();
            }

            if (! empty($validated['kontrak_mitra_jasa_id'])) {
                $kontrak = $mitra->kontrak()->with('layananJasa')->findOrFail($validated['kontrak_mitra_jasa_id']);
                $scopedIds = $kontrak->layananJasa->pluck('id')->map(fn ($id) => (int) $id);

                if ($scopedIds->isNotEmpty() && ! $scopedIds->intersect($selectedIds)->isNotEmpty()) {
                    back()->withInput()->with('error', 'Kontrak yang dipilih tidak mencakup layanan konsesi tersebut.')->throwResponse();
                }
            }
        }

        if (in_array($validated['jenis_konsesi'], ['persen_omzet', 'minimum_guarantee', 'kombinasi'], true)
            && empty($validated['persentase_konsesi'])) {
            back()->withInput()->with('error', 'Persentase konsesi wajib diisi untuk skema berbasis omzet.')->throwResponse();
        }

        if (in_array($validated['jenis_konsesi'], ['nilai_tetap', 'kombinasi'], true)
            && empty($validated['nilai_tetap'])) {
            back()->withInput()->with('error', 'Nilai tetap wajib diisi untuk skema nilai tetap/kombinasi.')->throwResponse();
        }

        return $validated;
    }

    private function layananAktifMitra(MitraJasa $mitra)
    {
        $leafIds = $mitra->layananJasaAktif()
            ->where('layanan_jasas.is_active', true)
            ->where('layanan_jasas.mendukung_konsesi', true)
            ->pluck('layanan_jasas.id');

        if ($leafIds->isEmpty()) {
            return collect();
        }

        $items = LayananJasa::with('parent.parent.parent.parent.parent')
            ->whereIn('id', $leafIds)
            ->get();

        $visibleIds = $items->flatMap(function (LayananJasa $layanan) {
            $ids = [$layanan->id];
            $parent = $layanan->parent;
            $guard = 0;

            while ($parent && $guard < 10) {
                $ids[] = $parent->id;
                $parent = $parent->parent;
                $guard++;
            }

            return $ids;
        })->unique()->values();

        return LayananJasa::query()
            ->whereIn('id', $visibleIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();
    }

    private function ensureOwnedByMitra(MitraJasa $mitra, MitraJasaKonsesi $konsesi): void
    {
        abort_unless((int) $konsesi->mitra_jasa_id === (int) $mitra->id, 404);
    }

    private function collectDescendantLeafIds(LayananJasa $layanan): array
    {
        if ($layanan->is_leaf) {
            return [$layanan->id];
        }

        return $layanan->children
            ->flatMap(fn (LayananJasa $child) => $this->collectDescendantLeafIds($child))
            ->values()
            ->all();
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
