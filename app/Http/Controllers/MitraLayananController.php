<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Services\MitraLayananService;
use Illuminate\Http\Request;

class MitraLayananController extends Controller
{
    public function edit(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        $layanans = LayananJasa::where('is_active', true)->orderBy('level')->orderBy('id')->get();
        $selectedIds = $mitra->layananJasa()
            ->wherePivot('status_aktif', true)
            ->pluck('layanan_jasas.id')
            ->all();

        return view('jasa_assignments.mitra-layanan', compact('mitra', 'layanans', 'selectedIds'));
    }

    public function update(Request $request, MitraJasa $mitra, MitraLayananService $service)
    {
        $this->abortUnlessCanManageMitraMaster();

        $validated = $request->validate([
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan_jasas,id'],
        ]);

        $service->sync($mitra, $validated['layanan_ids'] ?? [], auth()->id());

        return back()->with('success', 'Pengaturan layanan mitra berhasil disimpan.');
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
