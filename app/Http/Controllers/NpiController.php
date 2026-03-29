<?php

namespace App\Http\Controllers;

use App\Models\Perjaldin;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Http\Request;

class NpiController extends Controller
{
    /**
     * Daftar Perjaldin untuk Bendahara Pengeluaran membuat NPI.
     */
    public function index()
    {
        $perjaldins = Perjaldin::with(['pejabats', 'spps'])
            ->whereHas('spps', function ($q) {
                $q->whereIn('status_spp', [
                    'SPM Terbit',
                    'Menunggu TTD Bendahara Penerimaan',
                    'Menunggu Verifikasi PPK NPI',
                    'Revisi NPI',
                    'NPI Terbit',
                ]);
            })
            ->latest()
            ->get();

        return view('npis.index', compact('perjaldins'));
    }

    /**
     * Detail satu Perjaldin — form buat/edit NPI per SPM.
     */
    public function detail($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps'])->findOrFail($perjaldin_id);
        return view('npis.detail_perjaldin', compact('perjaldin'));
    }

    /**
     * Bendahara Pengeluaran Simpan NPI → Kirim ke Bendahara Penerimaan untuk TTD.
     */
    public function store(Request $request, $spp_id)
    {
        $spp = Spp::findOrFail($spp_id);
        $request->validate([
            'nomor_npi'   => 'required|string|max:255',
            'tanggal_npi' => 'required|date',
        ]);

        $spp->update([
            'nomor_npi'      => $request->nomor_npi,
            'tanggal_npi'    => $request->tanggal_npi,
            'status_spp'     => 'Menunggu TTD Bendahara Penerimaan',
            'catatan_revisi' => null,
        ]);

        // Notifikasi ke Bendahara Penerimaan
        $penerima = User::role('Bendahara Penerimaan')->get();
        \Illuminate\Support\Facades\Notification::send($penerima, new \App\Notifications\WorkflowNotification([
            'title'   => 'NPI Menunggu TTD Anda',
            'message' => "NPI {$request->nomor_npi} menunggu tanda tangan Bendahara Penerimaan.",
            'url'     => route('verifikasi-bendahara-penerimaan.npi.index'),
            'icon'    => 'how_to_reg',
            'color'   => 'warning',
        ]));

        return back()->with('success', "NPI berhasil dibuat dan dikirim ke Bendahara Penerimaan untuk tanda tangan.");
    }

    // =========================================================
    // VERIFIKASI — BENDAHARA PENERIMAAN
    // =========================================================

