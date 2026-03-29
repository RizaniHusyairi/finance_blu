<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\KontrakPengadaan;
use App\Models\MasterTarifPajak;
use App\Models\Tagihan;
use App\Models\DetailKontrak;
use App\Models\PotonganTagihan;
use App\Models\LogStatusDokumen;

class TagihanController extends Controller
{
    /**
     * Tampilkan form pembuatan tagihan khusus kontrak
     */
    public function createKontrak()
    {
        // Hanya Kontrak yang statusnya AKTIF yang bisa ditagih
        $kontraks = KontrakPengadaan::with('vendor')
                    ->where('status_kontrak', 'AKTIF')
                    ->latest()
                    ->get();
                    
        $pajaks = MasterTarifPajak::all();

        return view('tagihan.create_kontrak', compact('kontraks', 'pajaks'));
    }

    /**
     * Proses penyimpanan tagihan kontrak ke database sesuai state machine
     */
    public function storeKontrak(Request $request)
    {
        $validated = $request->validate([
            'kontrak_pengadaan_id' => 'required|exists:kontrak_pengadaan,id',
            'kontrak_termin_id' => 'required|integer', // validasi exists ke kontrak_termin jika modelnya siap
            'nomor_bapp' => 'nullable|string|max:100',
            'tanggal_bapp' => 'nullable|date',
            'nomor_bast' => 'required|string|max:100',
            'tanggal_bast' => 'required|date',
            'nomor_bap' => 'required|string|max:100',
            'tanggal_bap' => 'required|date',
            'nomor_invoice' => 'required|string|max:100',
            'total_bruto' => 'required|numeric|min:0',
            'pajak' => 'nullable|array',
            'pajak.*.id' => 'required|exists:master_tarif_pajak,id',
            'pajak.*.dpp' => 'required|numeric|min:0',
            'pajak.*.nominal' => 'required|numeric|min:0',
            
            // Files
            'file_invoice' => 'required|file|mimes:pdf|max:5120',
            'file_bast' => 'required|file|mimes:pdf|max:5120',
            'file_kwitansi' => 'required|file|mimes:pdf|max:5120',
            'file_pajak' => 'nullable|file|mimes:pdf|max:5120',
            'file_lampiran_lainnya' => 'nullable|file|mimes:pdf,zip|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // 1. Hitung ulang total potongan & netto secara server-side (keamanan)
            $totalPotongan = 0;
            if ($request->has('pajak')) {
                foreach ($request->pajak as $pj) {
                    $totalPotongan += $pj['nominal'];
                }
            }
            $totalNetto = $validated['total_bruto'] - $totalPotongan;

            // Generate nomor tagihan otomatis
            $nomorTagihan = 'TAG-K/' . date('Ym') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // 2. Buat Record Induk Tagihan
            $tagihan = Tagihan::create([
                'nomor_tagihan' => $nomorTagihan,
                'tipe_tagihan' => 'KONTRAK',
                // Ambila DIPA dari kontrak aslinya
                'master_dipa_id' => KontrakPengadaan::find($validated['kontrak_pengadaan_id'])->master_dipa_id,
                'deskripsi' => 'Pengajuan pembayaran untuk SPK/Kontrak ID: ' . $validated['kontrak_pengadaan_id'] . ' (Termin/BAST)',
                'total_bruto' => $validated['total_bruto'],
                'total_potongan' => $totalPotongan,
                'total_netto' => $totalNetto,
                'status' => 'PENDING_PPK', // State machine awal
                'created_by' => Auth::id(),
            ]);

            // 3. Upload File
            $pathInvoice = $request->file('file_invoice')->store('tagihan/invoice', 'public');
            $pathBast = $request->file('file_bast')->store('tagihan/bast', 'public');
            $pathKwitansi = $request->file('file_kwitansi')->store('tagihan/kwitansi', 'public');
            $pathPajak = $request->hasFile('file_pajak') ? $request->file('file_pajak')->store('tagihan/pajak', 'public') : null;
            $pathLampiran = $request->hasFile('file_lampiran_lainnya') ? $request->file('file_lampiran_lainnya')->store('tagihan/lampiran', 'public') : null;

            // 4. Buat Record Detail Kontrak (Anak Tagihan)
            DetailKontrak::create([
                'tagihan_id' => $tagihan->id,
                'kontrak_termin_id' => $validated['kontrak_termin_id'],
                'nomor_bapp' => $validated['nomor_bapp'],
                'tanggal_bapp' => $validated['tanggal_bapp'],
                'nomor_bast' => $validated['nomor_bast'],
                'tanggal_bast' => $validated['tanggal_bast'],
                'nomor_bap' => $validated['nomor_bap'],
                'tanggal_bap' => $validated['tanggal_bap'],
                
                'file_bapp' => null, // Jika digabung dalam BAST
                'file_bast' => $pathBast,
                'file_bap' => null, 
                'file_invoice' => $pathInvoice,
                'file_kwitansi' => $pathKwitansi,
                'file_lampiran_lainnya' => $pathLampiran,
            ]);

            // 5. Buat Record Potongan Tagihan
            if ($request->has('pajak')) {
                foreach ($request->pajak as $pj) {
                    PotonganTagihan::create([
                        'tagihan_id' => $tagihan->id,
                        'pajak_id' => $pj['id'],
                        'jenis_potongan' => 'PAJAK', // Hardcoded untuk modul ini
                        'deskripsi' => 'Potongan pajak dari tagihan ' . $nomorTagihan,
                        'dpp' => $pj['dpp'],
                        'nominal_potongan' => $pj['nominal'],
                        'file_faktur_pajak' => $pathPajak, // Bisa digunakan berulang kali jika ada 1 file
                    ]);
                }
            }

            // Update status termin agar tidak ditagih dua kali
            DB::table('kontrak_termin')
                ->where('id', $validated['kontrak_termin_id'])
                ->update(['status_termin' => 'SUDAH_DITAGIH']);

            // 6. Catat Log Sistem
            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'Pejabat Pengadaan',
                'status_sebelumnya' => null,
                'status_baru' => 'PENDING_PPK',
                'aksi' => 'DIAJUKAN',
                'catatan' => 'Tagihan termin baru dibuat. Menunggu verifikasi lanjutan.',
            ]);

            DB::commit();

            return redirect()->route('contracts.index')->with('success', 'Tagihan Termin/BAST berhasil diajukan dan sedang menunggu verifikasi.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal membuat tagihan: ' . $e->getMessage()]);
        }
    }
}
