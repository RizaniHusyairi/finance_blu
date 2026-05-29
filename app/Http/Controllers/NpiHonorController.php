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

class NpiHonorController extends Controller
{
    /**
     * Halaman daftar antrean SPM Honorarium yang siap/menunggu pembuatan NPI
     */
    public function index(Request $request)
    {
        // Query SPM Honorarium yang sudah FINAL
        $query = DokumenSpm::whereHas('spp.tagihan', function($q) {
                $q->where('tipe_tagihan', 'HONORARIUM');
            })
            ->whereIn('status', [DokumenSpm::STATUS_SPM_TERBIT, DokumenSpm::STATUS_DISETUJUI_FINAL])
            ->with([
                'npi',
                'npi.workflowInstances.approvals',
                'spp.tagihan.detailHonorarium',
                'dipaRevisionItem.coa'
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
                  ->orWhereHas('spp.tagihan', fn ($sq) => $sq->where('nomor_tagihan', 'like', "%{$search}%")
                                                            ->orWhere('deskripsi', 'like', "%{$search}%"));
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

        return view('npis.honor_index', compact('spmList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman Detail Workspace NPI Honorarium
     */
    public function show($spm_id)
    {
        $spmModel = DokumenSpm::whereHas('spp.tagihan', function($q) {
                $q->where('tipe_tagihan', 'HONORARIUM');
            })
            ->with([
                'spp.tagihan.detailHonorarium',
                'spp.tagihan.bendaharaPenerimaanUser',
                'spp.tagihan.koordinatorKeuanganUser',
                'spp.tagihan.arsipDokumen',
                'dipaRevisionItem.coa',
                'npi.bendaharaPenerimaan',
                'npi.workflowInstances.approvals.assignedUser',
                'npi.workflowInstances.approvals.actedByUser',
                'npi.logs.user'
            ])->findOrFail($spm_id);

        $sppModel = $spmModel->spp;
        $tagihan = $sppModel?->tagihan;
        
        $npiModel = $spmModel->npi;

        $bendaharaPenerimaanTagihan = $tagihan?->bendaharaPenerimaanUser;
        // Verifikator lain untuk info tampilan (Opsional jika ditampilkan)
        $ppkSpp = $sppModel?->ppkVerifikator;
        $koordinatorKeuanganUser = $tagihan?->koordinatorKeuanganUser ?: User::role('Koordinator Keuangan')->first();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();

        // Nominal
        $nominalNpi = (float) ($spmModel->nominal_spm ?? $tagihan->total_netto ?? 0);

        // Kelengkapan dokumen pendukung (SPM + SPP + arsip dokumen tagihan)
        $documentStatuses = collect([
            ['label' => 'SPM Honorarium', 'path' => true, 'required' => true, 'status' => 'ready'],
            ['label' => 'SPP', 'path' => true, 'required' => true, 'status' => 'ready'],
        ])->concat(
            collect($tagihan?->arsipDokumen ?? [])
                ->filter(fn ($arsip) => ($arsip->is_active ?? true))
                ->map(fn ($arsip) => [
                    'label' => \Illuminate\Support\Str::title(str_replace('_', ' ', strtolower($arsip->jenis_dokumen ?? 'Dokumen Pendukung'))),
                    'path' => $arsip->path_file,
                    'required' => false,
                    'status' => 'ready',
                ])
        )->values();

        // Readiness checklist
        $draftReady = $npiModel
            && filled($npiModel->nomor_npi)
            && filled($npiModel->tanggal_npi)
            && filled($bendaharaPenerimaanTagihan?->id);
        
        $readinessChecklist = collect([
            [
                'label' => 'SPM sumber Honorarium tersedia dan final',
                'status' => 'ready', // by default it is ready if it appears here
                'hint' => 'Dokumen SPM Honorarium telah disahkan.'
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

        // Workflow tracking - PARALLEL (Like Perjaldin)
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

        return view('npis.honor_detail', compact(
            'spmModel',
            'sppModel',
            'npiModel',
            'tagihan',
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
            'ppkSpp',
            'koordinatorKeuanganUser',
            'kasubbagUser',
            'autoNomorNpi'
        ));
    }

    /**
     * Menyimpan/Memperbarui NPI Honorarium dengan status DRAFT.
     */
    public function store(Request $request, $spm_id)
    {
        $spm = DokumenSpm::whereHas('spp.tagihan', function($q) {
                $q->where('tipe_tagihan', 'HONORARIUM');
            })
            ->with(['spp.tagihan.bendaharaPenerimaanUser', 'npi'])
            ->findOrFail($spm_id);
            
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
                    'uraian_npi' => $request->uraian_npi,
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
                'aksi' => $existingNpi ? 'UPDATE_DRAFT_NPI_HONOR' : 'CREATE_DRAFT_NPI_HONOR',
                'catatan' => 'Draft NPI Honorarium disimpan.',
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('npis.honor.detail', $spm->id)->with('success', 'Draft NPI Honorarium berhasil disimpan.');
    }

    /**
     * Memulai workflow paralel untuk verifikasi NPI Honorarium.
     */
    public function submit($spm_id)
    {
        $spm = DokumenSpm::with(['npi', 'spp.tagihan.bendaharaPenerimaanUser'])->findOrFail($spm_id);
        $npi = $spm->npi;

        if (!$npi) {
            return back()->withErrors(['error' => 'Dokumen NPI belum dibuat. Silakan simpan form draf terlebih dahulu untuk Operator.']);
        }

        if (!in_array($npi->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI])) {
            return back()->withErrors(['error' => 'NPI Honorarium ini tidak dapat diajukan (bukan status draft atau revisi).']);
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

            // Sync sumber tagihan root sesuai demand client di "Open Questions"
            $spm->spp->tagihan->update(['status' => 'NPI_TERBIT']);

            $ppkId = $spm->spp?->ppk_verifikator_id; // Using SPP's PPK as assignee if needed.
            app(WorkflowService::class)->startWorkflow('NPI_HONORARIUM', $npi, $ppkId);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenNpi::class,
                'dokumen_id' => $npi->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Bendahara Pengeluaran',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => DokumenNpi::STATUS_MENUNGGU_VERIFIKASI,
                'aksi' => 'SUBMIT_VERIFIKASI_NPI_HONOR',
                'catatan' => 'NPI Honorarium direkayasa dan diserbu paralel ke meja verifikator.',
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
                'title' => 'NPI Honorarium Diajukan',
                'message' => "NPI Honorarium ({$npi->nomor_npi}) siap Anda verifikasi melalu loket persetujuan ganda.",
                'url' => '#', 
                'icon' => 'receipt_long',
                'color' => 'success',
            ]));
        }

        return redirect()->route('npis.honor.detail', $spm->id)->with('success', 'NPI Honorarium dirilis menujui antrean persetujuan Verifikasi Paralel secara sukses.');
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
