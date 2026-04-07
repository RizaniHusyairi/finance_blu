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
use App\Services\WorkflowService;

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

        $docService = app(\App\Services\DocumentNumberService::class);
        $previewBapp = $docService->previewByKey('BAPP');
        $previewBast = $docService->previewByKey('BAST');
        $previewBap = $docService->previewByKey('BAP');

        return view('tagihan.create_kontrak', compact('kontraks', 'selectedKontrak', 'selectedTermin', 'selectedPotonganAngsuran', 'previewBapp', 'previewBast', 'previewBap'));
    }

    public function storeKontrak(Request $request)
    {
        $validated = $request->validate([
            'kontrak_pengadaan_id' => 'required|exists:kontrak_pengadaan,id',
            'kontrak_termin_id' => 'required|exists:kontrak_termin,id',
            'tanggal_bapp' => 'nullable|date',
            'tanggal_bast' => 'nullable|date',
            'tanggal_bap' => 'required|date',
            'nomor_invoice' => 'required|string|max:100',
            'tanggal_invoice' => 'required|date',
            'nama_pemeriksa' => 'required|string|max:150',
            'nip_pemeriksa' => 'nullable|string|max:50',
            'jabatan_pemeriksa' => 'required|string|max:150',
            'total_bruto' => 'required|numeric|min:0',
            'file_invoice' => 'required|file|mimes:pdf|max:5120',
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

            // Mencegah duplikasi pembuatan draft tagihan dari termin yang sama
            if (DetailKontrak::where('kontrak_termin_id', $termin->id)->exists()) {
                throw new \Exception('Draft tagihan untuk termin ini sudah pernah dibuat.');
            }

            if ($termin->jenis_termin === 'PELUNASAN') {
                $request->validate([
                    'tanggal_bast' => 'required|date',
                ]);
            }

            // Prioritas backend: sumber utama nilai adalah backend/data termin
            $totalBruto = $termin->nilai_bruto_termin;
            if (abs((float)$validated['total_bruto'] - (float)$totalBruto) > 1.0) {
                throw new \Exception("Nilai bruto yang dikirimkan tidak sesuai dengan data termin ({$totalBruto}).");
            }

            $potonganAngsuranUangMuka = $this->resolvePotonganAngsuranUangMuka($kontrak, $termin);
            $totalPotonganPajak = 0;
            $totalPotongan = $potonganAngsuranUangMuka + $totalPotonganPajak;
            $totalNetto = $totalBruto - $totalPotongan;
            $nomorTagihan = 'TAG-K/' . date('Ym') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $nomorTagihan,
                'tipe_tagihan' => 'KONTRAK',
                'master_dipa_id' => $kontrak->master_dipa_id,
                'dipa_revision_item_id' => $kontrak->dipa_revision_item_id ?? null,
                'pihak_id' => $kontrak->vendor_id,
                'deskripsi' => 'Pengajuan pembayaran untuk SPK/Kontrak ID: ' . $validated['kontrak_pengadaan_id'] . ' (Termin/BAST)',
                'total_bruto' => $totalBruto,
                'total_potongan' => $totalPotongan,
                'total_netto' => $totalNetto,
                'status' => 'DRAFT',
                'created_by' => Auth::id(),
            ]);

            $docService = app(\App\Services\DocumentNumberService::class);
            $nomorBapp = $docService->generateByKey('BAPP');
            $nomorBap = $docService->generateByKey('BAP');
            $nomorBast = $termin->jenis_termin === 'PELUNASAN' ? $docService->generateByKey('BAST') : null;

            $detailKontrak = DetailKontrak::create([
                'tagihan_id' => $tagihan->id,
                'kontrak_termin_id' => $termin->id,
                'nomor_bapp' => $nomorBapp,
                'tanggal_bapp' => $validated['tanggal_bapp'] ?? null,
                'nomor_bast' => $nomorBast,
                'tanggal_bast' => $validated['tanggal_bast'] ?? null,
                'nomor_bap' => $nomorBap,
                'tanggal_bap' => $validated['tanggal_bap'],
                'tanggal_invoice' => $validated['tanggal_invoice'],
                'nomor_invoice' => $validated['nomor_invoice'],
                'nama_pemeriksa' => $validated['nama_pemeriksa'],
                'nip_pemeriksa' => $validated['nip_pemeriksa'] ?? null,
                'jabatan_pemeriksa' => $validated['jabatan_pemeriksa'],
            ]);

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
                    'dpp' => $totalBruto,
                    'persentase_tarif_snapshot' => null,
                    'nama_pajak_snapshot' => 'Angsuran Uang Muka',
                    'nominal_potongan' => $potonganAngsuranUangMuka,
                ]);

                // TODO: Pengurangan $kontrak->sisa_uang_muka_belum_lunas JANGAN dilakukan prematur di tahap DRAFT.
                // Lakukan ini nanti saat verifikasi/pencairan mutakhir.
            }

            // Update termin status menjadi DRAFT agar UI mengunci pembuatan tombol tagihan ganda
            DB::table('kontrak_termin')
                ->where('id', $validated['kontrak_termin_id'])
                ->update(['status_termin' => 'DRAFT']);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'Pejabat Pengadaan',
                'status_sebelumnya' => null,
                'status_baru' => 'DRAFT',
                'aksi' => 'DIBUAT',
                'catatan' => 'Draft tagihan termin baru dibuat. Menunggu kelengkapan dokumen BAPP/BAST/BAP.',
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return redirect()->route('tagihan.kontrak.show', $tagihan->id)->with('success', 'Draft Tagihan Termin berhasil dibuat. Silakan lengkapi dokumen final.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal membuat tagihan: ' . $e->getMessage()]);
        }
    }

    public function showKontrak($id)
    {
        $tagihan = Tagihan::with([
            'detailKontrak.arsipDokumen',
            'detailKontrak.termin.kontrak.vendor',
            'detailKontrak.termin.kontrak.ppkUser.pegawai',
            'potonganTagihan'
        ])->findOrFail($id);

        if ($tagihan->tipe_tagihan !== 'KONTRAK') {
            abort(404);
        }

        $detailKontrak = $tagihan->detailKontrak;
        $termin = $detailKontrak->termin;
        $kontrak = $termin->kontrak;
        
        $arsip = $detailKontrak->arsipDokumen->where('is_active', true);
        
        $hasBappFinal = $arsip->contains('jenis_dokumen', 'BAPP_FINAL_TTD');
        $hasBapFinal = $arsip->contains('jenis_dokumen', 'BAP_FINAL_TTD');
        
        $wajibBast = ($termin->jenis_termin === 'PELUNASAN');
        $hasBastFinal = $arsip->contains('jenis_dokumen', 'BAST_FINAL_TTD');
        
        $isReadyToSubmit = $this->isTagihanReadyToSubmit($tagihan);

        return view('tagihan.show_kontrak', compact(
            'tagihan', 'detailKontrak', 'termin', 'kontrak', 
            'hasBappFinal', 'hasBapFinal', 'hasBastFinal', 'wajibBast', 'isReadyToSubmit'
        ));
    }

    public function uploadArsipKontrak(Request $request, $id)
    {
        $request->validate([
            'jenis_dokumen' => 'required|in:BAPP_FINAL_TTD,BAST_FINAL_TTD,BAP_FINAL_TTD',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $tagihan = Tagihan::findOrFail($id);
        $detailKontrak = $tagihan->detailKontrak;

        $jenis = $request->jenis_dokumen;

        // Nonaktifkan dokumen lama jika ada
        $detailKontrak->arsipDokumen()->where('jenis_dokumen', $jenis)->update(['is_active' => false]);

        // Simpan dokumen baru
        $path = $request->file('file')->store('tagihan/final_docs', 'public');

        $detailKontrak->arsipDokumen()->create([
            'jenis_dokumen' => $jenis,
            'nama_file_asli' => $request->file('file')->getClientOriginalName(),
            'path_file' => $path,
            'disk' => 'public',
            'mime_type' => $request->file('file')->getMimeType(),
            'ukuran_file' => $request->file('file')->getSize(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'is_active' => true,
        ]);

        return back()->with('success', str_replace('_FINAL_TTD', '', $jenis) . ' Final berhasil diunggah.');
    }

    public function submitKontrak(Request $request, $id)
    {
        $tagihan = Tagihan::findOrFail($id);
        
        if ($tagihan->status !== 'DRAFT') {
            return back()->withErrors(['error' => 'Tagihan tidak dalam status DRAFT.']);
        }

        if (!$this->isTagihanReadyToSubmit($tagihan)) {
            return back()->withErrors(['error' => 'Dokumen BAPP dan BAP (serta BAST untuk Pelunasan) Final bertandatangan wajib diunggah secara lengkap.']);
        }

        try {
            DB::beginTransaction();

            $tagihan->update(['status' => 'PENDING_PPK']);

            // Mengikat/memfinalisasi status termin menjadi SUDAH_DITAGIH agar statusnya maju ketika tagihan beneran disubmit
            if ($tagihan->detailKontrak && $tagihan->detailKontrak->termin) {
                $tagihan->detailKontrak->termin->update(['status_termin' => 'SUDAH_DITAGIH']);
            }

            // --- Workflow Engine: mulai workflow TAGIHAN_KONTRAK_PPK ---
            $ppkUserId = optional($tagihan->detailKontrak?->termin?->kontrak)->ppk_user_id;
            app(WorkflowService::class)->startWorkflow('TAGIHAN_KONTRAK_PPK', $tagihan, $ppkUserId);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'Pejabat Pengadaan',
                'status_sebelumnya' => 'DRAFT',
                'status_baru' => 'PENDING_PPK',
                'aksi' => 'DIAJUKAN',
                'catatan' => 'Tagihan termin dan dokumen berita acara ttd diajukan ke PPK.',
                'ip_address' => request()->ip(),
            ]);

            // Notifikasi ke PPK assigned, fallback ke semua PPK
            if ($ppkUserId) {
                $ppkUser = User::find($ppkUserId);
                if ($ppkUser) {
                    Notification::send($ppkUser, new WorkflowNotification([
                        'title' => 'Tagihan Kontrak Baru',
                        'message' => "Tagihan {$tagihan->nomor_tagihan} siap untuk diverifikasi.",
                        'url' => route('ppk.tagihan.kontrak.verify', $tagihan->id),
                        'icon' => 'receipt_long',
                        'color' => 'primary',
                    ]));
                }
            } else {
                $this->notifyRoles(
                    ['PPK'],
                    'Tagihan Kontrak Baru',
                    "Tagihan {$tagihan->nomor_tagihan} siap untuk diverifikasi.",
                    route('ppk.tagihan.kontrak.verify', $tagihan->id)
                );
            }

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'Tagihan berhasil diajukan ke PPK.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengajukan ke PPK: ' . $e->getMessage()]);
        }
    }

    public function exportPdfKontrak($id, $type)
    {
        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak.vendor', 'detailKontrak.termin.kontrak.ppkUser.pegawai', 'potonganTagihan'])->findOrFail($id);
        $detailKontrak = $tagihan->detailKontrak;
        $termin = $detailKontrak->termin;
        $kontrak = $termin->kontrak;
        
        $terbilang = ucwords(terbilang($kontrak->nilai_total_kontrak)) . ' Rupiah';
        $terbilangTagihan = ucwords(terbilang($tagihan->total_bruto)) . ' Rupiah';

        $data = [
            'tagihan' => $tagihan,
            'detail' => $detailKontrak,
            'termin' => $detailKontrak->termin,
            'kontrak' => $kontrak,
            'vendor' => $kontrak->vendor,
            'terbilang' => $terbilang,
            'terbilangTagihan' => $terbilangTagihan
        ];

        if ($type === 'BAPP') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.kontrak.bapp', $data)->setPaper('a4', 'portrait');
            return $pdf->stream('BAPP_' . str_replace('/', '_', $detailKontrak->nomor_bapp ?? 'draft') . '.pdf');
        } elseif ($type === 'BAST') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.kontrak.bast', $data)->setPaper('a4', 'portrait');
            return $pdf->stream('BAST_' . str_replace('/', '_', $detailKontrak->nomor_bast ?? 'draft') . '.pdf');
        } elseif ($type === 'BAP') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.kontrak.bap', $data)->setPaper('a4', 'portrait');
            return $pdf->stream('BAP_' . str_replace('/', '_', $detailKontrak->nomor_bap ?? 'draft') . '.pdf');
        }

        abort(404);
    }

    public function ppkIndex()
    {
        $tagihans = Tagihan::with(['detailKontrak.termin.kontrak.vendor', 'potonganTagihan', 'logs'])
            ->where('tipe_tagihan', 'KONTRAK')
            ->where('status', 'PENDING_PPK')
            ->latest()
            ->get();

        return view('tagihans.ppk_index', compact('tagihans'));
    }

    public function verifyKontrak($id)
    {
        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak.vendor', 'potonganTagihan', 'logs'])->findOrFail($id);

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()->route('ppk.tagihan.kontrak.index')->with('error', 'Tagihan ini tidak sedang dalam antrean verifikasi Anda.');
        }

        return view('tagihans.ppk_verify', compact('tagihan'));
    }

    public function approveKontrak($id)
    {
        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak'])->findOrFail($id);

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

            // --- Workflow Engine: approve step aktif ---
            app(WorkflowService::class)->approveCurrentStep($tagihan, Auth::id(), 'Tagihan kontrak disetujui PPK.');

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

        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak'])->findOrFail($id);

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()
                ->route('ppk.tagihan.kontrak.index')
                ->with('error', 'Tagihan ini tidak sedang dalam antrean verifikasi Anda.');
        }

        DB::beginTransaction();

        try {
            $statusSebelumnya = $tagihan->status;
            $contractId = $tagihan->detailKontrak?->termin?->kontrak?->id;
            $linkUrl = $contractId ? route('contracts.show', $contractId) : route('contracts.index');

            $tagihan->update([
                'status' => 'REVISI_PEJABAT_PENGADAAN',
            ]);

            // --- Workflow Engine: request revision ---
            app(WorkflowService::class)->requestRevision($tagihan, Auth::id(), $request->catatan_revisi);

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

    private function isTagihanReadyToSubmit(Tagihan $tagihan): bool
    {
        $arsip = optional($tagihan->detailKontrak)->arsipDokumen;
        if (!$arsip) return false;

        $arsipAktif = $arsip->where('is_active', true);
        
        $hasBappFinal = $arsipAktif->contains('jenis_dokumen', 'BAPP_FINAL_TTD');
        $hasBapFinal = $arsipAktif->contains('jenis_dokumen', 'BAP_FINAL_TTD');
        
        if (!$hasBappFinal || !$hasBapFinal) {
            return false;
        }

        $jenisTermin = optional(optional($tagihan->detailKontrak)->termin)->jenis_termin;
        if ($jenisTermin === 'PELUNASAN' && !$arsipAktif->contains('jenis_dokumen', 'BAST_FINAL_TTD')) {
            return false;
        }

        return true;
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
