<?php

namespace App\Http\Controllers;

use App\Models\TagihanPerjaldinKomponen;
use App\Services\PerjaldinKomponenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerjaldinKomponenController extends Controller
{
    protected PerjaldinKomponenService $service;

    public function __construct(PerjaldinKomponenService $service)
    {
        $this->service = $service;
    }

    /**
     * Update COA (dipa_revision_item_id) untuk satu komponen.
     */
    public function updateCoa(Request $request, $komponen_id)
    {
        $komponen = TagihanPerjaldinKomponen::findOrFail($komponen_id);

        $request->validate([
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
        ]);

        try {
            DB::beginTransaction();
            $this->service->updateKomponenCoa($komponen, $request->dipa_revision_item_id);
            DB::commit();

            return redirect()
                ->route('perjaldins.show', $komponen->tagihan_id)
                ->with('success', "COA untuk komponen {$komponen->nama_komponen} berhasil disimpan.");
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Buat DokumenSpp dari satu komponen.
     */
    public function createSpp(Request $request, $komponen_id)
    {
        $komponen = TagihanPerjaldinKomponen::findOrFail($komponen_id);

        try {
            DB::beginTransaction();
            $spp = $this->service->createSppFromKomponen($komponen, auth()->id());
            DB::commit();

            return redirect()
                ->route('perjaldins.show', $komponen->tagihan_id)
                ->with('success', "SPP untuk komponen {$komponen->nama_komponen} berhasil dibuat. Nomor SPP: {$spp->nomor_spp}");
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
