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
    public function createKontrak()
    {
        $kontraks = KontrakPengadaan::with('vendor')
            ->where('status_kontrak', 'AKTIF')
            ->latest()
            ->get();

        $pajaks = MasterTarifPajak::all();

        return view('tagihan.create_kontrak', compact('kontraks', 'pajaks'));
    }

    public function storeKontrak(Request $request)
    {
        $validated = $request->validate([
            'kontrak_pengadaan_id' => 'required|exists:kontrak_pengadaan,id',
            'kontrak_termin_id' => 'required|integer',
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
            'file_invoice' => 'required|file|mimes:pdf|max:5120',
            'file_bast' => 'required|file|mimes:pdf|max:5120',
            'file_kwitansi' => 'required|file|mimes:pdf|max:5120',
            'file_pajak' => 'nullable|file|mimes:pdf|max:5120',
            'file_lampiran_lainnya' => 'nullable|file|mimes:pdf,zip|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $kontrak = KontrakPengadaan::findOrFail($validated['kontrak_pengadaan_id']);
            $totalPotongan = collect($request->pajak ?? [])->sum('nominal');
            $totalNetto = $validated['total_bruto'] - $totalPotongan;
            $nomorTagihan = 'TAG-K/' . date('Ym') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $nomorTagihan,
                'tipe_tagihan' => 'KONTRAK',
                'master_dipa_id' => $kontrak->master_dipa_id,
                'pihak_id' => $kontrak->vendor_id,
                'deskripsi' => 'Pengajuan pembayaran untuk SPK/Kontrak ID: ' . $validated['kontrak_pengadaan_id'] . ' (Termin/BAST)',
                'total_bruto' => $validated['total_bruto'],
                'total_potongan' => $totalPotongan,
                'total_netto' => $totalNetto,
                'status' => 'PENDING_PPK',
                'created_by' => Auth::id(),
            ]);

            $detailKontrak = DetailKontrak::create([
                'tagihan_id' => $tagihan->id,
                'kontrak_termin_id' => $validated['kontrak_termin_id'],
                'nomor_bapp' => $validated['nomor_bapp'],
                'tanggal_bapp' => $validated['tanggal_bapp'],
                'nomor_bast' => $validated['nomor_bast'],
                'tanggal_bast' => $validated['tanggal_bast'],
                'nomor_bap' => $validated['nomor_bap'],
                'tanggal_bap' => $validated['tanggal_bap'],
            ]);

            if ($request->hasFile('file_bast')) {
                $detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'BAST',
                    'nama_file_asli' => $request->file('file_bast')->getClientOriginalName(),
                    'path_file' => $request->file('file_bast')->store('tagihan/bast', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_bast')->getMimeType(),
                    'ukuran_file' => $request->file('file_bast')->getSize(),
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            if ($request->hasFile('file_invoice')) {
                $detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'INVOICE',
                    'nama_file_asli' => $request->file('file_invoice')->getClientOriginalName(),
                    'path_file' => $request->file('file_invoice')->store('tagihan/invoice', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_invoice')->getMimeType(),
                    'ukuran_file' => $request->file('file_invoice')->getSize(),
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            if ($request->hasFile('file_kwitansi')) {
                $detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'KWITANSI',
                    'nama_file_asli' => $request->file('file_kwitansi')->getClientOriginalName(),
                    'path_file' => $request->file('file_kwitansi')->store('tagihan/kwitansi', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_kwitansi')->getMimeType(),
                    'ukuran_file' => $request->file('file_kwitansi')->getSize(),
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            if ($request->hasFile('file_lampiran_lainnya')) {
                $detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'LAMPIRAN_LAINNYA',
                    'nama_file_asli' => $request->file('file_lampiran_lainnya')->getClientOriginalName(),
                    'path_file' => $request->file('file_lampiran_lainnya')->store('tagihan/lampiran', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_lampiran_lainnya')->getMimeType(),
                    'ukuran_file' => $request->file('file_lampiran_lainnya')->getSize(),
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            if ($request->has('pajak')) {
                foreach ($request->pajak as $pj) {
                    $potongan = PotonganTagihan::create([
                        'tagihan_id' => $tagihan->id,
                        'pajak_id' => $pj['id'],
                        'jenis_potongan' => 'PAJAK',
                        'deskripsi' => 'Potongan pajak dari tagihan ' . $nomorTagihan,
                        'dpp' => $pj['dpp'],
                        'persentase_tarif_snapshot' => optional(MasterTarifPajak::find($pj['id']))->persentase,
                        'nama_pajak_snapshot' => optional(MasterTarifPajak::find($pj['id']))->jenis_pajak,
                        'nominal_potongan' => $pj['nominal'],
                    ]);

                    if ($request->hasFile('file_pajak')) {
                        $potongan->arsipDokumen()->create([
                            'jenis_dokumen' => 'FAKTUR_PAJAK',
                            'nama_file_asli' => $request->file('file_pajak')->getClientOriginalName(),
                            'path_file' => $request->file('file_pajak')->store('tagihan/pajak', 'public'),
                            'disk' => 'public',
                            'mime_type' => $request->file('file_pajak')->getMimeType(),
                            'ukuran_file' => $request->file('file_pajak')->getSize(),
                            'uploaded_by' => Auth::id(),
                            'uploaded_at' => now(),
                            'is_active' => true,
                        ]);
                    }
                }
            }

            DB::table('kontrak_termin')
                ->where('id', $validated['kontrak_termin_id'])
                ->update(['status_termin' => 'SUDAH_DITAGIH']);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'Pejabat Pengadaan',
                'status_sebelumnya' => null,
                'status_baru' => 'PENDING_PPK',
                'aksi' => 'DIAJUKAN',
                'catatan' => 'Tagihan termin baru dibuat. Menunggu verifikasi lanjutan.',
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return redirect()->route('contracts.index')->with('success', 'Tagihan Termin/BAST berhasil diajukan dan sedang menunggu verifikasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal membuat tagihan: ' . $e->getMessage()]);
        }
    }

    public function ppkIndex()
    {
        $tagihans = Tagihan::with(['detailKontrak.kontrakTermin.kontrak.vendor', 'potongans', 'logs'])
            ->where('tipe_tagihan', 'KONTRAK')
            ->where('status', 'PENDING_PPK')
            ->latest()
            ->get();

        return view('tagihans.ppk_index', compact('tagihans'));
    }

    public function verifyKontrak($id)
    {
        $tagihan = Tagihan::with(['detailKontrak.kontrakTermin.kontrak.vendor', 'potongans', 'logs'])->findOrFail($id);

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()->route('ppk.tagihan.kontrak.index')->with('error', 'Tagihan ini tidak sedang dalam antrean verifikasi Anda.');
        }

        return view('tagihans.ppk_verify', compact('tagihan'));
    }
}
