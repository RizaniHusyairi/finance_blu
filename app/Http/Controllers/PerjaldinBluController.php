<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pejabat;

class PerjaldinBluController extends Controller
{
    /**
     * Display a listing of the submitted Perjaldin (for BLU Operator approval).
     */
    public function index()
    {
        // Hanya tampilkan Pejabat yang statusnya "Diajukan"
        $pejabats = Pejabat::where('status', 'Diajukan')
                           ->with(['perjaldin', 'employee'])
                           ->get();
                           
        return view('perjaldin_blu.index', compact('pejabats'));
    }

    /**
     * Display a listing of past verifications (History Log)
     */
    public function history()
    {
        // Tampilkan Pejabat yang sudah diverifikasi (Disetujui PPK atau Ditolak)
        $pejabats = Pejabat::whereIn('status', ['Disetujui PPK', 'Ditolak'])
                           ->with(['perjaldin', 'employee'])
                           ->orderBy('updated_at', 'desc')
                           ->get();
                           
        return view('perjaldin_blu.history', compact('pejabats'));
    }

    /**
     * Display the specified Pejabat record for details.
     */
    public function show($id)
    {
        $pejabat = Pejabat::with(['perjaldin', 'employee'])->findOrFail($id);
        // We reuse the existing show.blade.php view since it's just a read-only detail page
        return view('perjaldins.show', compact('pejabat'));
    }

    /**
     * Approve the specified Perjaldin/Pejabat.
     */
    public function approve($id)
    {
        $pejabat = Pejabat::findOrFail($id);
        
        $pejabat->update([
            'status' => 'Disetujui PPK',
            'alasan_penolakan' => null, // clear any previous rejection reason
        ]);

        return redirect()->back()->with('success', 'Data Perjalanan Dinas atas nama ' . $pejabat->nama_pejabat . ' berhasil Disetujui!');
    }

    /**
     * Reject the specified Perjaldin/Pejabat.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'alasan_penolakan' => 'required|string',
        ]);

        $pejabat = Pejabat::findOrFail($id);
        
        $pejabat->update([
            'status' => 'Ditolak',
            'alasan_penolakan' => $request->alasan_penolakan,
        ]);

        return redirect()->back()->with('success', 'Data Perjalanan Dinas atas nama ' . $pejabat->nama_pejabat . ' berhasil Ditolak.');
    }
}
