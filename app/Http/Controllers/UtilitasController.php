<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MitraJasa;
use App\Models\LayananJasa;
use App\Models\LaporanUtilitas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        // Mode edit: muat laporan yang akan diubah (hanya draft/ditolak milik jenis ini).
        $editLaporan = null;
        if ($request->filled('edit')) {
            $editLaporan = LaporanUtilitas::where('id', $request->edit)
                ->where('jenis', $jenis)
                ->whereIn('status', ['draft', 'ditolak'])
                ->first();
        }

        return view('utilitas.dashboard', compact('jenis', 'layanan', 'mitras', 'laporans', 'editLaporan'));
    }

    public function store(Request $request)
    {
        // Mitra, jenis pencatatan, dan periode dipilih SEKALI (shared); satu submit
        // dapat berisi banyak data meter (laporan[]) untuk mitra & periode yang sama.
        $tipe = $request->input('tipe_perhitungan');

        $rules = [
            'jenis' => 'required|in:listrik,air',
            'layanan_jasa_id' => 'required|exists:layanan_jasas,id',
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
            'tipe_perhitungan' => 'required|in:kwh,flat',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020',
            'laporan' => 'required|array|min:1',
        ];

        if ($tipe === 'kwh') {
            $rules['laporan.*.stan_awal'] = 'required|integer|min:0';
            $rules['laporan.*.stan_akhir'] = 'required|integer|min:0';
            $rules['laporan.*.file_bukti_awal'] = 'required|image|max:5120';
            $rules['laporan.*.file_bukti'] = 'required|image|max:5120';
        } else {
            $rules['laporan.*.pemakaian_manual'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules, [
            'mitra_jasa_id.required' => 'Mitra wajib dipilih.',
            'tipe_perhitungan.required' => 'Jenis pencatatan wajib dipilih.',
            'laporan.required' => 'Tambahkan minimal satu data meter.',
            'laporan.*.stan_awal.required' => 'Stan awal wajib diisi untuk mode meter.',
            'laporan.*.stan_akhir.required' => 'Stan akhir wajib diisi untuk mode meter.',
            'laporan.*.pemakaian_manual.required' => 'Jumlah pemakaian wajib diisi untuk mode flat.',
            'laporan.*.file_bukti_awal.required' => 'Foto bukti awal wajib untuk mode meter.',
            'laporan.*.file_bukti.required' => 'Foto bukti akhir wajib untuk mode meter.',
        ]);

        // Catatan: lebih dari satu laporan per mitra+layanan+periode kini diizinkan.

        DB::beginTransaction();
        try {
            foreach ($validated['laporan'] as $i => $row) {
                if ($tipe === 'kwh') {
                    if ((int) $row['stan_akhir'] < (int) $row['stan_awal']) {
                        DB::rollBack();
                        return back()->withInput()->with('error', 'Data Meter #' . ($i + 1) . ': stan akhir tidak boleh lebih kecil dari stan awal.');
                    }
                    $pemakaian = (int) $row['stan_akhir'] - (int) $row['stan_awal'];
                    $fileBuktiAwal = $request->file("laporan.$i.file_bukti_awal")?->store('bukti-utilitas', 'local');
                    $fileBukti = $request->file("laporan.$i.file_bukti")?->store('bukti-utilitas', 'local');
                } else {
                    $pemakaian = $row['pemakaian_manual'];
                    $fileBuktiAwal = null;
                    $fileBukti = null;
                }

                LaporanUtilitas::create([
                    'mitra_jasa_id' => $validated['mitra_jasa_id'],
                    'layanan_jasa_id' => $validated['layanan_jasa_id'],
                    'jenis' => $validated['jenis'],
                    'tipe_perhitungan' => $tipe,
                    'bulan' => $validated['bulan'],
                    'tahun' => $validated['tahun'],
                    'stan_awal' => $tipe === 'kwh' ? $row['stan_awal'] : null,
                    'stan_akhir' => $tipe === 'kwh' ? $row['stan_akhir'] : null,
                    'file_bukti_awal' => $fileBuktiAwal,
                    'file_bukti' => $fileBukti,
                    'pemakaian' => $pemakaian,
                    // tarif_per_unit dan total_biaya diisi oleh Admin Jasa nanti
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan laporan: ' . $e->getMessage());
        }

        $count = count($validated['laporan']);

        return back()->with('success', $count > 1
            ? "{$count} laporan pemakaian berhasil disimpan."
            : 'Laporan pemakaian berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $laporan = LaporanUtilitas::findOrFail($id);

        // Hanya laporan yang belum dikirim/ditagihkan yang boleh diubah.
        if (!in_array($laporan->status, ['draft', 'ditolak'], true)) {
            return back()->with('error', 'Hanya laporan draft atau yang ditolak yang dapat diubah.');
        }

        $rules = [
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
            'tipe_perhitungan' => 'required|in:kwh,flat',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020',
        ];

        if ($request->tipe_perhitungan === 'kwh') {
            // Saat edit, foto bukti opsional — file lama dipertahankan bila tidak diunggah ulang.
            $rules['stan_awal'] = 'required|integer|min:0';
            $rules['stan_akhir'] = 'required|integer|gte:stan_awal';
            $rules['file_bukti_awal'] = 'nullable|image|max:5120';
            $rules['file_bukti'] = 'nullable|image|max:5120';
        } else {
            $rules['pemakaian_manual'] = 'required|numeric|min:0';
        }

        $request->validate($rules);

        // Catatan: lebih dari satu laporan per mitra+layanan+periode kini diizinkan.

        if ($request->tipe_perhitungan === 'kwh') {
            $pemakaian = $request->stan_akhir - $request->stan_awal;
        } else {
            $pemakaian = $request->pemakaian_manual;
        }

        $data = [
            'mitra_jasa_id' => $request->mitra_jasa_id,
            'tipe_perhitungan' => $request->tipe_perhitungan,
            'bulan' => $request->bulan,
            'tahun' => $request->tahun,
            'stan_awal' => $request->tipe_perhitungan === 'kwh' ? $request->stan_awal : null,
            'stan_akhir' => $request->tipe_perhitungan === 'kwh' ? $request->stan_akhir : null,
            'pemakaian' => $pemakaian,
        ];

        if ($request->hasFile('file_bukti_awal')) {
            if ($laporan->file_bukti_awal) {
                Storage::disk('public')->delete($laporan->file_bukti_awal);
            }
            $data['file_bukti_awal'] = $request->file('file_bukti_awal')->store('bukti-utilitas', 'local');
        }

        if ($request->hasFile('file_bukti')) {
            if ($laporan->file_bukti) {
                Storage::disk('public')->delete($laporan->file_bukti);
            }
            $data['file_bukti'] = $request->file('file_bukti')->store('bukti-utilitas', 'local');
        }

        $laporan->update($data);

        return redirect()->route('utilitas.dashboard')->with('success', 'Laporan berhasil diperbarui.');
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
