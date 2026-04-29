<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class SpmHonorController extends Controller
{
    /**
     * Halaman daftar SPM Honorarium — antrean kerja Operator BLU.
     */
    public function index(Request $request)
    {
        // Query: SPP Honorarium yang sudah disetujui_spp atau spp_terbit dan/atau sudah punya relasi SPM
        $query = DokumenSpp::whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->where(function ($q) {
                $q->whereIn('status', ['DISETUJUI_SPP', 'SPP_TERBIT'])
                  ->orWhereHas('spm');
            })
            ->with([
                'tagihan.detailHonorarium',
                'spm.ppspm',
                'spm.workflowInstances.approvals',
                'spm.logs.user',
                'dipaRevisionItem.coa',
                'ppkVerifikator',
            ]);

        // Filter status
        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'belum_dibuat') {
            $query->whereDoesntHave('spm');
        } elseif ($statusFilter === 'draft') {
            $query->whereHas('spm', fn ($q) => $q->where('status', 'DRAFT'));
        } elseif ($statusFilter === 'revisi') {
            $query->whereHas('spm', fn ($q) => $q->where('status', DokumenSpm::STATUS_REVISI));
        } elseif ($statusFilter === 'menunggu') {
            $query->whereHas('spm', fn ($q) => $q->where('status', DokumenSpm::STATUS_MENUNGGU_VERIFIKASI));
        } elseif ($statusFilter === 'selesai') {
            $query->whereHas('spm', fn ($q) => $q->where('status', DokumenSpm::STATUS_DISETUJUI_FINAL));
        }

        // Search
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spp', 'like', "%{$search}%")
                  ->orWhereHas('spm', fn ($sq) => $sq->where('nomor_spm', 'like', "%{$search}%"))
                  ->orWhereHas('tagihan', function ($sq) use ($search) {
                      $sq->where('nomor_tagihan', 'like', "%{$search}%")
                         ->orWhere('deskripsi', 'like', "%{$search}%");
                  })
                  ->orWhereHas('ppkVerifikator', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $sppList = $query->latest()->get();

        // Summary cards
        $summary = [
            'belum_dibuat' => $sppList->filter(fn ($spp) => !$spp->spm)->count(),
            'draft_revisi' => $sppList->filter(fn ($spp) => $spp->spm && in_array($spp->spm->status, ['DRAFT', DokumenSpm::STATUS_REVISI]))->count(),
            'menunggu' => $sppList->filter(fn ($spp) => $spp->spm && $spp->spm->status === DokumenSpm::STATUS_MENUNGGU_VERIFIKASI)->count(),
            'selesai' => $sppList->filter(fn ($spp) => $spp->spm && $spp->spm->status === DokumenSpm::STATUS_DISETUJUI_FINAL)->count(),
        ];

        return view('spms.honor_index', compact('sppList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman detail/workspace SPM Honorarium.
     */
    public function show($spp_id)
    {
        $sppModel = DokumenSpp::with([
            'tagihan',
            'tagihan.detailHonorarium',
            'tagihan.arsipDokumen',
            'tagihan.logs.user',
            'dipaRevisionItem.coa',
            'ppkVerifikator',
            'dibuatOleh',
            'spm.ppspm',
            'spm.dibuatOleh',
            'spm.dipaRevisionItem.coa',
            'spm.workflowInstances.approvals.assignedUser',
            'spm.workflowInstances.approvals.actedByUser',
            'spm.logs.user',
        ])->findOrFail($spp_id);

        $tagihan = $sppModel->tagihan;
        $dipa = $sppModel->dipaRevisionItem?->revision?->dipa;
        $activeRevision = $dipa?->activeRevision;
        $selectedBudgetItem = $sppModel->dipaRevisionItem;
        $spmModel = $sppModel->spm;

        $ppspms = User::role('PPSPM')->orderByDisplayName()->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderByDisplayName()->first();

        // Nominal SPM = nominal SPP (otomatis penuh, pph sudah netto)
        $nominalSpm = (float) ($sppModel->nominal_spp ?? $tagihan->total_netto ?? 0);

        // Dokumen pendukung Honorarium
        $arsipJenis = $tagihan->arsipDokumen->pluck('jenis_dokumen')->toArray();
        $dokumenWajib = ['SK Honorarium', 'Daftar Nominatif Bertandatangan', 'Dokumen Honorarium Bertandatangan'];
        
        $documentStatuses = collect($dokumenWajib)->map(function ($jenis) use ($tagihan, $arsipJenis) {
            $doc = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', $jenis);
            return [
                'label' => $jenis,
                'path' => $doc?->file_path,
                'status' => in_array($jenis, $arsipJenis) ? 'ready' : 'missing',
                'is_available' => in_array($jenis, $arsipJenis)
            ];
        });

        // Readiness checklist
        $semuaPunyaRekening = $tagihan->detailHonorarium->every(fn($item) => filled($item->rekening) && filled($item->nama_rekening));
        
        $draftReady = $spmModel
            && filled($spmModel->nomor_spm)
            && filled($spmModel->tanggal_spm)
            && (float) ($spmModel->nominal_spm ?? 0) > 0
            && filled($spmModel->ppspm_id);

        $readinessChecklist = collect([
            [
                'label' => 'SPP Honorarium disetujui',
                'status' => in_array($sppModel->status, ['DISETUJUI_SPP', 'SPP_TERBIT']) ? 'ready' : 'missing',
                'hint' => in_array($sppModel->status, ['DISETUJUI_SPP', 'SPP_TERBIT'])
                    ? 'SPP sudah disetujui penuh oleh instrumen SPP.'
                    : 'SPP belum lolos verifikasi akhir SPP.',
            ],
            [
                'label' => 'Item DIPA / COA valid',
                'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing',
                'hint' => filled($selectedBudgetItem?->coa)
                    ? 'Item anggaran SPP diwariskan ke SPM.'
                    : 'Item DIPA / COA belum tersedia.',
            ],
            [
                'label' => 'Rekening Penerima Honor Lengkap',
                'status' => $semuaPunyaRekening ? 'ready' : 'missing',
                'hint' => $semuaPunyaRekening
                    ? 'Rincian rekening tiap personel tersedia.'
                    : 'Terdapat personel honorarium yang tidak memiliki data rekening valid.',
            ],
            [
                'label' => 'Draft SPM sudah lengkap',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint' => $draftReady
                    ? 'Nomor, tanggal, dan nilai SPM sudah diisi.'
                    : 'Draft SPM belum lengkap atau belum disimpan.',
            ],
            [
                'label' => 'Verifikator PPSPM dipilih',
                'status' => filled($spmModel?->ppspm_id) ? 'ready' : 'missing',
                'hint' => filled($spmModel?->ppspm_id)
                    ? 'Verifikator PPSPM sudah ditentukan.'
                    : 'Pilih verifikator PPSPM pada draft SPM.',
            ],
        ])->values();

        $readinessIssues = $readinessChecklist->where('status', 'missing')->pluck('hint')->filter()->values();

        // Status SPM
        $statusSpm = $spmModel?->status ?? 'Belum Dibuat';
        $canEditSpm = !$spmModel || in_array($spmModel->status, ['DRAFT', DokumenSpm::STATUS_REVISI, '']);
        $canSubmit = $spmModel && in_array($spmModel->status, ['DRAFT', DokumenSpm::STATUS_REVISI]);
        $isReadyToSubmit = $canSubmit && $readinessIssues->isEmpty();

        // Workflow
        $latestWorkflowInstance = collect($spmModel?->workflowInstances ?? [])->sortByDesc('created_at')->first();
        $ppspmApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'PPSPM');
        $kasubbagApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');

        // Progress step
        $progressStep = 1;
        if ($spmModel && in_array($spmModel->status, [DokumenSpm::STATUS_MENUNGGU_VERIFIKASI, DokumenSpm::STATUS_REVISI])) {
            $progressStep = 2;
        } elseif ($spmModel && $spmModel->status === DokumenSpm::STATUS_DISETUJUI_FINAL) {
            $progressStep = 4;
        }

        // Recent activities
        $recentActivities = collect($spmModel?->logs ?? [])
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function ($log) {
                $title = match ($log->aksi) {
                    'CREATE_DRAFT_SPM' => 'Draft SPM dibuat',
                    'UPDATE_DRAFT_SPM' => 'Draft SPM diperbarui',
                    'SUBMIT_VERIFIKASI' => 'SPM diajukan verifikasi',
                    default => str_replace('_', ' ', $log->aksi ?? 'Aktivitas'),
                };
                return [
                    'title' => $title,
                    'time' => optional($log->created_at)->format('d M Y H:i'),
                    'actor' => $log->user?->name,
                    'note' => $log->catatan,
                ];
            })->values();

        $autoNomorSpm = \App\Services\DocumentNumberingService::generateDerivedNumber($sppModel->nomor_spp, 'SPM');

        return view('spms.detail_honor', compact(
            'sppModel',
            'tagihan',
            'dipa',
            'activeRevision',
            'selectedBudgetItem',
            'spmModel',
            'ppspms',
            'kasubbagUser',
            'nominalSpm',
            'documentStatuses',
            'readinessChecklist',
            'readinessIssues',
            'statusSpm',
            'canEditSpm',
            'canSubmit',
            'isReadyToSubmit',
            'ppspmApproval',
            'kasubbagApproval',
            'progressStep',
            'recentActivities',
            'autoNomorSpm'
        ));
    }

    /**
     * Simpan Draft SPM (tanpa memicu workflow verifikasi).
     */
    public function store(Request $request, $spp_id)
    {
        $spp = DokumenSpp::with(['spm', 'tagihan', 'dipaRevisionItem'])
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->findOrFail($spp_id);

        $existingSpm = $spp->spm;

        $request->validate([
            'nomor_spm' => [
                'required',
                'string',
                'max:100',
                Rule::unique('dokumen_spm', 'nomor_spm')->ignore($existingSpm?->id),
            ],
            'tanggal_spm' => 'required|date',
            'ppspm_id' => 'required|exists:users,id',
            'tahun_anggaran' => 'nullable|string|max:10',
            'jenis_tagihan' => 'nullable|string|max:50',
            'jatuh_tempo' => 'nullable|string|max:50',
            'cara_bayar' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($request, $spp, $existingSpm) {
            $nominalSpm = (float) $spp->nominal_spp;

            $spm = DokumenSpm::updateOrCreate(
                ['id' => $existingSpm?->id],
                [
                    'spp_id' => $spp->id,
                    'nomor_spm' => $request->nomor_spm,
                    'tanggal_spm' => $request->tanggal_spm,
                    'ppspm_id' => $request->ppspm_id,
                    'dipa_revision_item_id' => $spp->dipa_revision_item_id,
                    'tahun_anggaran' => $request->tahun_anggaran ?? date('Y'),
                    'jenis_tagihan' => $request->jenis_tagihan ?? 'NON REMUNERASI',
                    'jatuh_tempo' => $request->jatuh_tempo ?? 'Segera',
                    'cara_bayar' => $request->cara_bayar ?? 'SP2D BLU - TRF',
                    'nominal_spm' => $nominalSpm, // Menguasakan nilai Netto
                    'dibuat_oleh_id' => auth()->id(),
                    'status' => $existingSpm && $existingSpm->status === DokumenSpm::STATUS_REVISI
                        ? DokumenSpm::STATUS_REVISI
                        : 'DRAFT',
                ]
            );

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $existingSpm?->status,
                'status_baru' => $spm->status,
                'aksi' => $existingSpm ? 'UPDATE_DRAFT_SPM' : 'CREATE_DRAFT_SPM',
                'catatan' => 'Draft SPM honorarium disimpan. Menunjuk PPSPM: ' . optional(User::find($request->ppspm_id))->name,
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('spms.honor.detail', $spp->id)->with('success', 'Draft SPM Honorarium berhasil disimpan.');
    }

    /**
     * Ajukan Verifikasi SPM — memulai workflow paralel SPM_HONORARIUM_PPSPM.
     */
    public function submit($spp_id)
    {
        $spp = DokumenSpp::with(['spm', 'tagihan'])
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->findOrFail($spp_id);

        $spm = $spp->spm;

        if (!$spm) {
            return back()->withErrors(['error' => 'Dokumen draft SPM belum dibuat.']);
        }

        if (!in_array($spm->status, ['DRAFT', DokumenSpm::STATUS_REVISI])) {
            return back()->withErrors(['error' => 'SPM tidak dalam status DRAFT atau Revisi.']);
        }

        if (!$spm->ppspm_id) {
            return back()->withErrors(['error' => 'Verifikator PPSPM belum ditunjuk.']);
        }

        DB::transaction(function () use ($spm, $spp) {
            $statusSebelumnya = $spm->status;

            $spm->update(['status' => DokumenSpm::STATUS_MENUNGGU_VERIFIKASI]);

            // Sinkron status Tagihan (opsional as per existing convention)
            if (!in_array($spp->tagihan->status, ['SPM_TERBIT', 'SEBAGIAN_SPM_TERBIT'])) {
                 $spp->tagihan->update(['status' => 'SEBAGIAN_SPM_TERBIT']);
            }

            // Start Workflow spesifik Honorarium SPM yang didefinisikan secara independen
            app(WorkflowService::class)->startWorkflow('SPM_HONORARIUM_PPSPM', $spm, $spm->ppspm_id);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => DokumenSpm::STATUS_MENUNGGU_VERIFIKASI,
                'aksi' => 'SUBMIT_VERIFIKASI',
                'catatan' => 'SPM Honorarium diajukan untuk verifikasi paralel (PPSPM & Kasubbag).',
                'ip_address' => request()->ip(),
            ]);
        });

        // Broadcast Lonceng Notifikasi Kasubbag & PPSPM
        $selectedPpspm = User::find($spm->ppspm_id);
        if ($selectedPpspm) {
            Notification::send($selectedPpspm, new WorkflowNotification([
                'title' => 'SPM Honorarium Menunggu Anda',
                'message' => "SPM Honorarium ({$spm->nomor_spm}) untuk pengajuan {$spp->nomor_spp} menanti verifikasi.",
                'url' => '#', // Disesuaikan saat modul Verifikasi PPSPM Honor dibangun nanti
                'icon' => 'fact_check',
                'color' => 'success',
            ]));
        }

        $kasubbagUsers = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->get();
        if ($kasubbagUsers->isNotEmpty()) {
            Notification::send($kasubbagUsers, new WorkflowNotification([
                'title' => 'Ceklist SPM Honorarium',
                'message' => "Ada SPM Honorarium yang baru diajukan ({$spm->nomor_spm}) yang menanti persetujuan Kasubbag.",
                'url' => '#', 
                'icon' => 'fact_check',
                'color' => 'success',
            ]));
        }

        return redirect()->route('spms.honor.detail', $spm->spp_id)->with('success', 'Dokumen SPM Honorarium telah diajukan kepada Verifikator.');
    }
}
