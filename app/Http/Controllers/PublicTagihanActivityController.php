<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use Illuminate\Http\Request;

class PublicTagihanActivityController extends Controller
{
    /**
     * Halaman publik (signed URL) yang menampilkan rekam jejak aktivitas
     * tagihan: verifikasi → SPP → SPM → NPI → SP2D, beserta status verifikator.
     * Dipanggil dari QR code di PDF SPP.
     */
    public function show(Request $request, $id)
    {
        $tagihan = Tagihan::with([
            'detailKontrak.termin.kontrak.vendor',
            'workflowInstance.approvals.actedByUser',
            'workflowInstance.approvals.assignedUser',
            'spps.workflowInstance.approvals.actedByUser',
            'spps.workflowInstance.approvals.assignedUser',
            'spps.spm.workflowInstance.approvals.actedByUser',
            'spps.spm.workflowInstance.approvals.assignedUser',
            'spps.spm.npi.workflowInstance.approvals.actedByUser',
            'spps.spm.npi.workflowInstance.approvals.assignedUser',
            'spps.spm.npi.sp2d.workflowInstance.approvals.actedByUser',
            'spps.spm.npi.sp2d.workflowInstance.approvals.assignedUser',
        ])->findOrFail($id);

        return view('public.tagihan-aktivitas', compact('tagihan'));
    }
}
