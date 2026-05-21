<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\User;
use App\Services\AdminJasaLayananService;
use Illuminate\Http\Request;

class AdminJasaLayananController extends Controller
{
    public function edit(Request $request, User $user)
    {
        abort_unless($user->hasRole('Admin Jasa'), 404);

        $tipe = $request->query('tipe', 'SEMUA');
        if (! in_array($tipe, ['SEMUA', 'PNBP', 'KONSESI'], true)) {
            $tipe = 'SEMUA';
        }

        $allLayanans = LayananJasa::where('is_active', true)->orderBy('level')->orderBy('id')->get();
        $selectedIds = $user->layananJasaDikelola()
            ->wherePivot('status_aktif', true)
            ->pluck('layanan_jasas.id')
            ->all();

        $counts = [
            'SEMUA' => $allLayanans->where('is_leaf', true)->count(),
            'PNBP' => $allLayanans->where('is_leaf', true)->where('tipe_layanan', 'PNBP')->count(),
            'KONSESI' => $allLayanans->where('is_leaf', true)->where('mendukung_konsesi', true)->count(),
        ];

        $layanans = $tipe === 'SEMUA'
            ? $allLayanans
            : $this->filterTreeByType($allLayanans, $tipe);

        $visibleIds = $layanans->pluck('id')->map(fn ($id) => (int) $id)->all();
        $hiddenSelectedIds = collect($selectedIds)
            ->map(fn ($id) => (int) $id)
            ->diff($visibleIds)
            ->values()
            ->all();

        return view('jasa_assignments.admin-layanan', compact(
            'user',
            'layanans',
            'selectedIds',
            'hiddenSelectedIds',
            'tipe',
            'counts'
        ));
    }

    public function update(Request $request, User $user, AdminJasaLayananService $service)
    {
        abort_unless($user->hasRole('Admin Jasa'), 404);

        $validated = $request->validate([
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan_jasas,id'],
        ]);

        $service->sync($user, $validated['layanan_ids'] ?? [], auth()->id());

        return back()->with('success', 'Pengaturan layanan admin jasa berhasil disimpan.');
    }

    private function filterTreeByType($layanans, string $tipe)
    {
        $selectedIds = $layanans
            ->filter(function (LayananJasa $layanan) use ($tipe) {
                if (! $layanan->is_leaf) {
                    return false;
                }

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
