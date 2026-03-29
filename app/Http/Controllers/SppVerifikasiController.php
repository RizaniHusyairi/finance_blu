<?php

namespace App\Http\Controllers;

use App\Models\Spp;
use Illuminate\Http\Request;

class SppVerifikasiController extends Controller
{
    /**
     * Tampilkan daftar SPP (untuk semua kategori) yang perlu diverifikasi PPK
     */
    public function sppIndex()
    {
        // Ambil SPP yang statusnya Menunggu Verifikasi atau sudah Disetujui PPK/Revisi
        // Filter by latest first
        $spps = Spp::with('sppable')
            ->orderByRaw("CASE WHEN status_spp = 'Menunggu Verifikasi' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        return view('verifikasi_ppk.spp_index', compact('spps'));
    }

    /**
     * Setujui SPP
     */
    public function approveSpp($spp_id)
    {
        $spp = Spp::findOrFail($spp_id);
        
        $spp->update([
            'status_spp' => 'Disetujui PPK',
            'catatan_revisi' => null // bersihkan catatan revisi jika ada sebelumnya
        ]);

        $operators = \App\Models\User::role('Operator BLU')->get();
        \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
            'title' => 'SPP Disetujui',
            'message' => "SPP Kategori {$spp->kategori_biaya} telah disetujui PPK.",
            'url' => route('spps.perjaldin.detail', $spp->sppable_id),
            'icon' => 'verified',
            'color' => 'success'
        ]));

        return back()->with('success', "SPP Nomor {$spp->nomor_spp} berhasil disetujui.");
    }

    /**
     * Kembalikan SPP ke Operator dengan catatan revisi
     */
    public function revisiSpp(Request $request, $spp_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000'
        ]);

        $spp = Spp::findOrFail($spp_id);

        $spp->update([
            'status_spp' => 'Revisi',
            'catatan_revisi' => $request->catatan_revisi
        ]);

        $operators = \App\Models\User::role('Operator BLU')->get();
        \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
            'title' => 'SPP Direvisi PPK',
            'message' => "Catatan: {$request->catatan_revisi}",
            'url' => route('spps.perjaldin.detail', $spp->sppable_id),
            'icon' => 'error_outline',
            'color' => 'danger'
        ]));

        return back()->with('warning', "Catatan revisi untuk SPP {$spp->nomor_spp} telah dikirim ke Operator BLU.");
    }
}
