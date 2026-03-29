<?php

namespace App\Http\Controllers;

use App\Models\Perjaldin;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Http\Request;

class Sp2dController extends Controller
{
    /**
     * Daftar Perjaldin yang NPI-nya sudah terbit - siap dicatat SP2D-nya.
     */
    public function index()
    {
        $perjaldins = Perjaldin::with(['pejabats', 'spps'])
            ->whereHas('spps', function ($q) {
                $q->whereIn('status_spp', ['NPI Terbit', 'SP2D Terbit', 'Lunas']);
            })
            ->latest()
            ->get();

        return view('sp2ds.index', compact('perjaldins'));
    }

    /**
     * Detail Perjaldin — tampilkan per-SPP untuk catat nomor SP2D.
     */
    public function detail($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps'])->findOrFail($perjaldin_id);
        return view('sp2ds.detail_perjaldin', compact('perjaldin'));
    }

    /**
     * Catat nomor SP2D dari Bank/KPPN.
     */
    public function store(Request $request, $spp_id)
    {
        $spp = Spp::findOrFail($spp_id);
        $request->validate([
            'nomor_sp2d'   => 'required|string|max:255',
            'tanggal_sp2d' => 'required|date',
        ]);

        $spp->update([
            'nomor_sp2d'   => $request->nomor_sp2d,
            'tanggal_sp2d' => $request->tanggal_sp2d,
            'status_spp'   => 'SP2D Terbit',
        ]);

        return back()->with('success', "Nomor SP2D berhasil dicatat. Silakan lakukan pencatatan BKU.");
    }

    /**
     * Catat realisasi ke BKU — tahap final.
     */
    public function catatBku(Request $request, $spp_id)
    {
        $spp = Spp::findOrFail($spp_id);

        // Validasi: Harus ada Nomor SP2D sebelum catat BKU
        if (!$spp->nomor_sp2d || !$spp->tanggal_sp2d) {
            return back()->with('error', "Gagal! Nomor dan Tanggal SP2D harus diisi terlebih dahulu sebelum mencatat BKU.");
        }

        $request->validate([
            'catatan_bku' => 'nullable|string|max:1000',
        ]);

        $spp->update([
            'status_spp'  => 'Lunas',
            'catatan_bku' => $request->catatan_bku ?? 'Realisasi dicatat ke BKU.',
        ]);

        // Notifikasi ke Operator Perjaldin bahwa pencairan selesai
        $operators = User::role('Operator Perjaldin')->get();
        \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
            'title'   => '✅ Dana Perjaldin Cair!',
            'message' => "SP2D {$spp->nomor_sp2d} telah dicatat ke BKU. Pencairan perjaldin selesai.",
            'url'     => route('perjaldins.index'),
            'icon'    => 'payments',
            'color'   => 'success',
        ]));

        return back()->with('success', "Pencatatan BKU selesai! Alur perjaldin untuk dokumen ini telah LUNAS.");
    }
}
