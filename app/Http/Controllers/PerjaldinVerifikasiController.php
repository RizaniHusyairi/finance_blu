<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perjaldin;
use App\Models\User;
use App\Notifications\PerjaldinRevisiNotification;
use Illuminate\Support\Facades\Auth;

class PerjaldinVerifikasiController extends Controller
{
    /**
     * Dashboard PPK - tampilkan perjaldin yang sedang "Proses Verifikasi"
     */
    public function ppkIndex()
    {
        $perjaldins = Perjaldin::where('status', '!=', 'Draft')
                               ->with('pejabats')
                               ->latest()
                               ->get();
        return view('verifikasi_ppk.index', compact('perjaldins'));
    }

    /**
     * Dashboard Kasubag - tampilkan perjaldin yang sedang "Proses Verifikasi"
     */
    public function kasubagIndex()
    {
        $perjaldins = Perjaldin::where('status', '!=', 'Draft')
                               ->with('pejabats')
                               ->latest()
                               ->get();
        return view('verifikasi_kasubag.index', compact('perjaldins'));
    }

    /**
     * Approve - PPK atau Kasubag menyetujui
     */
    public function approve($id)
    {
        $perjaldin = Perjaldin::findOrFail($id);
        $user = Auth::user();

        if ($user->hasRole('PPK')) {
            $perjaldin->is_ppk_approved = true;
            $approverRole = 'PPK';
        } elseif ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
            $perjaldin->is_kasubag_approved = true;
            $approverRole = 'Kasubag';
        } else {
            $approverRole = 'Sistem';
        }

        \App\Models\PerjaldinLog::create([
            'perjaldin_id' => $perjaldin->perjaldin_id,
            'user_name' => $user->name,
            'action' => 'Menyetujui',
            'catatan' => "Disetujui oleh {$approverRole}."
        ]);

        if ($perjaldin->is_ppk_approved && $perjaldin->is_kasubag_approved) {
            $perjaldin->status = 'Disetujui';
            
            // Beritahu Operator bahwa keduanya sudah disetujui
            $operators = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title' => 'Perjaldin Disetujui',
                'message' => "Perjaldin '{$perjaldin->uraian}' telah disetujui penuh.",
                'url' => route('perjaldins.index'),
                'icon' => 'check_circle',
                'color' => 'success'
            ]));
        }

        $perjaldin->save();

        return redirect()->back()->with('success', 'Persetujuan Anda berhasil disimpan.');
    }

    /**
     * Revisi - salah satu pihak mengembalikan untuk revisi
     */
    public function revisi(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string',
        ]);

        $perjaldin = Perjaldin::with('pejabats')->findOrFail($id);
        $user = Auth::user();

        // Tentukan nama role yang melakukan revisi
        if ($user->hasRole('PPK')) {
            $revisiOleh = 'PPK';
        } elseif ($user->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
            $revisiOleh = 'Kasubag';
        } else {
            $revisiOleh = $user->name;
        }

        // Reset approval flags & update status
        $perjaldin->update([
            'status'              => 'Revisi',
            'is_ppk_approved'     => false,
            'is_kasubag_approved' => false,
            'catatan_revisi'      => $request->catatan_revisi,
            'revisi_oleh'         => $revisiOleh,
        ]);

        \App\Models\PerjaldinLog::create([
            'perjaldin_id' => $perjaldin->perjaldin_id,
            'user_name' => $user->name,
            'action' => 'Merevisi',
            'catatan' => "Mengembalikan untuk direvisi. Catatan: {$request->catatan_revisi}"
        ]);

        // Kirim notifikasi ke semua user dengan role "Operator Perjaldin"
        $operatorPerjaldin = User::role('Operator Perjaldin')->get();
        \Illuminate\Support\Facades\Notification::send($operatorPerjaldin, new \App\Notifications\WorkflowNotification([
            'title' => "Revisi dari {$revisiOleh}",
            'message' => "Perjaldin '{$perjaldin->uraian}' dikembalikan. Catatan: {$request->catatan_revisi}",
            'url' => route('perjaldins.index'),
            'icon' => 'error',
            'color' => 'danger'
        ]));

        return redirect()->back()->with('success', "Data berhasil dikembalikan untuk revisi. Operator Perjaldin telah dinotifikasi.");
    }
}
