<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\KontrakPengadaan;
use App\Models\Tagihan;
use App\Models\DetailKontrak;
use App\Models\ArsipDokumen;
use App\Models\PotonganTagihan;
use App\Models\LogStatusDokumen;
use App\Models\KontrakTermin;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
        $bapPreviewOffset = $selectedTermin?->jenis_termin === 'PELUNASAN' ? 2 : 1;
        $previewBapp = $docService->previewByKey('BAPP');
        $previewBast = $docService->previewByKey('BAST', null, 1);
        $previewBap = $docService->previewByKey('BAP', null, $bapPreviewOffset);

        // Pegawai aktif untuk dropdown Pemeriksa Hasil Pekerjaan (BAPP)
        $pegawaiList = \App\Models\MasterPegawai::where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nip', 'jabatan']);

        // User per role untuk dropdown Verifikator (PPK ditentukan otomatis dari kontrak)
        $verifikatorRoles = [
            'ppspm'                => 'PPSPM',
            'koordinator_keuangan' => 'Koordinator Keuangan',
            'bendahara_pengeluaran'=> 'Bendahara Pengeluaran',
            'bendahara_penerimaan' => 'Bendahara Penerimaan',
            'kasubbag'             => 'Kepala Subbagian Keuangan dan Tata Usaha',
        ];
        $verifikatorOptions = [];
        foreach ($verifikatorRoles as $key => $roleName) {
            $verifikatorOptions[$key] = User::role($roleName)
                ->with('profilable')
                ->orderByDisplayName()
                ->get()
                ->map(fn ($u) => [
                    'id'      => $u->id,
                    'name'    => $u->name,
                    'nip'     => optional($u->profilable)->nip ?? '-',
                    'jabatan' => optional($u->profilable)->jabatan ?? $roleName,
                ])
                ->values();
        }

        return view('tagihan.create_kontrak', compact(
            'kontraks', 'selectedKontrak', 'selectedTermin', 'selectedPotonganAngsuran',
            'previewBapp', 'previewBast', 'previewBap', 'pegawaiList', 'verifikatorOptions'
        ));
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
            // Verifikator (PPK ditentukan dari kontrak, 5 lainnya dipilih)
            'ppspm_user_id'                 => 'required|exists:users,id',
            'koordinator_keuangan_user_id'  => 'required|exists:users,id',
            'bendahara_pengeluaran_user_id' => 'required|exists:users,id',
            'bendahara_penerimaan_user_id'  => 'required|exists:users,id',
            'kasubbag_user_id'              => 'required|exists:users,id',
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

            // Bangun snapshot verifikator (potret nama+NIP saat tagihan dibuat)
            $verifikatorSnapshots = $this->buildVerifikatorSnapshots([
                'ppk'                  => (int) ($kontrak->ppk_user_id ?? 0),
                'ppspm'                => (int) $validated['ppspm_user_id'],
                'koordinator_keuangan' => (int) $validated['koordinator_keuangan_user_id'],
                'bendahara_pengeluaran'=> (int) $validated['bendahara_pengeluaran_user_id'],
                'bendahara_penerimaan' => (int) $validated['bendahara_penerimaan_user_id'],
                'kasubbag'             => (int) $validated['kasubbag_user_id'],
            ]);

            $tagihan = Tagihan::create(array_merge([
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
            ], $verifikatorSnapshots));

            $docService = app(\App\Services\DocumentNumberService::class);
            $nomorBapp = $docService->generateByKey('BAPP');
            $nomorBast = $termin->jenis_termin === 'PELUNASAN' ? $docService->generateByKey('BAST') : null;
            $nomorBap = $docService->generateByKey('BAP');

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
            'detailKontrak.termin.kontrak.ppkUser.profilable',
            'potonganTagihan',
            'workflowInstance.approvals.actedByUser',
            'workflowInstance.approvals.assignedUser',
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
        $hasGambarRabBapp = $arsip->contains('jenis_dokumen', 'BAPP_GAMBAR_RAB');
        $gambarRabBapp = $arsip->firstWhere('jenis_dokumen', 'BAPP_GAMBAR_RAB');

        $wajibBast = ($termin->jenis_termin === 'PELUNASAN');
        $hasBastFinal = $arsip->contains('jenis_dokumen', 'BAST_FINAL_TTD');

        $isReadyToSubmit = $this->isTagihanReadyToSubmit($tagihan);

        return view('tagihan.show_kontrak', compact(
            'tagihan', 'detailKontrak', 'termin', 'kontrak',
            'hasBappFinal', 'hasBapFinal', 'hasBastFinal', 'wajibBast', 'isReadyToSubmit',
            'hasGambarRabBapp', 'gambarRabBapp'
        ));
    }

    public function uploadArsipKontrak(Request $request, $id)
    {
        $jenis = $request->input('jenis_dokumen');
        $isGambarRab = $jenis === 'BAPP_GAMBAR_RAB';

        $request->validate([
            'jenis_dokumen' => 'required|in:BAPP_FINAL_TTD,BAST_FINAL_TTD,BAP_FINAL_TTD,BAPP_GAMBAR_RAB',
            'file' => $isGambarRab
                ? 'required|file|mimes:jpg,jpeg,png|max:5120'
                : 'required|file|mimes:pdf|max:10240',
        ]);

        $tagihan = Tagihan::findOrFail($id);
        $detailKontrak = $tagihan->detailKontrak;

        // Nonaktifkan dokumen lama jika ada
        $detailKontrak->arsipDokumen()->where('jenis_dokumen', $jenis)->update(['is_active' => false]);

        // Simpan dokumen baru
        $folder = $isGambarRab ? 'tagihan/bapp_gambar_rab' : 'tagihan/final_docs';
        $path = $request->file('file')->store($folder, 'public');

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

        $msg = $isGambarRab
            ? 'Gambar RAB BAPP berhasil diunggah.'
            : str_replace('_FINAL_TTD', '', $jenis) . ' Final berhasil diunggah.';

        return back()->with('success', $msg);
    }

    public function viewArsipKontrak($id, $arsipId)
    {
        $tagihan = Tagihan::with('detailKontrak')->findOrFail($id);
        $detailKontrak = $tagihan->detailKontrak;

        abort_unless($detailKontrak, 404);

        $arsip = ArsipDokumen::query()
            ->whereKey($arsipId)
            ->where('documentable_type', DetailKontrak::class)
            ->where('documentable_id', $detailKontrak->id)
            ->where('is_active', true)
            ->whereIn('jenis_dokumen', ['BAPP_FINAL_TTD', 'BAST_FINAL_TTD', 'BAP_FINAL_TTD', 'BAPP_GAMBAR_RAB'])
            ->firstOrFail();

        $disk = $arsip->disk ?: 'public';
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($arsip->path_file), 404);

        return $storage->response(
            $arsip->path_file,
            $arsip->nama_file_asli ?: basename($arsip->path_file)
        );
    }

    public function submitKontrak(Request $request, $id)
    {
        $tagihan = Tagihan::with('detailKontrak.termin.kontrak')->findOrFail($id);

        if ($tagihan->status !== 'DRAFT') {
            return back()->withErrors(['error' => 'Tagihan tidak dalam status DRAFT.']);
        }

        if (!$this->isTagihanReadyToSubmit($tagihan)) {
            return back()->withErrors(['error' => 'Dokumen BAPP dan BAP (serta BAST untuk Pelunasan) Final bertandatangan wajib diunggah secara lengkap.']);
        }

        // Pastikan semua verifikator sudah terisi
        $missingVerif = collect([
            'PPK' => $tagihan->ppk_user_id,
            'PPSPM' => $tagihan->ppspm_user_id,
            'Koordinator Keuangan' => $tagihan->koordinator_keuangan_user_id,
            'Bendahara Pengeluaran' => $tagihan->bendahara_pengeluaran_user_id,
            'Bendahara Penerimaan' => $tagihan->bendahara_penerimaan_user_id,
            'Kasubbag' => $tagihan->kasubbag_user_id,
        ])->filter(fn ($v) => empty($v))->keys();

        if ($missingVerif->isNotEmpty()) {
            return back()->withErrors(['error' => 'Verifikator belum lengkap: ' . $missingVerif->implode(', ')]);
        }

        try {
            DB::beginTransaction();

            // Mengikat termin menjadi SUDAH_DITAGIH
            if ($tagihan->detailKontrak && $tagihan->detailKontrak->termin) {
                $tagihan->detailKontrak->termin->update(['status_termin' => 'SUDAH_DITAGIH']);
            }

            // === Workflow baru: TAGIHAN_KONTRAK_VERIFIKATOR (5 paralel + Kasubbag final) ===
            // Service akan resolve assignee tiap step dari kolom *_user_id pada tagihan.
            $workflow = app(\App\Services\TagihanKontrakWorkflowService::class);
            $workflow->submit($tagihan, Auth::user(), $request->ip());

            // syncTagihanStatus sudah dijalankan di dalam submit() — status akan
            // terset ke PENDING_VERIFIKASI_KONTRAK secara otomatis.
            $tagihan->refresh();

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'Pejabat Pengadaan',
                'status_sebelumnya' => 'DRAFT',
                'status_baru' => $tagihan->status,
                'aksi' => 'DIAJUKAN',
                'catatan' => 'Tagihan diajukan ke 5 verifikator paralel (PPK, PPSPM, Koor.Keu, Bend.Keluar, Bend.Terima) lalu Kasubbag.',
                'ip_address' => request()->ip(),
            ]);

            // Notifikasi paralel ke semua 5 verifikator step 1
            foreach ([
                $tagihan->ppk_user_id,
                $tagihan->ppspm_user_id,
                $tagihan->koordinator_keuangan_user_id,
                $tagihan->bendahara_pengeluaran_user_id,
                $tagihan->bendahara_penerimaan_user_id,
            ] as $uid) {
                if (! $uid) continue;
                $u = User::find($uid);
                if (! $u) continue;
                Notification::send($u, new WorkflowNotification([
                    'title' => 'Tagihan Kontrak Menunggu Verifikasi',
                    'message' => "Tagihan {$tagihan->nomor_tagihan} menunggu verifikasi Anda.",
                    'url' => route('verifikasi-tagihan-kontrak.show', $tagihan->id),
                    'icon' => 'receipt_long',
                    'color' => 'primary',
                ]));
            }

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'Tagihan berhasil diajukan. Menunggu verifikasi 5 pejabat paralel, lalu finalisasi Kasubbag.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengajukan tagihan: ' . $e->getMessage()]);
        }
    }

    public function exportPdfKontrak($id, $type)
    {
        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak.vendor', 'detailKontrak.termin.kontrak.ppkUser.profilable', 'potonganTagihan'])->findOrFail($id);
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
            // Guard: gambar RAB wajib diunggah dulu sebelum export PDF draft BAPP
            $gambarRab = $detailKontrak->arsipDokumen
                ->where('jenis_dokumen', 'BAPP_GAMBAR_RAB')
                ->where('is_active', true)
                ->first();

            if (! $gambarRab) {
                return back()->withErrors([
                    'error' => 'Gambar RAB BAPP wajib diunggah terlebih dahulu sebelum mengekspor PDF draft BAPP.',
                ]);
            }

            // Encode gambar ke data URI agar dompdf dapat menampilkannya tanpa request HTTP
            $disk = Storage::disk($gambarRab->disk ?: 'public');
            if (! $disk->exists($gambarRab->path_file)) {
                return back()->withErrors(['error' => 'File gambar RAB tidak ditemukan di storage. Silakan unggah ulang.']);
            }

            $mime = $gambarRab->mime_type ?: 'image/png';
            $data['gambarRabBase64'] = 'data:' . $mime . ';base64,' . base64_encode($disk->get($gambarRab->path_file));

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

    /**
     * Bangun array kolom verifikator untuk Tagihan berdasarkan user_id yang dipilih.
     * Mengembalikan pasangan kolom: <prefix>_user_id, <prefix>_nama_snapshot, <prefix>_nip_snapshot
     * untuk setiap key yang valid (user_id > 0 dan ada di DB).
     */
    private function buildVerifikatorSnapshots(array $userIdByKey): array
    {
        $prefixMap = [
            'ppk'                  => 'ppk',
            'ppspm'                => 'ppspm',
            'koordinator_keuangan' => 'koordinator_keuangan',
            'bendahara_pengeluaran'=> 'bendahara_pengeluaran',
            'bendahara_penerimaan' => 'bendahara_penerimaan',
            'kasubbag'             => 'kasubbag',
        ];

        $userIds = array_filter(array_unique(array_values($userIdByKey)));
        $users = User::with('profilable')->whereIn('id', $userIds)->get()->keyBy('id');

        $out = [];
        foreach ($userIdByKey as $key => $userId) {
            if (! isset($prefixMap[$key])) {
                continue;
            }
            $prefix = $prefixMap[$key];

            if (! $userId || ! $users->has($userId)) {
                $out[$prefix . '_user_id'] = null;
                $out[$prefix . '_nama_snapshot'] = null;
                $out[$prefix . '_nip_snapshot'] = null;
                continue;
            }

            $u = $users->get($userId);
            $profile = $u->profilable;
            $out[$prefix . '_user_id'] = (int) $u->id;
            $out[$prefix . '_nama_snapshot'] = $u->name;
            $out[$prefix . '_nip_snapshot'] = $profile && isset($profile->nip) ? $profile->nip : null;
        }

        return $out;
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
