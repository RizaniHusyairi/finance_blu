<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterLayananJasaController extends Controller
{
    public function index(Request $request)
    {
        $canManageMaster = $this->canManageMaster();
        $tipe = $request->query('tipe', 'SEMUA');
        if (! in_array($tipe, ['SEMUA', 'PNBP', 'KONSESI'], true)) {
            $tipe = 'SEMUA';
        }

        $query = LayananJasa::with('parent')
            ->orderBy('level')
            ->orderBy('nama_layanan');

        if (! $canManageMaster) {
            $query->whereIn('id', $this->visibleLayananIdsForAdmin());
        }

        $layanans = $query->get();

        $counts = [
            'SEMUA' => $layanans->count(),
            'PNBP' => $layanans->where('tipe_layanan', 'PNBP')->count(),
            'KONSESI' => $layanans->where('mendukung_konsesi', true)->count(),
        ];

        $filteredLayanans = $tipe === 'SEMUA'
            ? $layanans
            : $this->filterTreeByType($layanans, $tipe);

        return view('master_layanan_jasa.index', [
            'layanans' => $filteredLayanans,
            'canManageMaster' => $canManageMaster,
            'tipe' => $tipe,
            'counts' => $counts,
        ]);
    }

    public function create()
    {
        abort_unless($this->canManageMaster(), 403);

        $parentOptions = $this->parentOptions();

        return view('master_layanan_jasa.create', compact('parentOptions'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageMaster(), 403);

        $validated = $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'node_type' => 'required|in:category,item',
            'parent_id' => 'nullable|exists:layanan_jasas,id',
            'kode_mak' => 'nullable|string|max:255',
            'kode_akun' => 'nullable|string|max:255',
            'satuan' => 'nullable|string|max:255',
            'tarif_dasar' => 'nullable|numeric|min:0',
            'tipe_layanan' => 'required|in:PNBP,KONSESI',
            'mendukung_konsesi' => 'boolean',
            'persentase_konsesi' => 'nullable|numeric|min:0|max:100',
            'jumlah_hari_jatuh_tempo' => 'required|integer|min:0|max:365',
            'masa_toleransi_hari' => 'required|integer|min:0|max:365',
            'wajib_tagihan_terpisah' => 'boolean',
            'catatan_jatuh_tempo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $nodeType = $validated['node_type'];
        unset($validated['node_type']);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_leaf'] = $nodeType === 'item';
        $validated['tarif_dasar'] = $validated['is_leaf'] ? ($validated['tarif_dasar'] ?? 0) : 0;
        $validated['satuan'] = $validated['is_leaf'] ? ($validated['satuan'] ?? null) : null;
        $validated['mendukung_konsesi'] = $validated['is_leaf'] && $request->has('mendukung_konsesi');
        if (! $validated['is_leaf']) {
            $validated['tipe_layanan'] = 'PNBP';
        }
        if ($validated['is_leaf'] && $validated['tipe_layanan'] === 'KONSESI') {
            $validated['mendukung_konsesi'] = true;
        }
        if (! $validated['mendukung_konsesi']) {
            $validated['persentase_konsesi'] = null;
        } elseif (($validated['persentase_konsesi'] ?? null) === null) {
            return back()
                ->withInput()
                ->withErrors(['persentase_konsesi' => 'Persentase konsesi wajib diisi jika layanan mendukung konsesi.']);
        }
        $validated['wajib_tagihan_terpisah'] = $request->has('wajib_tagihan_terpisah');
        if (! $validated['is_leaf']) {
            $validated['wajib_tagihan_terpisah'] = false;
        }
        $validated['level'] = 1;

        if ($validated['parent_id']) {
            $parent = LayananJasa::find($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
            
            // Parent is no longer a leaf
            if ($parent->is_leaf) {
                $parent->update(['is_leaf' => false]);
            }
        }

        LayananJasa::create($validated);

        return redirect()->route('master-layanan-jasa.index')
            ->with('success', 'Layanan Jasa berhasil ditambahkan');
    }

    public function edit(LayananJasa $master_layanan_jasa)
    {
        abort_unless($this->canManageMaster(), 403);

        $parentOptions = $this->parentOptions($master_layanan_jasa);
            
        return view('master_layanan_jasa.edit', [
            'layanan' => $master_layanan_jasa,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function update(Request $request, LayananJasa $master_layanan_jasa)
    {
        abort_unless($this->canManageMaster(), 403);

        $validated = $request->validate([
            'nama_layanan' => 'required|string|max:255',
            'node_type' => 'required|in:category,item',
            'parent_id' => 'nullable|exists:layanan_jasas,id',
            'kode_mak' => 'nullable|string|max:255',
            'kode_akun' => 'nullable|string|max:255',
            'satuan' => 'nullable|string|max:255',
            'tarif_dasar' => 'nullable|numeric|min:0',
            'tipe_layanan' => 'required|in:PNBP,KONSESI',
            'mendukung_konsesi' => 'boolean',
            'persentase_konsesi' => 'nullable|numeric|min:0|max:100',
            'jumlah_hari_jatuh_tempo' => 'required|integer|min:0|max:365',
            'masa_toleransi_hari' => 'required|integer|min:0|max:365',
            'wajib_tagihan_terpisah' => 'boolean',
            'catatan_jatuh_tempo' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $nodeType = $validated['node_type'];
        unset($validated['node_type']);

        if ($nodeType === 'item' && $master_layanan_jasa->children()->exists()) {
            return back()
                ->withInput()
                ->withErrors(['node_type' => 'Layanan ini sudah memiliki anak, jadi tidak bisa diubah menjadi item tarif.']);
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['is_leaf'] = $nodeType === 'item';
        $validated['tarif_dasar'] = $validated['is_leaf'] ? ($validated['tarif_dasar'] ?? 0) : 0;
        $validated['satuan'] = $validated['is_leaf'] ? ($validated['satuan'] ?? null) : null;
        $validated['mendukung_konsesi'] = $validated['is_leaf'] && $request->has('mendukung_konsesi');
        if (! $validated['is_leaf']) {
            $validated['tipe_layanan'] = 'PNBP';
        }
        if ($validated['is_leaf'] && $validated['tipe_layanan'] === 'KONSESI') {
            $validated['mendukung_konsesi'] = true;
        }
        if (! $validated['mendukung_konsesi']) {
            $validated['persentase_konsesi'] = null;
        } elseif (($validated['persentase_konsesi'] ?? null) === null) {
            return back()
                ->withInput()
                ->withErrors(['persentase_konsesi' => 'Persentase konsesi wajib diisi jika layanan mendukung konsesi.']);
        }
        $validated['wajib_tagihan_terpisah'] = $request->has('wajib_tagihan_terpisah');
        if (! $validated['is_leaf']) {
            $validated['wajib_tagihan_terpisah'] = false;
        }
        
        $oldParentId = $master_layanan_jasa->parent_id;

        if ($validated['parent_id']) {
            $parent = LayananJasa::find($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
            
            if ($parent->is_leaf) {
                $parent->update(['is_leaf' => false]);
            }
        } else {
            $validated['level'] = 1;
        }

        $master_layanan_jasa->update($validated);
        
        // Re-evaluate old parent if changed
        if ($oldParentId && $oldParentId != $validated['parent_id']) {
            $oldParent = LayananJasa::find($oldParentId);
            if ($oldParent && $oldParent->children()->count() == 0) {
                $oldParent->update(['is_leaf' => true]);
            }
        }

        return redirect()->route('master-layanan-jasa.index')
            ->with('success', 'Layanan Jasa berhasil diupdate');
    }

    public function destroy(LayananJasa $master_layanan_jasa)
    {
        abort_unless($this->canManageMaster(), 403);

        if ($master_layanan_jasa->children()->count() > 0) {
            return redirect()->route('master-layanan-jasa.index')
                ->with('error', 'Gagal menghapus: Layanan ini memiliki sub-layanan.');
        }

        $blocked = collect([
            'mitra jasa' => $master_layanan_jasa->mitras()->exists(),
            'admin jasa' => $master_layanan_jasa->adminJasa()->exists(),
            'detail tagihan' => \App\Models\TagihanJasaDetail::where('layanan_jasa_id', $master_layanan_jasa->id)->exists(),
            'laporan penjualan/PJP2U' => \App\Models\MitraJasaPenjualan::where('layanan_jasa_id', $master_layanan_jasa->id)->exists(),
            'laporan utilitas' => \App\Models\LaporanUtilitas::where('layanan_jasa_id', $master_layanan_jasa->id)->exists(),
            'hak konsesi' => \App\Models\MitraJasaKonsesi::where('layanan_jasa_id', $master_layanan_jasa->id)->exists(),
            'hak PJP2U' => \App\Models\MitraJasaPjp2u::where('layanan_jasa_id', $master_layanan_jasa->id)->exists(),
        ])->filter();

        if ($blocked->isNotEmpty()) {
            return redirect()->route('master-layanan-jasa.index')
                ->with('error', 'Gagal menghapus: layanan sudah digunakan pada ' . $blocked->keys()->join(', ') . '.');
        }

        $parentId = $master_layanan_jasa->parent_id;
        $master_layanan_jasa->delete();

        // Check if parent is now a leaf
        if ($parentId) {
            $parent = LayananJasa::find($parentId);
            if ($parent && $parent->children()->count() == 0) {
                $parent->update(['is_leaf' => true]);
            }
        }

        return redirect()->route('master-layanan-jasa.index')
            ->with('success', 'Layanan Jasa berhasil dihapus');
    }

    private function parentOptions(?LayananJasa $exclude = null)
    {
        $items = LayananJasa::query()
            ->where('is_active', true)
            ->where('is_leaf', false)
            ->orderBy('level')
            ->orderBy('nama_layanan')
            ->get();

        if ($exclude) {
            $allItems = LayananJasa::query()
                ->where('is_active', true)
                ->get(['id', 'parent_id']);

            $excludedIds = $this->collectDescendantIds($allItems, $exclude->id)
                ->push($exclude->id)
                ->unique();

            $items = $items->reject(fn (LayananJasa $item) => $excludedIds->contains($item->id));
        }

        $childrenByParent = $items->groupBy(fn (LayananJasa $item) => $item->parent_id ?: 0);
        $options = collect();

        $walk = function (int $parentId = 0, int $depth = 0, array $path = []) use (&$walk, $childrenByParent, $options) {
            $childrenByParent
                ->get($parentId, collect())
                ->sortBy('nama_layanan', SORT_NATURAL | SORT_FLAG_CASE)
                ->each(function (LayananJasa $item) use (&$walk, $options, $depth, $path) {
                    $currentPath = array_merge($path, [$item->nama_layanan]);

                    $options->push([
                        'id' => $item->id,
                        'label' => $item->nama_layanan,
                        'depth' => $depth,
                        'path' => implode(' > ', $currentPath),
                    ]);

                    $walk($item->id, $depth + 1, $currentPath);
                });
        };

        $walk();

        return $options;
    }

    private function collectDescendantIds($items, int $parentId)
    {
        $ids = collect();

        foreach ($items->where('parent_id', $parentId) as $child) {
            $ids->push($child->id);
            $ids = $ids->merge($this->collectDescendantIds($items, $child->id));
        }

        return $ids;
    }

    private function canManageMaster(): bool
    {
        return Auth::user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Operator BLU', 'Koordinator Keuangan', 'Koordinator Jasa']) ?? false;
    }

    private function visibleLayananIdsForAdmin(): array
    {
        $assigned = Auth::user()
            ->layananJasaDikelolaAktif()
            ->where('layanan_jasas.is_active', true)
            ->pluck('layanan_jasas.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($assigned->isEmpty()) {
            return [];
        }

        $all = LayananJasa::query()
            ->with('parent.parent.parent.parent.parent')
            ->whereIn('id', $assigned)
            ->get();

        return $all->flatMap(function (LayananJasa $layanan) {
            $ids = [$layanan->id];
            $parent = $layanan->parent;
            $guard = 0;

            while ($parent && $guard < 10) {
                $ids[] = $parent->id;
                $parent = $parent->parent;
                $guard++;
            }

            return $ids;
        })->unique()->values()->all();
    }

    private function filterTreeByType($layanans, string $tipe)
    {
        $selectedIds = $layanans
            ->filter(function (LayananJasa $layanan) use ($tipe) {
                if ($tipe === 'KONSESI') {
                    return (bool) $layanan->mendukung_konsesi;
                }

                return ($layanan->tipe_layanan ?? 'PNBP') === $tipe;
            })
            ->pluck('id')
            ->all();

        if (empty($selectedIds)) {
            return $layanans->whereIn('id', [])->values();
        }

        $keepIds = collect($selectedIds);
        $byId = $layanans->keyBy('id');

        foreach ($selectedIds as $id) {
            $parent = $byId->get($id)?->parent;
            $guard = 0;

            while ($parent && $guard < 10) {
                $keepIds->push($parent->id);
                $parent = $parent->parent;
                $guard++;
            }
        }

        return $layanans
            ->whereIn('id', $keepIds->unique()->all())
            ->values();
    }
}
