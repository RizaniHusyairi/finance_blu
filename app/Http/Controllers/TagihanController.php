<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\KontrakPengadaan;
use App\Models\Tagihan;
use App\Models\DetailKontrak;
use App\Models\PotonganTagihan;
use App\Models\LogStatusDokumen;
use App\Models\KontrakTermin;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;

class TagihanController extends Controller
{
    public function createKontrak(Request $request)
    {
        $kontraks = KontrakPengadaan::with(['vendor', 'termin'])
            ->where('status_kontrak', 'AKTIF')
            ->latest()
            ->get();

        $selectedKontrak = null;
        $selectedTermin = null;

        if ($request->filled('kontrak_id') && $request->filled('termin_id')) {
            $selectedKontrak = KontrakPengadaan::with('vendor')
                ->where('status_kontrak', 'AKTIF')
                ->findOrFail($request->integer('kontrak_id'));

            $selectedTermin = KontrakTermin::where('kontrak_pengadaan_id', $selectedKontrak->id)
                ->where('status_termin', 'READY_TO_BILL')
                ->findOrFail($request->integer('termin_id'));
        }

        $selectedPotonganAngsuran = ($selectedKontrak && $selectedTermin)
            ? $this->resolvePotonganAngsuranUangMuka($selectedKontrak, $selectedTermin)
            : 0;

        return view('tagihan.create_kontrak', compact('kontraks', 'selectedKontrak', 'selectedTermin', 'selectedPotonganAngsuran'));
    }

