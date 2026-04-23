<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class SpmKontrakController extends Controller
{
    /**
     * Halaman daftar SPM Kontrak — antrean kerja Operator BLU.
     */
    public function index(Request $request)
    {
        // Query: SPP Kontrak yang sudah disetujui (APPROVED / final) atau sudah punya SPM
        $query = DokumenSpp::whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->where(function ($q) {
                $q->where('status', 'APPROVED')
                  ->orWhereHas('spm');
            })
            ->with([
                'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
                'tagihan.potonganTagihan',
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
                  ->orWhereHas('tagihan', fn ($sq) => $sq->where('nomor_tagihan', 'like', "%{$search}%"))
                  ->orWhereHas('tagihan.detailKontrak.kontrakTermin.kontrak', function ($sq) use ($search) {
                      $sq->where('nomor_spk', 'like', "%{$search}%")
                        ->orWhere('nama_pekerjaan', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tagihan.detailKontrak.kontrakTermin.kontrak.vendor', fn ($sq) => $sq->where('nama_pihak', 'like', "%{$search}%"));
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

        return view('spms.spm_kontrak_index', compact('sppList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman detail/workspace SPM Kontrak.
     */
    public function show($spp_id)
    {
        $sppModel = DokumenSpp::with([
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'tagihan.detailKontrak.kontrakTermin.kontrak.dipa.activeRevision.items.coa',
            'tagihan.detailKontrak.arsipDokumen',
            'tagihan.potonganTagihan.pajak',
            'tagihan.logs.user',
            'dipaRevisionItem.coa',
            'ppkVerifikator',
            'dibuatOleh',
            'arsipDokumen',
            'spm.ppspm',
            'spm.dibuatOleh',
            'spm.dipaRevisionItem.coa',
            'spm.workflowInstances.approvals.assignedUser',
            'spm.workflowInstances.approvals.actedByUser',
            'spm.logs.user',
            'spm.arsipDokumen',
        ])->findOrFail($spp_id);

        $tagihan = $sppModel->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $termin = $detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();
        $dipa = $kontrak?->dipa;
        $activeRevision = $dipa?->activeRevision;
        $selectedBudgetItem = $sppModel->dipaRevisionItem;
        $spmModel = $sppModel->spm;

        $ppspms = User::role('PPSPM')->orderBy('name')->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderBy('name')->first();

        // Potongan
        $potonganTagihans = collect($tagihan->potonganTagihan ?? []);
        $potonganAngsuranUm = $potonganTagihans->firstWhere('jenis_potongan', 'ANGSURAN_UANG_MUKA');
        $potonganPajak = $potonganTagihans->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
        $isPelunasan = ($termin?->jenis_termin ?? null) === 'PELUNASAN';

        // Nominal SPM = nominal SPP (otomatis)
        $nominalSpm = (float) ($sppModel->nominal_spp ?? $tagihan->total_netto ?? 0);

        // Dokumen pendukung
        $documentStatuses = $this->buildDocumentStatuses($detailKontrak, $spmModel, $isPelunasan, $potonganPajak->isNotEmpty());

        // Readiness checklist
        $rekeningReady = filled($rekening?->nama_bank) && filled($rekening?->nomor_rekening) && filled($rekening?->nama_rekening);
        $draftReady = $spmModel
            && filled($spmModel->nomor_spm)
            && filled($spmModel->tanggal_spm)
            && (float) ($spmModel->nominal_spm ?? 0) > 0
            && filled($spmModel->ppspm_id);

        $readinessChecklist = collect([
            [
                'label' => 'SPP sumber tersedia & disetujui',
                'status' => $sppModel->status === 'APPROVED' ? 'ready' : 'missing',
                'hint' => $sppModel->status === 'APPROVED'
                    ? 'SPP sudah disetujui dan siap jadi dasar SPM.'
                    : 'SPP belum dalam status disetujui.',
            ],
            [
                'label' => 'Item DIPA / COA valid',
                'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing',
                'hint' => filled($selectedBudgetItem?->coa)
                    ? 'Item anggaran sudah terpilih.'
                    : 'Item DIPA / COA belum tersedia.',
            ],
            [
                'label' => 'Data vendor & rekening lengkap',
                'status' => $rekeningReady ? 'ready' : 'missing',
                'hint' => $rekeningReady
                    ? 'Rekening vendor siap untuk pembayaran.'
                    : 'Data rekening vendor belum lengkap.',
            ],
            [
                'label' => 'Draft SPM sudah lengkap',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint' => $draftReady
                    ? 'Nomor, tanggal, dan verifikator SPM sudah terisi.'
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

        return view('spms.spm_kontrak_detail', compact(
            'sppModel',
            'tagihan',
            'detailKontrak',
            'termin',
            'kontrak',
            'vendor',
            'rekening',
            'dipa',
            'activeRevision',
            'selectedBudgetItem',
            'spmModel',
            'ppspms',
            'kasubbagUser',
            'potonganAngsuranUm',
            'potonganPajak',
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
            'kasubbagUser',
            'autoNomorSpm'
        ));
    }

    /**
     * Simpan Draft SPM (tanpa workflow).
     */
    public function store(Request $request, $spp_id)
    {
        $spp = DokumenSpp::with(['spm', 'tagihan', 'dipaRevisionItem'])
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
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
                    'nominal_spm' => $nominalSpm,
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
                'catatan' => 'Draft SPM kontrak disimpan. PPSPM: ' . optional(User::find($request->ppspm_id))->name,
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('spms.kontrak.detail', $spp->id)->with('success', 'Draft SPM kontrak berhasil disimpan.');
    }

    /**
     * Ajukan Verifikasi SPM — mulai workflow paralel PPSPM + Kasubbag.
     */
    public function submit($spp_id)
    {
        $spp = DokumenSpp::with(['spm'])
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->findOrFail($spp_id);

        $spm = $spp->spm;

        if (!$spm) {
            return back()->withErrors(['error' => 'Dokumen SPM belum dibuat. Silakan simpan draft terlebih dahulu.']);
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

            // Start workflow paralel PPSPM + Kasubbag
            app(WorkflowService::class)->startWorkflow('SPM_KONTRAK_PPSPM', $spm, $spm->ppspm_id);

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpm::class,
                'dokumen_id' => $spm->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => DokumenSpm::STATUS_MENUNGGU_VERIFIKASI,
                'aksi' => 'SUBMIT_VERIFIKASI',
                'catatan' => 'SPM kontrak diajukan untuk verifikasi paralel PPSPM + Kasubbag.',
                'ip_address' => request()->ip(),
            ]);
        });

        // Notifikasi
        $selectedPpspm = User::find($spm->ppspm_id);
        if ($selectedPpspm) {
            Notification::send($selectedPpspm, new WorkflowNotification([
                'title' => 'SPM Kontrak Diajukan',
                'message' => "SPM Kontrak ({$spm->nomor_spm}) menunggu verifikasi Anda.",
                'url' => route('verifikasi-ppspm.spm.index'),
                'icon' => 'description',
                'color' => 'primary',
            ]));
        }

        $kasubbagUsers = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->get();
        if ($kasubbagUsers->isNotEmpty()) {
            Notification::send($kasubbagUsers, new WorkflowNotification([
                'title' => 'SPM Kontrak Diajukan',
                'message' => "SPM Kontrak ({$spm->nomor_spm}) menunggu verifikasi Anda.",
                'url' => route('verifikasi-kasubag.spm.index'),
                'icon' => 'description',
                'color' => 'primary',
            ]));
        }

        return redirect()->route('spms.kontrak.detail', $spm->spp_id)->with('success', 'SPM kontrak berhasil diajukan untuk verifikasi.');
    }

    /**
     * Build document statuses array.
     */
    private function buildDocumentStatuses($detailKontrak, $spmModel, $isPelunasan, $requiresTaxDocuments)
    {
        $ebillingDocument = $spmModel?->arsipDokumen?->firstWhere('jenis_dokumen', 'E_BILLING');

        return collect([
            ['key' => 'bapp', 'label' => 'BAPP', 'path' => $detailKontrak?->file_bapp, 'required' => true],
            ['key' => 'bast', 'label' => 'BAST', 'path' => $detailKontrak?->file_bast, 'required' => $isPelunasan],
            ['key' => 'bap', 'label' => 'BAP', 'path' => $detailKontrak?->file_bap, 'required' => true],
            ['key' => 'invoice', 'label' => 'Invoice', 'path' => $detailKontrak?->file_invoice, 'required' => true],
            ['key' => 'faktur_pajak', 'label' => 'Faktur Pajak', 'path' => $detailKontrak?->file_faktur_pajak, 'required' => $requiresTaxDocuments],
            ['key' => 'ebilling', 'label' => 'E-Billing', 'path' => $ebillingDocument?->path_file, 'required' => $requiresTaxDocuments],
            ['key' => 'kwitansi', 'label' => 'Kwitansi', 'path' => $detailKontrak?->file_kwitansi ?? null, 'required' => false],
            ['key' => 'spp_pdf', 'label' => 'SPP (PDF)', 'path' => null, 'required' => false],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required'] ? 'not_required' : ($isAvailable ? 'ready' : 'missing');
            return array_merge($item, ['status' => $status, 'is_available' => $isAvailable]);
        })->values();
    }
}
