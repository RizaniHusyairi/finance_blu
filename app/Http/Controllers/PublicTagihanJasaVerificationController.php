<?php

namespace App\Http\Controllers;

use App\Models\TagihanJasa;
use Illuminate\Http\Request;

class PublicTagihanJasaVerificationController extends Controller
{
    public function show(Request $request, $id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'creator',
            'suratPengantarSigner',
            'details.layananJasa',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);

        $expectedSeal = $tagihan->digitalSealHash();
        $providedSeal = (string) $request->query('seal', '');
        $isValid = hash_equals($expectedSeal, $providedSeal);

        return view('public.tagihan-jasa-verify', [
            'tagihan' => $tagihan,
            'mitraTagihan' => $tagihan->mitra ?? $tagihan->mitraLegacy,
            'isValid' => $isValid,
            'providedSeal' => $providedSeal,
            'expectedSeal' => $expectedSeal,
        ]);
    }
}
