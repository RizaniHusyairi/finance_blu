<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\LogStatusDokumen;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PerjaldinVerifikasiController extends Controller
{
    public function ppkIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_ppk.index', compact('tagihans'));
    }

    public function kasubagIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_kasubag.index', compact('tagihans'));
    }

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
        ]);

        LogStatusDokumen::create([
            'dokumen_type' => Tagihan::class,
            'dokumen_id' => $tagihan->id,
            'user_id' => $user->id,
            'role_saat_itu' => $user->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => $statusLama,
            'status_baru' => 'DISETUJUI_PPK',
            'aksi' => 'APPROVE',
            'catatan' => 'Disetujui oleh PPK.',
            'ip_address' => request()->ip(),
        ]);

        try {
            $operators = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title' => 'Perjaldin Disetujui PPK',
                'message' => "Tagihan '{$tagihan->deskripsi}' telah disetujui PPK.",
                'url' => route('perjaldins.index'),
                'icon' => 'check_circle',
                'color' => 'success'
            ]));
        } catch (\Exception $e) {
        }

        return redirect()->back()->with('success', 'Persetujuan PPK berhasil disimpan.');
    }

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
            'user_id' => $user->id,
            'role_saat_itu' => $user->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => $statusLama,
            'status_baru' => 'DITOLAK_PPK',
            'aksi' => 'REJECT',
            'catatan' => $request->catatan_revisi,
            'ip_address' => request()->ip(),
        ]);

        try {
            $operatorPerjaldin = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operatorPerjaldin, new \App\Notifications\WorkflowNotification([
                'title' => 'Revisi dari PPK',
                'message' => "Tagihan Perjaldin dikembalikan. Catatan: {$request->catatan_revisi}",
                'url' => route('perjaldins.index'),
                'icon' => 'error',
                'color' => 'danger'
            ]));
        } catch (\Exception $e) {
        }

        return redirect()->back()->with('success', 'Data berhasil dikembalikan untuk revisi. Operator Perjaldin telah dinotifikasi.');
    }
}
