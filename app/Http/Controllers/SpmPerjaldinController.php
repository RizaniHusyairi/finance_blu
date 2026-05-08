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

class SpmPerjaldinController extends Controller
{
    private const APPROVED_SPP_STATUSES = [
        'APPROVED',
        'DISETUJUI_SPP',
        'Disetujui PPK',
    ];

    /**
     * Halaman daftar SPM Perjaldin — antrean kerja Operator BLU.
     */
    public function index(Request $request)
    {
        $approvedSppStatuses = self::APPROVED_SPP_STATUSES;

        $query = DokumenSpp::whereNotNull('tagihan_perjaldin_komponen_id')
            ->where(function ($q) use ($approvedSppStatuses) {
                $q->where(function ($sq) use ($approvedSppStatuses) {
                    $sq->whereIn('status', $approvedSppStatuses)
                       ->has('signedSppArsip');
                })->orWhereHas('spm');
            })
            ->with([
                'tagihan.detailPerjaldin',
                'tagihanPerjaldinKomponen.dipaRevisionItem.coa',
                'spm.ppspm',
                'spm.workflowInstances.approvals',
                'spm.logs.user',
                'dipaRevisionItem.coa',
                'dibuatOleh',
            ]);

        // Filter
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
            $query->whereHas('spm', fn ($q) => $q->whereIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_MENUNGGU_UPLOAD, DokumenSpm::STATUS_SPM_TERBIT]));
        }

        // Search
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_spp', 'like', "%{$search}%")
                  ->orWhereHas('spm', fn ($sq) => $sq->where('nomor_spm', 'like', "%{$search}%"))
                  ->orWhereHas('tagihan', fn ($sq) => $sq->where('nomor_tagihan', 'like', "%{$search}%"));
            });
        }

        $sppList = $query->latest()->get();

        $summary = [
            'belum_dibuat' => $sppList->filter(fn ($spp) => !$spp->spm)->count(),
            'draft_revisi' => $sppList->filter(fn ($spp) => $spp->spm && in_array($spp->spm->status, ['DRAFT', DokumenSpm::STATUS_REVISI]))->count(),
            'menunggu'     => $sppList->filter(fn ($spp) => $spp->spm && $spp->spm->status === DokumenSpm::STATUS_MENUNGGU_VERIFIKASI)->count(),
            'selesai'      => $sppList->filter(fn ($spp) => $spp->spm && in_array($spp->spm->status, [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_MENUNGGU_UPLOAD, DokumenSpm::STATUS_SPM_TERBIT]))->count(),
        ];

        return view('spms.spm_perjaldin_index', compact('sppList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman detail/workspace SPM Perjaldin.
     */
    public function show($spp_id)
    {
        $sppModel = DokumenSpp::with([
            'tagihan.detailPerjaldin',
            'tagihan.komponenPerjaldin.dipaRevisionItem.coa',
            'tagihan.komponenPerjaldin.dokumenSpp',
            'tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'dipaRevisionItem.coa',
            'dibuatOleh',
            'ppkVerifikator',
            'spm.ppspm',
            'spm.dibuatOleh',
            'spm.workflowInstances.approvals.assignedUser',
            'spm.workflowInstances.approvals.actedByUser',
            'spm.logs.user',
            'logs.user',
            'signedSppArsip',
        ])
        ->whereNotNull('tagihan_perjaldin_komponen_id')
        ->findOrFail($spp_id);

        $tagihan   = $sppModel->tagihan;
        $komponen  = $sppModel->tagihanPerjaldinKomponen;
        $selectedBudgetItem = $komponen?->dipaRevisionItem;
        $spmModel  = $sppModel->spm;

        $ppspms = User::role('PPSPM')->orderByDisplayName()->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderByDisplayName()->first();
        $koordinatorUser = User::role('Koordinator Keuangan')->orderByDisplayName()->first();

        // Nominal SPM = nominal SPP
        $nominalSpm = (float) ($sppModel->nominal_spp ?? 0);

        // Readiness checklist
        $draftReady = $spmModel
            && filled($spmModel->nomor_spm)
            && filled($spmModel->tanggal_spm)
            && (float) ($spmModel->nominal_spm ?? 0) > 0
            && filled($spmModel->ppspm_id);

        $readinessChecklist = collect([
            [
                'label'  => 'SPP sumber tersedia & disetujui',
                'status' => in_array($sppModel->status, self::APPROVED_SPP_STATUSES, true) ? 'ready' : 'missing',
                'hint'   => in_array($sppModel->status, self::APPROVED_SPP_STATUSES, true)
                    ? 'SPP Perjaldin sudah disetujui dan siap jadi dasar SPM.'
                    : 'SPP belum dalam status disetujui.',
            ],

            [
                'label'  => 'Item DIPA / COA valid',
                'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing',
                'hint'   => filled($selectedBudgetItem?->coa)
                    ? 'Item anggaran sudah terpilih.'
                    : 'Item DIPA / COA belum tersedia.',
            ],
            [
                'label'  => 'Draft SPM sudah lengkap',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint'   => $draftReady
                    ? 'Nomor, tanggal, dan verifikator SPM sudah terisi.'
                    : 'Draft SPM belum lengkap atau belum disimpan.',
            ],
            [
                'label'  => 'Verifikator PPSPM dipilih',
                'status' => filled($spmModel?->ppspm_id) ? 'ready' : 'missing',
                'hint'   => filled($spmModel?->ppspm_id)
                    ? 'Verifikator PPSPM sudah ditentukan.'
                    : 'Pilih verifikator PPSPM pada draft SPM.',
            ],
        ])->values();

        $readinessIssues = $readinessChecklist->where('status', 'missing')->pluck('hint')->filter()->values();
        $isChecklistComplete = $readinessIssues->isEmpty();

        // Status SPM
        $statusSpm   = $spmModel?->status ?? 'Belum Dibuat';
        $canEditSpm  = !$spmModel || in_array($spmModel->status, ['DRAFT', DokumenSpm::STATUS_REVISI, '']);
        $canSubmit   = $spmModel && in_array($spmModel->status, ['DRAFT', DokumenSpm::STATUS_REVISI]);
        $isReadyToSubmit = $canSubmit && $isChecklistComplete;

        // Workflow
        $latestWorkflowInstance = collect($spmModel?->workflowInstances ?? [])->sortByDesc('created_at')->first();
        $ppspmApproval    = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'PPSPM');
        $kasubbagApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $koordinatorApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Koordinator Keuangan');

        // Progress step
        $progressStep = 1;
        if ($spmModel && in_array($spmModel->status, [DokumenSpm::STATUS_MENUNGGU_VERIFIKASI, DokumenSpm::STATUS_REVISI])) {
            $progressStep = 2;
        } elseif ($spmModel && $spmModel->status === DokumenSpm::STATUS_MENUNGGU_UPLOAD) {
            $progressStep = 3;
        } elseif ($spmModel && in_array($spmModel->status, [DokumenSpm::STATUS_SPM_TERBIT, DokumenSpm::STATUS_DISETUJUI_FINAL])) {
            $progressStep = 4;
        }

        // Signed SPM file check
        $hasSignedSpmFile = $spmModel?->hasSignedSpmFile() ?? false;

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
                    'time'  => optional($log->created_at)?->format('d M Y H:i'),
                    'actor' => $log->user?->name,
                    'note'  => $log->catatan,
                ];
            })->values();

        $oldPpspmId = old('ppspm_id', $spmModel?->ppspm_id);

        $autoNomorSpm = \App\Services\DocumentNumberingService::generateDerivedNumber($sppModel->nomor_spp, 'SPM');

        return view('spms.spm_perjaldin_detail', compact(
            'sppModel',
            'tagihan',
            'komponen',
            'selectedBudgetItem',
            'spmModel',
            'ppspms',
            'kasubbagUser',
            'nominalSpm',
            'readinessChecklist',
            'readinessIssues',
            'isChecklistComplete',
            'statusSpm',
            'canEditSpm',
            'canSubmit',
            'isReadyToSubmit',
            'ppspmApproval',
            'kasubbagApproval',
            'koordinatorApproval',
            'koordinatorUser',
            'progressStep',
            'recentActivities',
            'oldPpspmId',
            'autoNomorSpm',
            'hasSignedSpmFile'
        ));
    }

    /**
     * Simpan Draft SPM Perjaldin.
     */
    public function store(Request $request, $spp_id)
    {
        $spp = DokumenSpp::with(['spm', 'tagihanPerjaldinKomponen'])
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($spp_id);

        $existingSpm = $spp->spm;

        if (!$existingSpm && !in_array($spp->status, self::APPROVED_SPP_STATUSES, true)) {
            return back()->withErrors(['error' => 'SPP Perjaldin belum disetujui final, sehingga belum bisa dibuatkan SPM.']);
        }

        $request->validate([
            'nomor_spm'      => [
                'required', 'string', 'max:100',
                Rule::unique('dokumen_spm', 'nomor_spm')->ignore($existingSpm?->id),
            ],
            'tanggal_spm'    => 'required|date',
            'ppspm_id'       => 'required|exists:users,id',

        ]);

        DB::transaction(function () use ($request, $spp, $existingSpm) {
            $nominalSpm = (float) $spp->nominal_spp;

            $spm = DokumenSpm::updateOrCreate(
                ['id' => $existingSpm?->id],
                [
                    'spp_id'              => $spp->id,
                    'nomor_spm'           => $request->nomor_spm,
                    'tanggal_spm'         => $request->tanggal_spm,
                    'ppspm_id'            => $request->ppspm_id,
                    'dipa_revision_item_id' => $spp->tagihanPerjaldinKomponen?->dipa_revision_item_id ?? $spp->dipa_revision_item_id,
                    'tahun_anggaran'      => $spp->tagihanPerjaldinKomponen?->dipaRevisionItem?->revision?->dipa?->tahun_anggaran ?? date('Y'),
                    'jenis_tagihan'       => $spp->jenis_tagihan ?? 'NON REMUNERASI',
                    'jatuh_tempo'         => 'Segera',
                    'cara_bayar'          => 'SP2D BLU - TRF',
                    'nominal_spm'         => $nominalSpm,
                    'dibuat_oleh_id'      => auth()->id(),
                    'status'              => $existingSpm && $existingSpm->status === DokumenSpm::STATUS_REVISI
                        ? DokumenSpm::STATUS_REVISI
                        : 'DRAFT',
                ]
            );

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpm::class,
                'dokumen_id'        => $spm->id,
                'user_id'           => auth()->id(),
                'role_saat_itu'     => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $existingSpm?->status,
                'status_baru'       => $spm->status,
                'aksi'              => $existingSpm ? 'UPDATE_DRAFT_SPM' : 'CREATE_DRAFT_SPM',
                'catatan'           => 'Draft SPM Perjaldin disimpan. PPSPM: ' . optional(User::find($request->ppspm_id))->name,
                'ip_address'        => request()->ip(),
            ]);
        });

        return redirect()->route('spms.perjaldin.detail', $spp->id)->with('success', 'Draft SPM Perjaldin berhasil disimpan.');
    }

    /**
     * Ajukan Verifikasi SPM — mulai workflow paralel PPSPM + Kasubbag.
     */
    public function submit($spp_id)
    {
        $spp = DokumenSpp::with(['spm'])
            ->whereNotNull('tagihan_perjaldin_komponen_id')
            ->findOrFail($spp_id);

        $spm = $spp->spm;

        if (!$spm) {
            return back()->withErrors(['error' => 'Dokumen SPM belum dibuat. Simpan draft terlebih dahulu.']);
        }

        if (!in_array($spm->status, ['DRAFT', DokumenSpm::STATUS_REVISI])) {
            return back()->withErrors(['error' => 'SPM tidak dalam status yang bisa diajukan (harus DRAFT atau Revisi).']);
        }

        if (!$spm->ppspm_id) {
            return back()->withErrors(['error' => 'Verifikator PPSPM belum dipilih.']);
        }

        DB::transaction(function () use ($spm) {
            $statusSebelumnya = $spm->status;

            $spm->update(['status' => DokumenSpm::STATUS_MENUNGGU_VERIFIKASI]);

            // Start workflow paralel PPSPM + Kasubbag + Koordinator Keuangan
            app(WorkflowService::class)->startWorkflow('SPM_PERJALDIN_PPSPM', $spm, $spm->ppspm_id);

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSpm::class,
                'dokumen_id'        => $spm->id,
                'user_id'           => auth()->id(),
                'role_saat_itu'     => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru'       => DokumenSpm::STATUS_MENUNGGU_VERIFIKASI,
                'aksi'              => 'SUBMIT_VERIFIKASI',
                'catatan'           => 'SPM Perjaldin diajukan untuk verifikasi paralel PPSPM + Koordinator Keuangan + Kasubbag.',
                'ip_address'        => request()->ip(),
            ]);
        });

        // Notifikasi
        $selectedPpspm = User::find($spm->ppspm_id);
        if ($selectedPpspm) {
            Notification::send($selectedPpspm, new WorkflowNotification([
                'title'   => 'SPM Perjaldin Diajukan',
                'message' => "SPM Perjaldin ({$spm->nomor_spm}) menunggu verifikasi Anda.",
                'url'     => route('verifikasi-ppspm.spm.index'),
                'icon'    => 'description',
                'color'   => 'primary',
            ]));
        }

        $koordinatorUsers = User::role('Koordinator Keuangan')->get();
        if ($koordinatorUsers->isNotEmpty()) {
            Notification::send($koordinatorUsers, new WorkflowNotification([
                'title'   => 'SPM Perjaldin Diajukan',
                'message' => "SPM Perjaldin ({$spm->nomor_spm}) menunggu verifikasi Anda.",
                'url'     => route('verifikasi-koordinator.spm.perjaldin.index'),
                'icon'    => 'description',
                'color'   => 'primary',
            ]));
        }

        $kasubbagUsers = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->get();
        if ($kasubbagUsers->isNotEmpty()) {
            Notification::send($kasubbagUsers, new WorkflowNotification([
                'title'   => 'SPM Perjaldin Diajukan',
                'message' => "SPM Perjaldin ({$spm->nomor_spm}) menunggu verifikasi Anda.",
                'url'     => route('verifikasi-kasubag.spm.perjaldin.index'),
                'icon'    => 'description',
                'color'   => 'primary',
            ]));
        }

        return redirect()->route('spms.perjaldin.detail', $spm->spp_id)->with('success', 'SPM Perjaldin berhasil diajukan untuk verifikasi.');
    }

    /**
     * Upload file SPM Bertandatangan.
     */
    public function uploadSignedSpm(Request $request, $spm_id)
    {
        $spm = DokumenSpm::findOrFail($spm_id);

        if (!in_array($spm->status, [DokumenSpm::STATUS_MENUNGGU_UPLOAD, DokumenSpm::STATUS_SPM_TERBIT, DokumenSpm::STATUS_DISETUJUI_FINAL])) {
            return back()->withErrors(['error' => 'SPM belum disetujui oleh semua verifikator.']);
        }

        $request->validate([
            'file_spm_ttd' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'file_spm_ttd.required' => 'File SPM Bertandatangan wajib diunggah.',
            'file_spm_ttd.mimes' => 'File harus berformat PDF, JPG, atau PNG.',
            'file_spm_ttd.max' => 'Ukuran file maksimal 10MB.',
        ]);

        DB::transaction(function () use ($request, $spm) {
            $file = $request->file('file_spm_ttd');
            $namaAsli = $file->getClientOriginalName();
            $path = $file->store('arsip_spm_signed/' . date('Y'), 'public');

            $spm->arsipDokumen()
                ->where('jenis_dokumen', DokumenSpm::SPM_SIGNED_ARCHIVE_TYPE)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $spm->arsipDokumen()->create([
                'jenis_dokumen' => DokumenSpm::SPM_SIGNED_ARCHIVE_TYPE,
                'nama_file_asli' => $namaAsli,
                'path_file' => $path,
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ]);

            $statusLama = $spm->status;
            $spm->update(['status' => DokumenSpm::STATUS_SPM_TERBIT]);

            if ($spm->spp && $spm->spp->tagihan) {
                $spm->spp->tagihan->update(['status' => 'SPM_TERBIT']);
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusLama,
                'status_baru' => DokumenSpm::STATUS_SPM_TERBIT,
                'aksi' => 'UPLOAD_SPM_BERTANDATANGAN',
                'catatan' => "File SPM Bertandatangan diunggah: {$namaAsli}. Status SPM berubah menjadi SPM Terbit.",
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'File SPM Bertandatangan berhasil diunggah. Status SPM telah berubah menjadi SPM Terbit.');
    }
}