    public function storeKontrak(Request $request)
    {
        $validated = $request->validate([
            'kontrak_pengadaan_id' => 'required|exists:kontrak_pengadaan,id',
            'kontrak_termin_id' => 'required|exists:kontrak_termin,id',
            'nomor_bapp' => 'nullable|string|max:100',
            'tanggal_bapp' => 'nullable|date',
            'nomor_bast' => 'nullable|string|max:100',
            'tanggal_bast' => 'nullable|date',
            'nomor_bap' => 'required|string|max:100',
            'tanggal_bap' => 'required|date',
            'nomor_invoice' => 'required|string|max:100',
            'total_bruto' => 'required|numeric|min:0',
            'file_invoice' => 'required|file|mimes:pdf|max:5120',
            'file_bapp' => 'nullable|file|mimes:pdf|max:5120',
            'file_bap' => 'required|file|mimes:pdf|max:5120',
            'file_bast' => 'nullable|file|mimes:pdf|max:5120',
            'file_kwitansi' => 'required|file|mimes:pdf|max:5120',
            'file_lampiran_lainnya' => 'nullable|file|mimes:pdf,zip|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $kontrak = KontrakPengadaan::findOrFail($validated['kontrak_pengadaan_id']);
            $termin = KontrakTermin::where('kontrak_pengadaan_id', $kontrak->id)
                ->findOrFail($validated['kontrak_termin_id']);

            if ($termin->status_termin !== 'READY_TO_BILL') {
                throw new \Exception('Termin yang dipilih tidak lagi siap ditagih.');
            }

            if ($termin->jenis_termin === 'PELUNASAN') {
                $request->validate([
                    'nomor_bast' => 'required|string|max:100',
                    'tanggal_bast' => 'required|date',
                    'file_bast' => 'required|file|mimes:pdf|max:5120',
                ]);
            }

            $potonganAngsuranUangMuka = $this->resolvePotonganAngsuranUangMuka($kontrak, $termin);
            $totalPotonganPajak = 0;
            $totalPotongan = $potonganAngsuranUangMuka + $totalPotonganPajak;
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
                'kontrak_termin_id' => $termin->id,
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

            if ($request->hasFile('file_bapp')) {
                $detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'BAPP',
                    'nama_file_asli' => $request->file('file_bapp')->getClientOriginalName(),
                    'path_file' => $request->file('file_bapp')->store('tagihan/bapp', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_bapp')->getMimeType(),
                    'ukuran_file' => $request->file('file_bapp')->getSize(),
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            if ($request->hasFile('file_bap')) {
                $detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'BAP',
                    'nama_file_asli' => $request->file('file_bap')->getClientOriginalName(),
                    'path_file' => $request->file('file_bap')->store('tagihan/bap', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_bap')->getMimeType(),
                    'ukuran_file' => $request->file('file_bap')->getSize(),
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

            if ($potonganAngsuranUangMuka > 0) {
                PotonganTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'pajak_id' => null,
                    'jenis_potongan' => 'ANGSURAN_UANG_MUKA',
                    'deskripsi' => 'Potongan angsuran uang muka dari tagihan ' . $nomorTagihan,
                    'dpp' => $validated['total_bruto'],
                    'persentase_tarif_snapshot' => null,
                    'nama_pajak_snapshot' => 'Angsuran Uang Muka',
                    'nominal_potongan' => $potonganAngsuranUangMuka,
                ]);

                $kontrak->update([
                    'sisa_uang_muka_belum_lunas' => max(0, (float) $kontrak->sisa_uang_muka_belum_lunas - $potonganAngsuranUangMuka),
                ]);
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

            $this->notifyRoles(
                ['PPK'],
                'Tagihan Kontrak Baru',
                "Tagihan {$tagihan->nomor_tagihan} baru diajukan dan menunggu verifikasi Anda.",
                route('ppk.tagihan.kontrak.verify', $tagihan->id)
            );

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

    public function approveKontrak($id)
    {
        $tagihan = Tagihan::with(['detailKontrak.kontrakTermin.kontrak'])->findOrFail($id);

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()
                ->route('ppk.tagihan.kontrak.index')
                ->with('error', 'Tagihan ini tidak sedang dalam antrean verifikasi Anda.');
        }

        DB::beginTransaction();

        try {
            $statusSebelumnya = $tagihan->status;

            $tagihan->update([
                'status' => 'READY_FOR_SPP',
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'READY_FOR_SPP',
                'aksi' => 'APPROVE_PPK',
                'catatan' => 'Tagihan kontrak disetujui PPK dan diteruskan ke proses SPP.',
                'ip_address' => request()->ip(),
            ]);

            $this->notifyRoles(
                ['Operator BLU'],
                'Tagihan Kontrak Siap Diproses SPP',
                "Tagihan {$tagihan->nomor_tagihan} telah disetujui PPK dan siap dibuatkan SPP.",
                route('spps.kontrak.index')
            );

            DB::commit();

            return redirect()
                ->route('ppk.tagihan.kontrak.index')
                ->with('success', "Tagihan {$tagihan->nomor_tagihan} berhasil disetujui dan diteruskan ke SPP.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyetujui tagihan: ' . $e->getMessage());
        }
    }

    public function rejectKontrak(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|min:10|max:1000',
        ]);

        $tagihan = Tagihan::with(['detailKontrak.kontrakTermin.kontrak'])->findOrFail($id);

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()
                ->route('ppk.tagihan.kontrak.index')
                ->with('error', 'Tagihan ini tidak sedang dalam antrean verifikasi Anda.');
        }

        DB::beginTransaction();

        try {
            $statusSebelumnya = $tagihan->status;
            $contractId = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->id;
            $linkUrl = $contractId ? route('contracts.show', $contractId) : route('contracts.index');

            $tagihan->update([
                'status' => 'REVISI_PEJABAT_PENGADAAN',
            ]);

            if ($tagihan->detailKontrak?->kontrak_termin_id) {
                DB::table('kontrak_termin')
                    ->where('id', $tagihan->detailKontrak->kontrak_termin_id)
                    ->update(['status_termin' => 'READY_TO_BILL']);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'REVISI_PEJABAT_PENGADAAN',
                'aksi' => 'REJECT_PPK',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            $this->notifyRoles(
                ['Pejabat Pengadaan'],
                'Tagihan Kontrak Perlu Revisi',
                "Tagihan {$tagihan->nomor_tagihan} dikembalikan PPK: {$request->catatan_revisi}",
                $linkUrl
            );

            DB::commit();

            return redirect()
                ->route('ppk.tagihan.kontrak.index')
                ->with('success', "Tagihan {$tagihan->nomor_tagihan} berhasil dikembalikan untuk revisi.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal mengembalikan tagihan: ' . $e->getMessage());
        }
    }

    private function resolvePotonganAngsuranUangMuka(KontrakPengadaan $kontrak, KontrakTermin $termin): float
    {
        if (!$kontrak->ada_uang_muka || (float) $kontrak->sisa_uang_muka_belum_lunas <= 0) {
            return 0;
        }

        if (!in_array($termin->jenis_termin, ['PROGRESS', 'PELUNASAN'], true)) {
            return 0;
        }

        return round(min(
            (float) $termin->potongan_angsuran_uang_muka,
            (float) $kontrak->sisa_uang_muka_belum_lunas
        ), 2);
    }

    private function notifyRoles(array $roles, string $judul, string $pesan, ?string $linkUrl = null): void
    {
        Notification::send(User::role($roles)->get(), new WorkflowNotification([
            'title' => $judul,
            'message' => $pesan,
            'url' => $linkUrl,
            'icon' => 'notifications',
            'color' => 'primary',
        ]));
    }
}
