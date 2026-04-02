<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\Spp;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SppVerifikasiController extends Controller
{
    /**
     * Tampilkan daftar SPP (untuk semua kategori) yang perlu diverifikasi PPK
     */
    public function sppIndex()
    {
        $spps = Spp::with(['tagihan', 'spm'])
            ->orderByRaw(
                "CASE 
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 2
                    ELSE 3
                END",
                ['Menunggu Verifikasi', 'Revisi']
            )
            ->latest()
            ->get();

        return view('verifikasi_ppk.spp_index', compact('spps'));
    }

    /**
     * Setujui SPP
     */
    public function approveSpp($spp_id)
    {
        $spp = Spp::with('tagihan')->findOrFail($spp_id);

        if ($spp->status !== 'Menunggu Verifikasi') {
            return back()->with('warning', "SPP {$spp->nomor_spp} tidak sedang menunggu verifikasi.");
        }
        
        $spp->update([
            'status' => 'Disetujui PPK',
        ]);

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => 'Disetujui PPK',
            'aksi' => 'APPROVE_PPK',
            'catatan' => 'Dokumen SPP disetujui oleh PPK.',
            'ip_address' => request()->ip(),
        ]);

        $operators = User::role('Operator BLU')->get();
        Notification::send($operators, new WorkflowNotification([
            'title' => 'SPP Disetujui',
            'message' => "SPP {$spp->nomor_spp} telah disetujui PPK.",
            'url' => $this->resolveOperatorDetailRoute($spp),
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

        $spp = Spp::with('tagihan')->findOrFail($spp_id);

        if ($spp->status !== 'Menunggu Verifikasi') {
            return back()->with('warning', "SPP {$spp->nomor_spp} tidak sedang menunggu verifikasi.");
        }

        $spp->update([
            'status' => 'Revisi',
        ]);

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpp::class,
            'dokumen_id' => $spp->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => 'Menunggu Verifikasi',
            'status_baru' => 'Revisi',
            'aksi' => 'REVISI_PPK',
            'catatan' => $request->catatan_revisi,
            'ip_address' => request()->ip(),
        ]);

        $operators = User::role('Operator BLU')->get();
        Notification::send($operators, new WorkflowNotification([
            'title' => 'SPP Direvisi PPK',
            'message' => "SPP {$spp->nomor_spp} perlu revisi. Catatan: {$request->catatan_revisi}",
            'url' => $this->resolveOperatorDetailRoute($spp),
            'icon' => 'error_outline',
            'color' => 'danger'
        ]));

        return back()->with('warning', "Catatan revisi untuk SPP {$spp->nomor_spp} telah dikirim ke Operator BLU.");
    }

    private function resolveOperatorDetailRoute(Spp $spp): string
    {
        $tagihan = $spp->tagihan;

        if (!$tagihan) {
            return route('spps.kontrak.index');
        }

        return match ($tagihan->tipe_tagihan) {
            'PERJALDIN' => route('spps.perjaldin.detail', $tagihan->id),
            'HONORARIUM' => route('spps.honor.detail', $tagihan->id),
            'KONTRAK' => route('spps.kontrak.detail', $tagihan->id),
            default => route('verifikasi-ppk.spp.index'),
        };
    }
}
