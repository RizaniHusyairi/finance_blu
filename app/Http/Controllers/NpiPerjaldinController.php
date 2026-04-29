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

class NpiPerjaldinController extends Controller
{
    /**
     * Halaman daftar antrean NPI Perjaldin untuk Bendahara Pengeluaran
     */
    public function index(Request $request)
    {
        // Query SPM Perjaldin yang sudah FINAL 
        // Note: Perjaldin ditandai dengan spp memiliki tagihan_perjaldin_komponen_id tidak null
        $query = DokumenSpm::whereHas('spp', function($q) {
                $q->whereNotNull('tagihan_perjaldin_komponen_id');
            })
            ->whereIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_APPROVED_KASUBAG])
            ->with([
                'npi',
                'npi.workflowInstances.approvals',
                'spp.tagihan.detailPerjaldin.pegawai',
                'spp.tagihan.detailPerjaldin.provinsi',
                'spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa'
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

        return view('npis.perjaldin_index', compact('spmList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman Detail Workspace NPI Perjaldin
     */
    public function show($spm_id)
    {
        $spmModel = DokumenSpm::whereHas('spp', function($q) {
                $q->whereNotNull('tagihan_perjaldin_komponen_id');
            })
            ->with([
                'spp.tagihan.detailPerjaldin.pegawai',
                'spp.tagihan.detailPerjaldin.provinsi',
                'spp.tagihan.komponenPerjaldin',
                'spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa',
                'npi.bendaharaPenerimaan',
                'npi.workflowInstances.approvals.assignedUser',
                'npi.workflowInstances.approvals.actedByUser',
                'npi.logs.user'
            ])->findOrFail($spm_id);

        $sppModel = $spmModel->spp;
        $tagihan = $sppModel?->tagihan;
        $komponenSpp = $sppModel?->tagihanPerjaldinKomponen;
        
        $npiModel = $spmModel->npi;

        $bendaharaPenerimaans = User::role('Bendahara Penerimaan')->orderByDisplayName()->get();
        // Fetch PPK & Kasubbag
        $ppkSpp = $sppModel?->ppkVerifikator; // Asumsi jika SPP menyimpan info verifikator PPK
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();

        // Nominal
        $nominalNpi = (float) ($sppModel->nominal_spp ?? $tagihan->total_netto ?? 0);

        // Readiness checklist
        $draftReady = $npiModel && filled($npiModel->nomor_npi) && filled($npiModel->tanggal_npi) && filled($npiModel->bendahara_penerimaan_id);
        
        $readinessChecklist = collect([
            [
                'label' => 'SPM sumber tersedia dan final',
                'status' => 'ready', // by default it is ready if we are accessing this page and the query only shows final SPM
                'hint' => 'Dokumen SPM Perjaldin telah disahkan.'
            ],
            [
                'label' => 'Draft NPI dilengkapi',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint' => $draftReady ? 'Nomor, Tanggal dan Bendahara Penerimaan telah ditentukan.' : 'Bendahara Penerimaan & Nomor NPI belum diatur di Draft.'
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

        return view('npis.perjaldin_detail', compact(
            'spmModel',
            'sppModel',
            'npiModel',
            'tagihan',
            'komponenSpp',
            'bendaharaPenerimaans',
            'nominalNpi',
            'readinessChecklist',
            'readinessIssues',
            'statusNpi',
            'canEditNpi',
            'canSubmit',
            'isReadyToSubmit',
            'benpenApproval',
            'ppkApproval',
            'kasubbagApproval',
            'recentActivities',
            'ppkSpp',
            'kasubbagUser',
            'autoNomorNpi'
        ));
    }

    /**
     * Menyimpan/Memperbarui NPI dengan status DRAFT.
     */
    public function store(Request $request, $spm_id)
    {
        $spm = DokumenSpm::whereHas('spp', function($q) {
                $q->whereNotNull('tagihan_perjaldin_komponen_id');
            })->findOrFail($spm_id);
            
        $existingNpi = $spm->npi;

        $request->validate([
            'nomor_npi' => 'required|string|max:100',
            'tanggal_npi' => 'required|date',
            'bendahara_penerimaan_id' => 'required|exists:users,id',
            'tahun_anggaran' => 'nullable|string|max:10',
            'uraian_npi' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request, $spm, $existingNpi) {
            $npi = DokumenNpi::updateOrCreate(
                ['id' => $existingNpi?->id],
                [
                    'spm_id' => $spm->id,
                    'nomor_npi' => $request->nomor_npi,
                    'tanggal_npi' => $request->tanggal_npi,
                    'bendahara_penerimaan_id' => $request->bendahara_penerimaan_id,
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

        return redirect()->route('npis.perjaldin.detail', $spm->id)->with('success', 'Draft NPI berhasil disimpan.');
    }

    /**
     * Memulai workflow paralel untuk verifikasi NPI Perjaldin.
     */
    public function submit($spm_id)
    {
        $spm = DokumenSpm::with(['npi', 'spp'])->findOrFail($spm_id);
        $npi = $spm->npi;

        if (!$npi) {
            return back()->withErrors(['error' => 'Dokumen NPI belum dibuat. Silakan simpan draft terlebih dahulu.']);
        }

        if (!in_array($npi->status, [DokumenNpi::STATUS_DRAFT, DokumenNpi::STATUS_REVISI])) {
            return back()->withErrors(['error' => 'NPI tidak dapat diajukan (bukan status draft atau revisi).']);
        }

        DB::transaction(function () use ($npi, $spm) {
            $statusSebelumnya = $npi->status;

            // Updated status
            $npi->update(['status' => DokumenNpi::STATUS_MENUNGGU_VERIFIKASI]);

            $ppkId = $spm->spp?->ppk_verifikator_id; // Using SPP's PPK as assignee if needed, workflow engine handles standard rolenames
            app(WorkflowService::class)->startWorkflow('NPI_PERJALDIN', $npi, $ppkId);

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
        $usersToNotify = $usersToNotify->merge(User::role(['PPK', 'Kepala Subbagian Keuangan dan Tata Usaha'])->get());
        
        $usersToNotify = $usersToNotify->filter()->unique('id');

        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new WorkflowNotification([
                'title' => 'NPI Perjaldin Diajukan',
                'message' => "NPI Perjaldin ({$npi->nomor_npi}) menunggu verifikasi pada dashboard tugas Anda.",
                'url' => '#', 
                'icon' => 'receipt_long',
                'color' => 'primary',
            ]));
        }

        return redirect()->route('npis.perjaldin.detail', $spm->id)->with('success', 'NPI berhasil diajukan untuk verifikasi paralel.');
    }
}
