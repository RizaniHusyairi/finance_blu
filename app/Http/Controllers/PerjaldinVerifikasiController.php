<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\LogStatusDokumen;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PerjaldinVerifikasiController extends Controller
{
    /**
     * Dashboard PPK - tampilkan tagihan perjaldin yang status bukan DRAFT
     */
    public function ppkIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_ppk.index', compact('tagihans'));
    }

    /**
     * Dashboard Kasubag - tampilkan tagihan perjaldin yang status bukan DRAFT
     */
    public function kasubagIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_kasubag.index', compact('tagihans'));
    }

    /**
     * Approve - PPK menyetujui tagihan perjaldin
     */
    public function approve($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user = Auth::user();

        if (!in_array($tagihan->status, ['PENDING_PPK'])) {
            return redirect()->back()->withErrors(['error' => 'Tagihan ini tidak dalam status menunggu persetujuan.']);
        }

        $statusLama = $tagihan->status;
        $tagihan->update([
            'status' => 'DISETUJUI_PPK',
            'diverifikasi_ppk_id' => $user->id,
            'waktu_verifikasi_ppk' => now(),
        ]);

        LogStatusDokumen::create([
            'dokumen_type' => Tagihan::class,
            'dokumen_id' => $tagihan->id,
            'status_lama' => $statusLama,
            'status_baru' => 'DISETUJUI_PPK',
            'diubah_oleh' => $user->id,
            'catatan' => 'Disetujui oleh PPK.',
        ]);

        // Notifikasi ke Operator
        try {
            $operators = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title' => 'Perjaldin Disetujui PPK',
                'message' => "Tagihan '{$tagihan->deskripsi}' telah disetujui PPK.",
                'url' => route('perjaldins.index'),
                'icon' => 'check_circle',
                'color' => 'success'
            ]));
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', 'Persetujuan PPK berhasil disimpan.');
    }

    /**
     * Revisi - PPK mengembalikan tagihan untuk direvisi
     */
    public function revisi(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string',
        ]);

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user = Auth::user();

        $statusLama = $tagihan->status;
        $tagihan->update([
            'status' => 'DITOLAK_PPK',
        ]);

        LogStatusDokumen::create([
            'dokumen_type' => Tagihan::class,
            'dokumen_id' => $tagihan->id,
            'status_lama' => $statusLama,
            'status_baru' => 'DITOLAK_PPK',
            'diubah_oleh' => $user->id,
            'catatan' => $request->catatan_revisi,
        ]);

        // Notifikasi ke Operator
        try {
            $operatorPerjaldin = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operatorPerjaldin, new \App\Notifications\WorkflowNotification([
                'title' => 'Revisi dari PPK',
                'message' => "Tagihan Perjaldin dikembalikan. Catatan: {$request->catatan_revisi}",
                'url' => route('perjaldins.index'),
                'icon' => 'error',
                'color' => 'danger'
            ]));
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', "Data berhasil dikembalikan untuk revisi. Operator Perjaldin telah dinotifikasi.");
    }
}
