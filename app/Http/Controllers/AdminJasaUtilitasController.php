<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LaporanUtilitas;
use App\Models\TagihanJasa;
use App\Models\TagihanJasaDetail;
use Illuminate\Support\Facades\DB;

class AdminJasaUtilitasController extends Controller
{
    public function index(Request $request)
    {
        $query = LaporanUtilitas::with(['mitraJasa', 'layananJasa'])
            ->whereIn('status', ['dikirim_ke_admin_jasa', 'ditagihkan']);

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $laporans = $query->latest()->paginate(15);

        return view('super_admin_jasa.mitra.utilitas-index', compact('laporans'));
    }

    public function show($id)
    {
        $laporan = LaporanUtilitas::with(['mitraJasa', 'layananJasa', 'tagihanJasa', 'createdByUser'])
            ->findOrFail($id);

        return view('super_admin_jasa.mitra.utilitas-show', compact('laporan'));
    }

    public function buatTagihan(Request $request, $id)
    {
        $request->validate([
            'tarif_per_unit' => 'required|numeric|min:0',
            'total_biaya' => 'required|numeric|min:0',
        ]);

        $laporan = LaporanUtilitas::with(['mitraJasa', 'layananJasa'])->findOrFail($id);

        if ($laporan->status !== 'dikirim_ke_admin_jasa') {
            return back()->with('error', 'Laporan tidak valid untuk dibuatkan tagihan.');
        }

        $tarifPerUnit = $request->tarif_per_unit;
        $totalBiaya = $request->total_biaya;

        DB::beginTransaction();
        try {
            $nomorTagihan = 'TAG/UTIL/' . date('Ym') . '/' . str_pad(rand(1, 999), 4, '0', STR_PAD_LEFT);
            $jatuhTempo = now()->addDays(7);

            // Deskripsi berdasarkan tipe
            $satuanText = $laporan->jenis === 'listrik' ? 'kWh' : 'm³';
            if ($laporan->tipe_perhitungan === 'kwh') {
                $deskripsi = "Pemakaian " . ucfirst($laporan->jenis) . " (" . $laporan->stan_awal . " → " . $laporan->stan_akhir . " = " . $laporan->pemakaian . " " . $satuanText . ")";
            } else {
                $deskripsi = "Pemakaian " . ucfirst($laporan->jenis) . " (Flat: " . $laporan->pemakaian . " " . $satuanText . ")";
            }

            $tagihan = TagihanJasa::create([
                'mitra_jasa_id' => $laporan->mitra_jasa_id,
                'jenis_tagihan' => 'tagihan_jasa',
                'tipe_pnbp' => 'FUNGSI',
                'nomor_tagihan' => $nomorTagihan,
                'tanggal_tagihan' => now(),
                'tanggal_publish' => now(),
                'tanggal_jatuh_tempo' => $jatuhTempo,
                'total_tagihan' => $totalBiaya,
                'sisa_tagihan' => $totalBiaya,
                'keterangan' => "Tagihan Pemakaian " . ucfirst($laporan->jenis) . " Periode " . $laporan->bulan . "/" . $laporan->tahun,
                'status' => 'PUBLISHED',
                'created_by' => auth()->id(),
            ]);

            TagihanJasaDetail::create([
                'tagihan_jasa_id' => $tagihan->id,
                'layanan_jasa_id' => $laporan->layanan_jasa_id,
                'deskripsi' => $deskripsi,
                'volume' => $laporan->pemakaian,
                'satuan' => $laporan->layananJasa->satuan ?? $satuanText,
                'tarif' => $tarifPerUnit,
                'total' => $totalBiaya,
            ]);

            // Update laporan utilitas with tarif and total from Admin Jasa
            $laporan->update([
                'tarif_per_unit' => $tarifPerUnit,
                'total_biaya' => $totalBiaya,
                'status' => 'ditagihkan',
                'tagihan_jasa_id' => $tagihan->id,
            ]);

            DB::commit();
            return back()->with('success', 'Tagihan resmi berhasil dibuat dengan nomor: ' . $nomorTagihan);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function tolak(Request $request, $id)
    {
        $request->validate(['catatan' => 'required|string']);
        
        $laporan = LaporanUtilitas::findOrFail($id);
        
        if ($laporan->status !== 'dikirim_ke_admin_jasa') {
            return back()->with('error', 'Hanya laporan baru yang bisa ditolak.');
        }

        $laporan->update([
            'status' => 'ditolak',
            'catatan_admin_jasa' => $request->catatan,
        ]);

        return back()->with('success', 'Laporan berhasil ditolak dan dikembalikan ke pencatat.');
    }
}
