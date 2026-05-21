<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MitraJasa;
use App\Models\LayananJasa;
use App\Models\LaporanUtilitas;
use Illuminate\Support\Facades\DB;

class UtilitasController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        // Cek apakah user adalah Admin Listrik atau Admin Air
        $jenis = $user->hasRole('Admin Listrik') ? 'listrik' : 'air';
        $layananKeyword = $jenis === 'listrik' ? 'Listrik' : 'Air';

        // Cari ID layanan yang sesuai (hardcode 439/440 bisa berbahaya kalau ID berubah, mending pakai string match)
        $layanan = LayananJasa::where('nama_layanan', 'like', "%{$layananKeyword}%")->first();

        if (!$layanan) {
            return back()->with('error', "Layanan Jasa untuk {$jenis} tidak ditemukan di master data.");
        }

        // Cari mitra yang melanggan layanan ini
        $mitras = MitraJasa::whereHas('layananJasa', function ($q) use ($layanan) {
            $q->where('layanan_jasa_id', $layanan->id)
              ->where('mitra_jasa_layanan.status_aktif', true);
        })->get();

        // Ambil riwayat laporan utilitas
        $laporans = LaporanUtilitas::with('mitraJasa')
            ->where('jenis', $jenis)
            ->latest()
            ->paginate(15);

        return view('utilitas.dashboard', compact('jenis', 'layanan', 'mitras', 'laporans'));
    }

    public function store(Request $request)
    {
        $rules = [
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
            'layanan_jasa_id' => 'required|exists:layanan_jasas,id',
            'jenis' => 'required|in:listrik,air',
            'tipe_perhitungan' => 'required|in:kwh,flat',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020',
            'file_bukti' => 'nullable|image|max:5120', // max 5MB
        ];

        // Validasi conditional berdasarkan tipe
        if ($request->tipe_perhitungan === 'kwh') {
            // kWh: stan awal & akhir (manual meter reading) + 2 foto bukti
            $rules['stan_awal'] = 'required|integer|min:0';
            $rules['stan_akhir'] = 'required|integer|gte:stan_awal';
            $rules['file_bukti_awal'] = 'required|image|max:5120';
            $rules['file_bukti'] = 'required|image|max:5120';
        } else {
            // Flat: input pemakaian langsung
            $rules['pemakaian_manual'] = 'required|numeric|min:0';
        }

        $request->validate($rules);

        // Cek overlap
        $exists = LaporanUtilitas::where('mitra_jasa_id', $request->mitra_jasa_id)
            ->where('layanan_jasa_id', $request->layanan_jasa_id)
            ->where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Laporan untuk bulan & tahun ini sudah ada.')->withInput();
        }

        // Hitung pemakaian
        if ($request->tipe_perhitungan === 'kwh') {
            $pemakaian = $request->stan_akhir - $request->stan_awal;
        } else {
            $pemakaian = $request->pemakaian_manual;
        }

        // Upload file bukti jika ada
        $fileBuktiAwal = null;
        if ($request->hasFile('file_bukti_awal')) {
            $fileBuktiAwal = $request->file('file_bukti_awal')->store('bukti-utilitas', 'public');
        }

        $fileBukti = null;
        if ($request->hasFile('file_bukti')) {
            $fileBukti = $request->file('file_bukti')->store('bukti-utilitas', 'public');
        }

        LaporanUtilitas::create([
            'mitra_jasa_id' => $request->mitra_jasa_id,
            'layanan_jasa_id' => $request->layanan_jasa_id,
            'jenis' => $request->jenis,
            'tipe_perhitungan' => $request->tipe_perhitungan,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'stan_awal' => $request->tipe_perhitungan === 'kwh' ? $request->stan_awal : null,
            'stan_akhir' => $request->tipe_perhitungan === 'kwh' ? $request->stan_akhir : null,
            'file_bukti_awal' => $fileBuktiAwal,
            'file_bukti' => $fileBukti,
            'pemakaian' => $pemakaian,
            // tarif_per_unit dan total_biaya diisi oleh Admin Jasa nanti
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Laporan pemakaian berhasil disimpan.');
    }

    public function submit(Request $request, $id)
    {
        $laporan = LaporanUtilitas::findOrFail($id);
        
        if ($laporan->status !== 'draft' && $laporan->status !== 'ditolak') {
            return back()->with('error', 'Hanya laporan draft/revisi yang bisa dikirim.');
        }

        $laporan->update(['status' => 'dikirim_ke_admin_jasa']);
        return back()->with('success', 'Laporan berhasil dikirim ke Admin Jasa untuk ditagihkan.');
    }

    public function destroy($id)
    {
        $laporan = LaporanUtilitas::findOrFail($id);
        
        if ($laporan->status !== 'draft' && $laporan->status !== 'ditolak') {
            return back()->with('error', 'Laporan yang sudah dikirim tidak bisa dihapus.');
        }

        $laporan->delete();
        return back()->with('success', 'Laporan berhasil dihapus.');
    }

    public function getLastStanAkhir(Request $request)
    {
        $request->validate([
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
            'layanan_jasa_id' => 'required|exists:layanan_jasas,id',
            'bulan' => 'required|integer',
            'tahun' => 'required|integer',
        ]);

        // Get the latest reading before the current period
        $lastReport = LaporanUtilitas::where('mitra_jasa_id', $request->mitra_jasa_id)
            ->where('layanan_jasa_id', $request->layanan_jasa_id)
            ->where('tipe_perhitungan', 'kwh')
            ->where(function($q) use ($request) {
                $q->where('tahun', '<', $request->tahun)
                  ->orWhere(function($sq) use ($request) {
                      $sq->where('tahun', $request->tahun)
                         ->where('bulan', '<', $request->bulan);
                  });
            })
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();

        return response()->json([
            'stan_akhir' => $lastReport ? $lastReport->stan_akhir : 0
        ]);
    }
}
