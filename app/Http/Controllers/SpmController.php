<?php

namespace App\Http\Controllers;

use App\Models\Perjaldin;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Http\Request;

class SpmController extends Controller
{
    /**
     * Tampilkan daftar Perjaldin yang SPP-nya sudah siap dibuatkan SPM.
     * (Sama persis seperti perjaldin_index untuk SPP)
     */
    public function index()
    {
        // Ambil Perjaldin yang punya setidaknya 1 SPP dengan status relevan untuk SPM
        $perjaldins = Perjaldin::with(['pejabats', 'spps'])
            ->whereHas('spps', function ($q) {
                $q->whereIn('status_spp', [
                    'Disetujui PPK', 
                    'Menunggu Verifikasi SPM', 
                    'Revisi SPM', 
                    'SPM Terbit',
                    'Menunggu TTD Bendahara Penerimaan',
                    'Menunggu Verifikasi PPK NPI',
                    'Revisi NPI',
                    'NPI Terbit',
                    'SP2D Terbit',
                    'Lunas'
                ]);
            })
            ->latest()
            ->get();

        return view('spms.index', compact('perjaldins'));
    }

    /**
     * Tampilkan detail satu Perjaldin dengan daftar SPP-nya untuk dibuatkan SPM.
     * (Sama seperti pola SppController@detailPerjaldin)
     */
    public function detail($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps'])->findOrFail($perjaldin_id);
        $ppspms = User::role('PPSPM')->get();

        return view('spms.detail_perjaldin', compact('perjaldin', 'ppspms'));
    }

    /**
     * Simpan/Update SPM ke Database dari halaman detail.
     */
    public function store(Request $request, $spp_id)
    {
        $spp = Spp::findOrFail($spp_id);

        $request->validate([
            'nomor_spm'  => 'required|string|max:255',
            'tanggal_spm' => 'required|date',
            'ppspm_id'   => 'required|exists:users,id',
        ]);

        $ppspm = User::findOrFail($request->ppspm_id);

        $spp->update([
            'nomor_spm'              => $request->nomor_spm,
            'tanggal_spm'            => $request->tanggal_spm,
            'penandatangan_spm_nama' => $ppspm->name,
            'penandatangan_spm_nip'  => '-',
            'status_spp'             => 'Menunggu Verifikasi SPM'
        ]);

        $ppspmUsers = User::role('PPSPM')->get();
        \Illuminate\Support\Facades\Notification::send($ppspmUsers, new \App\Notifications\WorkflowNotification([
            'title'   => 'Verifikasi SPM Baru',
            'message' => "Ada SPM {$request->nomor_spm} yang butuh tanda tangan PPSPM.",
            'url'     => route('verifikasi-ppspm.spm.index'),
            'icon'    => 'fact_check',
            'color'   => 'warning'
        ]));

        return back()->with('success', "SPM berhasil dikirim ke meja PPSPM untuk diverifikasi.");
    }

    /**
     * Cetak dokumen SPM ke format PDF.
     */
    public function cetakPdfSpm($spp_id)
    {
        $spp = Spp::findOrFail($spp_id);

        $sppable = $spp->sppable;
        $jumlahUang = $spp->jumlah_uang;
        $uraianSupplier = $spp->uraian ?? ($sppable->uraian ?? 'Belanja Perjalanan Dinas');

        $terbilang = terbilang_rupiah($jumlahUang);

        // Gunakan fallback jika data SPM belum lengkap (data lama)
        if (!$spp->nomor_spm)           $spp->nomor_spm = 'SPM-BLU/APTP-' . date('Y') . '/DRAFT';
        if (!$spp->tanggal_spm)         $spp->tanggal_spm = now()->toDateString();
        if (!$spp->penandatangan_spm_nama) $spp->penandatangan_spm_nama = 'PEJABAT BERWENANG';
        if (!$spp->penandatangan_spm_nip)  $spp->penandatangan_spm_nip = '-';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('spms.pdf', compact('spp', 'sppable', 'jumlahUang', 'terbilang', 'uraianSupplier'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPM-BLU-' . str_replace('/', '-', $spp->nomor_spm) . '.pdf');
    }
}
