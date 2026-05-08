<?php

namespace App\Http\Controllers;

use App\Models\DokumenNpi;
use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NpiKontrakController extends Controller
{
    /**
     * Halaman daftar antrean NPI Kontrak untuk Bendahara Pengeluaran
     */
    public function index(Request $request)
    {
        // Query SPM Kontrak yang sudah FINAL 
        $query = DokumenSpm::whereHas('spp.tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereIn('status', [DokumenSpm::STATUS_SPM_TERBIT, DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_APPROVED_KASUBAG])
            ->with([
                'npi',
                'npi.workflowInstances.approvals',
                'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor'
            ]);

        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'belum_dibuat') {
            $query->whereDoesntHave('npi');
        } elseif ($statusFilter === 'draft') {
            $query->whereHas('npi', fn ($q) => $q->where('status', DokumenNpi::STATUS_DRAFT));
        } elseif ($statusFilter === 'revisi') {
            $query->whereHas('npi', fn ($q) => $q->where('status', DokumenNpi::STATUS_REVISI));
        } elseif ($statusFilter === 'menunggu') {
            $query->whereHas('npi', fn ($q) => $q->whereIn('status', [
                DokumenNpi::STATUS_MENUNGGU_VERIFIKASI,
                DokumenNpi::STATUS_SUBMITTED_BENPEN,
                DokumenNpi::STATUS_SUBMITTED_PPK,
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
            ]));
        } elseif ($statusFilter === 'selesai') {
            $query->whereHas('npi', fn ($q) => $q->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG]));
        }

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spm', 'like', "%{$search}%")
                  ->orWhereHas('npi', fn ($sq) => $sq->where('nomor_npi', 'like', "%{$search}%"))
                  ->orWhereHas('spp', fn ($sq) => $sq->where('nomor_spp', 'like', "%{$search}%"))
                  ->orWhereHas('spp.tagihan', fn ($sq) => $sq->where('nomor_tagihan', 'like', "%{$search}%"))
                  ->orWhereHas('spp.tagihan.detailKontrak.kontrakTermin.kontrak', function ($sq) use ($search) {
                      $sq->where('nomor_spk', 'like', "%{$search}%")->orWhere('nama_pekerjaan', 'like', "%{$search}%");
                  })
                  ->orWhereHas('spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor', fn ($sq) => $sq->where('nama_pihak', 'like', "%{$search}%"));
            });
        }

        $spmList = $query->latest()->get();

        $summary = [
            'belum_dibuat' => $spmList->filter(fn ($spm) => !$spm->npi)->count(),
            'draft_revisi' => $spmList->filter(fn ($spm) => $spm->npi && in_array($spm->npi->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI]))->count(),
            'menunggu' => $spmList->filter(fn ($spm) => $spm->npi && in_array($spm->npi->status, [
                DokumenNpi::STATUS_MENUNGGU_VERIFIKASI,
                DokumenNpi::STATUS_SUBMITTED_BENPEN,
                DokumenNpi::STATUS_SUBMITTED_PPK,
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
            ]))->count(),
            'selesai' => $spmList->filter(fn ($spm) => $spm->npi && in_array($spm->npi->status, [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG]))->count(),
        ];

        return view('npis.kontrak_index', compact('spmList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman Detail Workspace NPI Kontrak
     */
    public function show($spm_id)
    {
        $spmModel = DokumenSpm::with([
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.dipaRevisionItem.coa',
            'spp.tagihan.bendaharaPenerimaanUser',
            'spp.tagihan.koordinatorKeuanganUser',
            'spp.tagihan.potonganTagihan.pajak',
            'npi.bendaharaPenerimaan',
            'npi.workflowInstances.approvals.assignedUser',
            'npi.workflowInstances.approvals.actedByUser',
            'npi.logs.user'
        ])->findOrFail($spm_id);

        $sppModel = $spmModel->spp;
        $tagihan = $sppModel?->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $termin = $detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();
        
        $npiModel = $spmModel->npi;

        $bendaharaPenerimaanTagihan = $tagihan?->bendaharaPenerimaanUser;
        // Fetch PPK from SPP and Kasubbag
        $ppkSpp = $sppModel?->ppkVerifikator;
        $koordinatorKeuanganUser = $tagihan?->koordinatorKeuanganUser ?: User::role('Koordinator Keuangan')->first();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();

        // Nominal
        $nominalNpi = (float) ($sppModel->nominal_spp ?? $tagihan->total_netto ?? 0);

        // Dokumen pendukung checklist (dari Tagihan)
        $isPelunasan = ($termin?->jenis_termin ?? null) === 'PELUNASAN';
        $potonganPajak = collect($tagihan?->potonganTagihan ?? [])->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
        $requiresTaxDocuments = $potonganPajak->isNotEmpty();
        $documentStatuses = collect([
            ['key' => 'spm', 'label' => 'SPM', 'path' => true, 'required' => true],
            ['key' => 'spp', 'label' => 'SPP', 'path' => true, 'required' => true],
            ['key' => 'bapp', 'label' => 'BAPP', 'path' => $detailKontrak?->file_bapp, 'required' => true],
            ['key' => 'bast', 'label' => 'BAST', 'path' => $detailKontrak?->file_bast, 'required' => $isPelunasan],
            ['key' => 'bap', 'label' => 'BAP', 'path' => $detailKontrak?->file_bap, 'required' => true],
            ['key' => 'invoice', 'label' => 'Invoice', 'path' => $detailKontrak?->file_invoice, 'required' => true],
            ['key' => 'faktur_pajak', 'label' => 'Faktur Pajak', 'path' => $detailKontrak?->file_faktur_pajak, 'required' => $requiresTaxDocuments],
            ['key' => 'kwitansi', 'label' => 'Kwitansi', 'path' => $detailKontrak?->file_kwitansi ?? null, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');
            return array_merge($item, ['status' => $status, 'is_available' => $isAvailable]);
        })->values();

        // Readiness checklist
        $draftReady = $npiModel
            && filled($npiModel->nomor_npi)
            && filled($npiModel->tanggal_npi)
            && filled($bendaharaPenerimaanTagihan?->id);
        $rekeningReady = filled($rekening?->nama_bank) && filled($rekening?->nomor_rekening);
        
        $readinessChecklist = collect([
            [
                'label' => 'SPM sumber tersedia',
                'status' => 'ready', // by default it is ready if we are accessing this page and the query only shows final SPM
                'hint' => 'Dasar dokumen NPI telah disahkan.'
            ],
            [
                'label' => 'Data vendor & rekening lengkap',
                'status' => $rekeningReady ? 'ready' : 'missing',
                'hint' => $rekeningReady ? 'Informasi rekening pembayaran sudah valid.' : 'Data rekening tidak lengkap.'
            ],
            [
                'label' => 'Draft NPI dilengkapi',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint' => $draftReady
                    ? 'Nomor, Tanggal dan Bendahara Penerimaan dari tagihan telah ditentukan.'
                    : 'Nomor NPI belum lengkap atau Bendahara Penerimaan belum ditentukan pada tagihan.'
            ],
        ])->values();

        $readinessIssues = $readinessChecklist->where('status', 'missing')->pluck('hint')->filter()->values();

        $statusNpi = $npiModel?->status ?? 'Belum Dibuat';
        $canEditNpi = !$npiModel || in_array($npiModel->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI, '']);
        $canSubmit = $npiModel && in_array($npiModel->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI]);
        $isReadyToSubmit = $canSubmit && $readinessIssues->isEmpty();

        // Workflow tracking - PARALLEL
        $latestWorkflowInstance = collect($npiModel?->workflowInstances ?? [])->sortByDesc('created_at')->first();
        $benpenApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Bendahara Penerimaan');
        $ppkApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'PPK');
        $koordinatorApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Koordinator Keuangan');
        $kasubbagApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');

        // Recent activities
        $recentActivities = collect($npiModel?->logs ?? [])
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function ($log) {
                return [
                    'title' => str_replace('_', ' ', $log->aksi ?? 'Aktivitas'),
                    'time' => optional($log->created_at)->format('d M Y H:i'),
                    'actor' => $log->user?->name ?? 'Sistem',
                    'note' => $log->catatan,
                ];
            })->values();

        $autoNomorNpi = \App\Services\DocumentNumberingService::generateDerivedNumber($sppModel->nomor_spp, 'NPI');

        return view('npis.kontrak_detail', compact(
            'spmModel',
            'sppModel',
            'npiModel',
            'tagihan',
            'detailKontrak',
            'kontrak',
            'termin',
            'vendor',
            'rekening',
            'bendaharaPenerimaanTagihan',
            'nominalNpi',
            'documentStatuses',
            'readinessChecklist',
            'readinessIssues',
            'statusNpi',
            'canEditNpi',
            'canSubmit',
            'isReadyToSubmit',
            'benpenApproval',
            'ppkApproval',
            'koordinatorApproval',
            'kasubbagApproval',
            'recentActivities',
            'rekeningReady',
            'ppkSpp',
            'koordinatorKeuanganUser',
            'kasubbagUser',
            'autoNomorNpi'
        ));
    }

    /**
     * Menyimpan/Memperbarui NPI dengan status DRAFT.
     */
    public function store(Request $request, $spm_id)
    {
        $spm = DokumenSpm::with(['spp.tagihan.bendaharaPenerimaanUser', 'npi'])->findOrFail($spm_id);
        $existingNpi = $spm->npi;
        $bendaharaPenerimaan = $spm->spp?->tagihan?->bendaharaPenerimaanUser;

        if (!$bendaharaPenerimaan || !$bendaharaPenerimaan->hasRole('Bendahara Penerimaan')) {
            return back()
                ->withInput()
                ->withErrors(['bendahara_penerimaan_id' => 'Verifikator Bendahara Penerimaan belum ditentukan pada tagihan. Lengkapi data tagihan terlebih dahulu.']);
        }

        $request->validate([
            'nomor_npi' => 'required|string|max:100',
            'tanggal_npi' => 'required|date',
            'tahun_anggaran' => 'nullable|string|max:10',
            'uraian_npi' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request, $spm, $existingNpi, $bendaharaPenerimaan) {
            $npi = DokumenNpi::updateOrCreate(
                ['id' => $existingNpi?->id],
                [
                    'spm_id' => $spm->id,
                    'nomor_npi' => $request->nomor_npi,
                    'tanggal_npi' => $request->tanggal_npi,
                    'bendahara_penerimaan_id' => $bendaharaPenerimaan->id,
                    'tahun_anggaran' => $request->tahun_anggaran ?? date('Y'),
                    'status' => $existingNpi && $existingNpi->status === DokumenNpi::STATUS_REVISI
                                    ? DokumenNpi::STATUS_REVISI 
                                    : DokumenNpi::STATUS_DRAFT,
                ]
            );

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Bendahara Pengeluaran',
                'status_sebelumnya' => $existingNpi?->status,
                'status_baru' => $npi->status,
                'aksi' => $existingNpi ? 'UPDATE_DRAFT_NPI' : 'CREATE_DRAFT_NPI',
                'catatan' => 'Draft NPI disimpan.',
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('npis.kontrak.detail', $spm->id)->with('success', 'Draft NPI berhasil disimpan.');
    }

    /**
     * Memulai workflow paralel untuk verifikasi NPI, mengubah status ke MENUNGGU_VERIFIKASI.
     */
    public function submit($spm_id)
    {
        $spm = DokumenSpm::with(['npi', 'spp.tagihan.bendaharaPenerimaanUser'])->findOrFail($spm_id);
        $npi = $spm->npi;

        if (!$npi) {
            return back()->withErrors(['error' => 'Dokumen NPI belum dibuat. Silakan simpan draft terlebih dahulu.']);
        }

        if (!in_array($npi->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI])) {
            return back()->withErrors(['error' => 'NPI tidak dapat diajukan (bukan status draft atau revisi).']);
        }

        $bendaharaPenerimaan = $spm->spp?->tagihan?->bendaharaPenerimaanUser;

        if (!$bendaharaPenerimaan || !$bendaharaPenerimaan->hasRole('Bendahara Penerimaan')) {
            return back()->withErrors(['bendahara_penerimaan_id' => 'Verifikator Bendahara Penerimaan belum ditentukan pada tagihan. Lengkapi data tagihan terlebih dahulu.']);
        }

        DB::transaction(function () use ($npi, $spm, $bendaharaPenerimaan) {
            $statusSebelumnya = $npi->status;

            // Updated status
            $npi->update([
                'bendahara_penerimaan_id' => $bendaharaPenerimaan->id,
                'status' => DokumenNpi::STATUS_MENUNGGU_VERIFIKASI,
            ]);

            $ppkId = $spm->spp?->ppk_verifikator_id;
            app(WorkflowService::class)->startWorkflow('NPI_KONTRAK', $npi, $ppkId); // PPK assignee

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Bendahara Pengeluaran',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => DokumenNpi::STATUS_MENUNGGU_VERIFIKASI,
                'aksi' => 'SUBMIT_VERIFIKASI_NPI',
                'catatan' => 'NPI diajukan secara paralel untuk diverifikasi.',
                'ip_address' => request()->ip(),
            ]);
        });

        // Notifications
        $usersToNotify = collect();
        if ($npi->bendahara_penerimaan_id) {
            $usersToNotify->push(User::find($npi->bendahara_penerimaan_id));
        }
        $usersToNotify = $usersToNotify->merge(User::role(['PPK', 'Koordinator Keuangan', 'Kepala Subbagian Keuangan dan Tata Usaha'])->get());
        
        $usersToNotify = $usersToNotify->filter()->unique('id');

        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new WorkflowNotification([
                'title' => 'NPI Kontrak Diajukan',
                'message' => "NPI Kontrak ({$npi->nomor_npi}) menunggu verifikasi pada dashboard tugas Anda.",
                // They will see it in their respective index later
                'url' => '#', 
                'icon' => 'receipt_long',
                'color' => 'primary',
            ]));
        }

        return redirect()->route('npis.kontrak.detail', $spm->id)->with('success', 'NPI berhasil diajukan untuk verifikasi paralel.');
    }

    /**
     * Upload file NPI Bertandatangan.
     */
    public function uploadSignedNpi(Request $request, $npi_id)
    {
        $npi = DokumenNpi::findOrFail($npi_id);

        if (!in_array($npi->status, [DokumenNpi::STATUS_MENUNGGU_UPLOAD, DokumenNpi::STATUS_NPI_TERBIT, DokumenNpi::STATUS_DISETUJUI_FINAL])) {
            return back()->withErrors(['error' => 'NPI belum disetujui oleh semua verifikator.']);
        }

        $request->validate([
            'file_npi_ttd' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'file_npi_ttd.required' => 'File NPI Bertandatangan wajib diunggah.',
            'file_npi_ttd.mimes' => 'File harus berformat PDF, JPG, atau PNG.',
            'file_npi_ttd.max' => 'Ukuran file maksimal 10MB.',
        ]);

        DB::transaction(function () use ($request, $npi) {
            $file = $request->file('file_npi_ttd');
            $namaAsli = $file->getClientOriginalName();
            $path = $file->store('arsip_npi_signed/' . date('Y'), 'public');

            // Nonaktifkan arsip lama (jika ada re-upload)
            $npi->arsipDokumen()
                ->where('jenis_dokumen', DokumenNpi::NPI_SIGNED_ARCHIVE_TYPE)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Buat arsip baru
            $npi->arsipDokumen()->create([
                'jenis_dokumen' => DokumenNpi::NPI_SIGNED_ARCHIVE_TYPE,
                'nama_file_asli' => $namaAsli,
                'path_file' => $path,
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ]);

            // Update status ke NPI_TERBIT
            if ($npi->status !== DokumenNpi::STATUS_NPI_TERBIT) {
                $statusSebelumnya = $npi->status;
                $npi->update(['status' => DokumenNpi::STATUS_NPI_TERBIT]);

                LogStatusDokumen::create([
                    'dokumen_type' => DokumenNpi::class,
                    'dokumen_id' => $npi->id,
                    'user_id' => auth()->id(),
                    'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Bendahara Pengeluaran',
                    'status_sebelumnya' => $statusSebelumnya,
                    'status_baru' => DokumenNpi::STATUS_NPI_TERBIT,
                    'aksi' => 'UPLOAD_SIGNED_NPI',
                    'catatan' => 'File fisik NPI yang telah ditandatangani berhasil diunggah.',
                    'ip_address' => request()->ip(),
                ]);
            }
        });

        return back()->with('success', 'File NPI Bertandatangan berhasil diunggah dan NPI resmi terbit.');
    }
}