    /**
     * Dashboard verifikasi NPI untuk Bendahara Penerimaan.
     */
    public function penerimaaIndex()
    {
        $spms = Spp::with('sppable')
            ->whereIn('status_spp', ['Menunggu TTD Bendahara Penerimaan', 'Menunggu Verifikasi PPK NPI', 'NPI Terbit'])
            ->orderByRaw("CASE WHEN status_spp = 'Menunggu TTD Bendahara Penerimaan' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        return view('verifikasi_bendahara_penerimaan.npi_index', compact('spms'));
    }

    /**
     * Bendahara Penerimaan Menyetujui (TTD) → lanjut ke PPK.
     */
    public function approvePenerimaan($spp_id)
    {
        $spp = Spp::findOrFail($spp_id);
        $spp->update(['status_spp' => 'Menunggu Verifikasi PPK NPI']);

        // Notifikasi ke PPK
        $ppks = User::role('PPK')->get();
        \Illuminate\Support\Facades\Notification::send($ppks, new \App\Notifications\WorkflowNotification([
            'title'   => 'NPI Menunggu Persetujuan PPK',
            'message' => "NPI {$spp->nomor_npi} sudah di-TTD Bendahara Penerimaan, menunggu persetujuan PPK.",
            'url'     => route('verifikasi-ppk.npi.index'),
            'icon'    => 'account_balance',
            'color'   => 'warning',
        ]));

        return back()->with('success', "NPI berhasil di-TTD. Dokumen diteruskan ke PPK untuk persetujuan akhir.");
    }

    // =========================================================
    // VERIFIKASI — PPK (Final Approval)
    // =========================================================

    /**
     * Dashboard verifikasi NPI untuk PPK.
     */
    public function verifikasiIndex()
    {
        $spms = Spp::with('sppable')
            ->whereIn('status_spp', ['Menunggu Verifikasi PPK NPI', 'Revisi NPI', 'NPI Terbit', 'SP2D Terbit', 'Lunas'])
            ->orderByRaw("CASE WHEN status_spp = 'Menunggu Verifikasi PPK NPI' THEN 1 WHEN status_spp = 'Revisi NPI' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        return view('verifikasi_ppk.npi_index', compact('spms'));
    }

    /**
     * PPK Menyetujui NPI → NPI Terbit.
     */
    public function approve($spp_id)
    {
        $spp = Spp::findOrFail($spp_id);
        $spp->update([
            'status_spp'     => 'NPI Terbit',
            'catatan_revisi' => null,
        ]);

        // Notifikasi ke Bendahara Pengeluaran
        $bendaharas = User::role('Bendahara Pengeluaran')->get();
        \Illuminate\Support\Facades\Notification::send($bendaharas, new \App\Notifications\WorkflowNotification([
            'title'   => 'NPI Disetujui PPK!',
            'message' => "NPI {$spp->nomor_npi} telah disetujui PPK. Silakan lanjutkan ke pencatatan SP2D.",
            'url'     => route('sp2ds.index'),
            'icon'    => 'task_alt',
            'color'   => 'success',
        ]));

        return back()->with('success', "NPI disetujui oleh PPK. Bendahara dapat melanjutkan ke pencatatan SP2D.");
    }

    /**
     * PPK Menolak NPI → kembalikan ke Bendahara Pengeluaran.
     */
    public function revisi(Request $request, $spp_id)
    {
        $request->validate(['catatan_revisi' => 'required|string|max:1000']);
        $spp = Spp::findOrFail($spp_id);

        $spp->update([
            'status_spp'     => 'Revisi NPI',
            'catatan_revisi' => $request->catatan_revisi,
        ]);

        $bendaharas = User::role('Bendahara Pengeluaran')->get();
        \Illuminate\Support\Facades\Notification::send($bendaharas, new \App\Notifications\WorkflowNotification([
            'title'   => 'NPI Dikembalikan PPK',
            'message' => "NPI {$spp->nomor_npi} perlu diperbaiki: {$request->catatan_revisi}",
            'url'     => route('npis.index'),
            'icon'    => 'error',
            'color'   => 'danger',
        ]));

        return back()->with('success', "NPI dikembalikan ke Bendahara Pengeluaran untuk diperbaiki.");
    }

    /**
     * Cetak dokumen NPI ke format PDF.
     */
    public function cetakPdf($spp_id)
    {
        $spp = Spp::findOrFail($spp_id);

        if (!$spp->nomor_npi) $spp->nomor_npi = 'NPI-BLU/APTP-' . date('Y') . '/DRAFT';
        if (!$spp->tanggal_npi) $spp->tanggal_npi = now()->toDateString();

        $jumlahUang = $spp->jumlah_uang;
        $terbilang  = terbilang_rupiah($jumlahUang);

        $bendaharaPengeluaran = User::role('Bendahara Pengeluaran')->first();
        $bendaharaPenerimaan  = User::role('Bendahara Penerimaan')->first();

        $penandatanganPengeluaran = $bendaharaPengeluaran->name ?? 'BENDAHARA PENGELUARAN';
        $nipPengeluaran           = '-';
        $penandatanganPenerimaan  = $bendaharaPenerimaan->name ?? 'BENDAHARA PENERIMAAN';
        $nipPenerimaan            = '-';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('npis.pdf', compact(
            'spp', 'jumlahUang', 'terbilang',
            'penandatanganPengeluaran', 'nipPengeluaran',
            'penandatanganPenerimaan', 'nipPenerimaan'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('NPI-BLU-' . str_replace('/', '-', $spp->nomor_npi) . '.pdf');
    }
}
