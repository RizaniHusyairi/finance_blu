<?php

namespace App\Http\Controllers;

use App\Models\Spp;
use App\Models\User;
use Illuminate\Http\Request;

class SpmVerifikasiController extends Controller
{
    /**
     * Tampilkan daftar SPM yang perlu diverifikasi PPSPM
     */
    public function index()
    {
        $spms = Spp::with('sppable')
            ->whereIn('status_spp', ['Menunggu Verifikasi SPM', 'Revisi SPM', 'SPM Terbit'])
            ->orderByRaw("CASE WHEN status_spp = 'Menunggu Verifikasi SPM' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        return view('verifikasi_ppspm.spm_index', compact('spms'));
    }

    /**
     * Setujui SPM
     */
    public function approve($spp_id)
    {
        $spp = Spp::findOrFail($spp_id);
        
        $spp->update([
            'status_spp' => 'SPM Terbit',
            'catatan_revisi' => null
        ]);

        $operators = User::role('Operator BLU')->get();
        \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
            'title' => 'SPM Valid!',
            'message' => "SPM Nomor {$spp->nomor_spm} ditandatangani dan terbit.",
            'url' => route('spms.index'),
            'icon' => 'task_alt',
            'color' => 'success'
        ]));

        return back()->with('success', "Dokumen SPM Sah diterbitkan!");
    }

    /**
     * Kembalikan SPM ke Operator dengan catatan revisi
     */
    public function revisi(Request $request, $spp_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000'
        ]);

        $spp = Spp::findOrFail($spp_id);

        $spp->update([
            'status_spp' => 'Revisi SPM',
            'catatan_revisi' => $request->catatan_revisi 
        ]);

        $operators = User::role('Operator BLU')->get();
        \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
            'title' => 'Revisi SPM (Ditolak)',
            'message' => "PPSPM merevisi SPM {$spp->nomor_spm}: {$request->catatan_revisi}",
            'url' => route('spms.index'),
            'icon' => 'error',
            'color' => 'danger'
        ]));

        return back()->with('success', "SPM dikembalikan ke Operator untuk diperbaiki.");
    }
}
