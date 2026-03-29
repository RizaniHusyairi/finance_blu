<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spp;
use App\Models\Perjaldin;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class SppController extends Controller
{
    /**
     * Menampilkan daftar Perjaldin yang sudah siap dibikinkan SPP
     */
    public function perjaldinIndex()
    {
        $perjaldins = Perjaldin::whereIn('status', ['Disetujui', 'Proses SPP', 'SPP Terbit'])
            ->with(['spps'])
            ->latest()
            ->get();
        return view('spps.perjaldin_index', compact('perjaldins'));
    }

    public function detailPerjaldin($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps'])->findOrFail($perjaldin_id);
        
        // Menghitung total tiap kategori
        $kategoriTotals = [
            'Tiket' => 0,
            'Transport' => 0,
            'Penginapan' => 0,
            'Uang Harian' => 0,
            'Uang Representasi' => 0,
        ];

        foreach($perjaldin->pejabats as $p) {
            $kategoriTotals['Tiket'] += $p->tiket;
            $kategoriTotals['Transport'] += $p->transport;
            $kategoriTotals['Penginapan'] += $p->penginapan;
            $kategoriTotals['Uang Harian'] += $p->uang_harian;
            $kategoriTotals['Uang Representasi'] += $p->uang_representasi;
        }

        $budgets = \App\Models\Budget::where('year', date('Y'))->get();

        return view('spps.detail_perjaldin', compact('perjaldin', 'kategoriTotals', 'budgets'));
    }

    public function storePerjaldin(Request $request, $perjaldin_id)
    {
        $request->validate([
            'kategori_biaya' => 'required|string',
            'jumlah_uang' => 'required|numeric',
            'nomor_spp' => 'required|string',
            'tanggal_spp' => 'required|date',
            'tahun_anggaran' => 'required|string',
            'nomor_dipa' => 'required|string',
            'tanggal_dipa' => 'required|date',
            'akun_mak' => 'required|string',
            'penandatangan_nama' => 'required|string',
            'penandatangan_nip' => 'required|string',
        ]);

        $perjaldin = Perjaldin::findOrFail($perjaldin_id);

        // Standard Uraian template based on PDF
        $uraianText = 'Belanja Barang Perjalanan Dinas Pegawai - ' . $request->kategori_biaya;

        DB::transaction(function () use ($request, $perjaldin, $uraianText) {
            // Polymorphic Create Spp untuk Kategori Tertentu
            $perjaldin->spps()->updateOrCreate(
                [
                    'kategori_biaya' => $request->kategori_biaya,
                ],
                [
                    'jumlah_uang' => $request->jumlah_uang,
                    'uraian' => $uraianText,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'tahun_anggaran' => $request->tahun_anggaran,
                    'nomor_dipa' => $request->nomor_dipa,
                    'tanggal_dipa' => $request->tanggal_dipa,
                    'akun_mak' => $request->akun_mak,
                    'penandatangan_nama' => $request->penandatangan_nama,
                    'penandatangan_nip' => $request->penandatangan_nip,
                    // Default values matching template
                    'jenis_tagihan' => 'NON REMUNERASI',
                    'jatuh_tempo' => 'Segera',
                    'cara_bayar' => 'SP2D BLU - TRF',
                    'status_spp' => 'Menunggu Verifikasi',
                    'catatan_revisi' => null,
                ]
            );

            // Update status perjaldin
            $semuaBuatSPP = true;
            // logic here could be added later if we want to track 'semua selesai'
            $perjaldin->update(['status' => 'Proses SPP']);
        });

        // Beritahu PPK ada SPP baru/diubah
        $ppks = \App\Models\User::role('PPK')->get();
        \Illuminate\Support\Facades\Notification::send($ppks, new \App\Notifications\WorkflowNotification([
            'title' => 'Pengajuan SPP',
            'message' => "Surat Permintaan Pembayaran ({$request->kategori_biaya}) menunggu verifikasi Anda.",
            'url' => route('verifikasi-ppk.spp.index'),
            'icon' => 'receipt_long',
            'color' => 'primary'
        ]));

        return redirect()->route('spps.perjaldin.detail', $perjaldin_id)->with('success', 'SPP '.$request->kategori_biaya.' berhasil diterbitkan. Silakan cetak PDF.');
    }

    public function cetakPdf($spp_id)
    {
        $spp = Spp::with('sppable')->findOrFail($spp_id);
        $sppable = $spp->sppable;
        
        $jumlahUang = $spp->jumlah_uang;
        $uraianSupplier = $spp->uraian;
        
        // Buat logic terbilang menggunakan helper rupiah
        $terbilang = terbilang_rupiah($jumlahUang);

        $pdf = Pdf::loadView('spps.pdf', compact('spp', 'sppable', 'jumlahUang', 'terbilang', 'uraianSupplier'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPP-BLU-' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
    }
}
