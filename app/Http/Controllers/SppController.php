<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spp;
use App\Models\Perjaldin;
use App\Models\Transaction;
use App\Models\Contract;
use App\Models\Tagihan;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\MasterTarifPajak;
use App\Models\PotonganTagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\PaymentPdfReference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification;
use App\Services\WorkflowService;

class SppController extends Controller
{
    /**
     * Menampilkan daftar Perjaldin yang sudah siap dibikinkan SPP
     */
    public function perjaldinIndex()
    {
        $perjaldins = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereIn('status', [
                'DISETUJUI_PERJALDIN',
                'PROSES_COA',
                'PROSES_SPP',
                'SEBAGIAN_SPP_TERBIT',
                'SPP_LENGKAP'
            ])
            ->with(['detailPerjaldin', 'komponenPerjaldin.dokumenSpp', 'logs'])
            ->latest()
            ->get();

        // Tagihan Perjaldin yang dikembalikan untuk revisi oleh Operator BLU saat ini,
        // agar operator mengingat dokumen yang sedang menunggu perbaikan.
        $revisis = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->where('status', 'DIKEMBALIKAN')
            ->whereHas('logs', function ($q) {
                $q->where('aksi', 'KEMBALIKAN_REVISI_COA')
                  ->where('user_id', auth()->id());
            })
            ->with(['detailPerjaldin', 'logs'])
            ->latest()
            ->get();

        return view('spps.perjaldin_index', compact('perjaldins', 'revisis'));
    }

    /**
     * Menampilkan halaman detail Multi-SPP Perjaldin per komponen
     */
    public function detailPerjaldin($perjaldin_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with([
                'detailPerjaldin.pegawai',
                'komponenPerjaldin.dipaRevisionItem.coa',
                'komponenPerjaldin.dokumenSpp.logs',
                'komponenPerjaldin.dokumenSpp.workflowInstances.approvals',
                'logs'
            ])
            ->findOrFail($perjaldin_id);
            
        $budgets = \App\Support\DipaBudgetOptionService::groupedOptions();
        $ppkUser = \App\Models\User::find($tagihan->ppk_user_id) ?? \App\Models\User::role('PPK')->orderByDisplayName()->first();
        $kasubbagUser = \App\Models\User::find($tagihan->kasubbag_user_id) ?? \App\Models\User::role('Kepala Subbagian Keuangan dan Tata Usaha')->first();
        $koordinatorUser = \App\Models\User::find($tagihan->koordinator_keuangan_user_id) ?? \App\Models\User::role('Koordinator Keuangan')->first();

        // Hitung counter berikutnya untuk preview nomor SPP
        $tahun = date('Y');
        $nextSppCounter = \App\Services\DocumentNumberingService::getNextSppSequence($tahun);

        return view('spps.detail_perjaldin', compact('tagihan', 'budgets', 'ppkUser', 'kasubbagUser', 'koordinatorUser', 'nextSppCounter'));
    }

    /**
     * Membuat atau Update SPP berdasarkan item biaya / komponen dari dokumen Perjaldin.
     */
    public function storeFromPerjaldinKomponen(Request $request, $komponenId)
    {
        $komponen = \App\Models\TagihanPerjaldinKomponen::with('dokumenSpp')->findOrFail($komponenId);

        $request->validate([
            'tanggal_spp' => 'required|date',
            'tahun_anggaran' => 'required|string',
            'ppk_verifikator_id' => 'required|exists:users,id',
        ]);

        try {
            DB::transaction(function() use ($komponen, $request) {
                $tagihan = Tagihan::findOrFail($komponen->tagihan_id);
                $isUpdate = $komponen->hasDokumenTurunan();
                
                $ppkUser = \App\Models\User::with('profilable')->findOrFail($request->ppk_verifikator_id);
                $uraianText = 'Belanja Barang Perjalanan Dinas Pegawai - ' . str_replace('_', ' ', $komponen->kode_komponen);
                
                // Auto-generate nomor SPP jika baru, atau pertahankan nomor lama jika update
                $existingSpp = $komponen->dokumenSpp;
                if ($isUpdate && $existingSpp) {
                    $nomorSpp = $existingSpp->nomor_spp;
                } else {
                    $nomorSpp = \App\Services\DocumentNumberingService::generateSppNumber(date('Y'));
                }

                $spp = DokumenSpp::updateOrCreate(
                    [
                        'tagihan_perjaldin_komponen_id' => $komponen->id
                    ],
                    [
                        'tagihan_id' => $tagihan->id,
                        'komponen_biaya' => $komponen->kode_komponen,
                        'dipa_revision_item_id' => $komponen->dipa_revision_item_id,
                        'kategori_pembayaran' => optional($tagihan->mekanisme_pembayaran)->sppKategoriPembayaran() ?? 'SP2D BLU - TRF',
                        'jenis_tagihan' => $request->jenis_tagihan ?? 'NON REMUNERASI',
                        'nominal_spp' => $komponen->total_nominal,
                        'nomor_spp' => $nomorSpp,
                        'tanggal_spp' => $request->tanggal_spp,
                        'tahun_anggaran' => $request->tahun_anggaran,
                        'penandatangan_nama' => $ppkUser->name,
                        'penandatangan_nip' => $ppkUser->pegawai?->nip ?? '-',
                        'ppk_verifikator_id' => $request->ppk_verifikator_id,
                        'uraian' => $uraianText,
                        'status' => $isUpdate && in_array($komponen->dokumenSpp->status, ['Revisi', 'REVISI_PPK', 'REVISI_KASUBBAG'], true)
                            ? $komponen->dokumenSpp->status
                            : 'DRAFT',
                        'dibuat_oleh_id' => $request->user()->id,
                    ]
                );

                // Rekalkulasi status tagihan
                if (!in_array($tagihan->status, ['PROSES_SPP', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP'])) {
                    $tagihan->update(['status' => 'PROSES_SPP']);
                }

                \App\Models\LogStatusDokumen::create([
                    'dokumen_type' => DokumenSpp::class,
                    'dokumen_id' => $spp->id,
                    'user_id' => $request->user()->id,
                    'role_saat_itu' => $request->user()->getRoleNames()->first() ?? 'Operator BLU',
                    'status_sebelumnya' => $isUpdate ? $komponen->dokumenSpp->status : null,
                    'status_baru' => $spp->status,
                    'aksi' => $isUpdate ? 'UPDATE_DRAFT_SPP' : 'CREATE_DRAFT_SPP',
                    'catatan' => ($isUpdate ? 'Draft dokumen SPP diperbarui' : 'Draft dokumen SPP dibuat') . ' oleh Operator BLU.',
                    'ip_address' => request()->ip(),
                ]);

                // Update status komponen
                $komponen->syncStatusFromDocuments();
            });

            return redirect()->back()->with('success', 'Draft SPP untuk komponen ini berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * @deprecated Gunakan SppController::storeFromPerjaldinKomponen() untuk flow multi-komponen.
     */
    public function storePerjaldin(Request $request, $perjaldin_id)
    {
        $request->validate([
            'kategori_biaya' => 'required|string',
            'jumlah_uang' => 'required|numeric',
            'nomor_spp' => 'required|string',
            'tanggal_spp' => 'required|date',
            'tahun_anggaran' => 'required|string',
            'nomor_dipa' => 'required|string',
            'tanggal_dipa' => 'required|date',
            'akun_mak' => 'required|string',
            'penandatangan_nama' => 'required|string',
            'penandatangan_nip' => 'required|string',
        ]);

        $perjaldin = Perjaldin::findOrFail($perjaldin_id);

        // Standard Uraian template based on PDF
        $uraianText = 'Belanja Barang Perjalanan Dinas Pegawai - ' . $request->kategori_biaya;

        DB::transaction(function () use ($request, $perjaldin, $uraianText) {
            // Polymorphic Create Spp untuk Kategori Tertentu
            $perjaldin->spps()->updateOrCreate(
                [
                    'kategori_biaya' => $request->kategori_biaya,
                ],
                [
                    'jumlah_uang' => $request->jumlah_uang,
                    'uraian' => $uraianText,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'tahun_anggaran' => $request->tahun_anggaran,
                    'nomor_dipa' => $request->nomor_dipa,
                    'tanggal_dipa' => $request->tanggal_dipa,
                    'akun_mak' => $request->akun_mak,
                    'penandatangan_nama' => $request->penandatangan_nama,
                    'penandatangan_nip' => $request->penandatangan_nip,
                    // Default values matching template
                    'jenis_tagihan' => 'NON REMUNERASI',
                    'jatuh_tempo' => 'Segera',
                    'cara_bayar' => 'SP2D BLU - TRF',
                    'status_spp' => 'Menunggu Verifikasi',
                    'catatan_revisi' => null,
                ]
            );

            // Update status perjaldin
            $perjaldin->update(['status' => 'Proses SPP']);
        });

        // Beritahu PPK ada SPP baru/diubah
        $ppks = \App\Models\User::role('PPK')->get();
        \Illuminate\Support\Facades\Notification::send($ppks, new \App\Notifications\WorkflowNotification([
            'title' => 'Pengajuan SPP',
            'message' => "Surat Permintaan Pembayaran ({$request->kategori_biaya}) menunggu verifikasi Anda.",
            'url' => route('verifikasi-spp.perjaldin.index'),
            'icon' => 'receipt_long',
            'color' => 'primary'
        ]));

        return redirect()->route('spps.perjaldin.detail', $perjaldin_id)->with('success', 'SPP '.$request->kategori_biaya.' berhasil diterbitkan. Silakan cetak PDF.');
    }

    /**
     * Mengembalikan tagihan Perjaldin ke pembuat untuk direvisi jika pagu COA tidak valid.
     */
    public function returnRevisionPerjaldin(Request $request, $perjaldin_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:500'
        ]);

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($perjaldin_id);

        if (!in_array($tagihan->status, ['DISETUJUI_PERJALDIN', 'PROSES_SPP'])) {
            return back()->withErrors(['error' => 'Dokumen ini tidak dalam status yang dapat dikembalikan untuk revisi.']);
        }

        try {
            DB::transaction(function() use ($request, $tagihan) {
                $oldStatus = $tagihan->status;
                $tagihan->update(['status' => 'DIKEMBALIKAN']);

                LogStatusDokumen::create([
                    'dokumen_type' => Tagihan::class,
                    'dokumen_id' => $tagihan->id,
                    'user_id' => auth()->id(),
                    'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                    'status_sebelumnya' => $oldStatus,
                    'status_baru' => 'DIKEMBALIKAN',
                    'aksi' => 'KEMBALIKAN_REVISI_COA',
                    'catatan' => 'Dikembalikan oleh Operator BLU. Alasan: ' . $request->catatan_revisi,
                    'ip_address' => request()->ip(),
                ]);
            });

            // Kirim Notifikasi ke Pembuat dan PPK
            $usersToNotify = collect([User::find($tagihan->created_by), User::find($tagihan->ppk_user_id)])->filter();
            if ($usersToNotify->isNotEmpty()) {
                Notification::send($usersToNotify, new WorkflowNotification([
                    'title' => 'Perjaldin Dikembalikan (Revisi)',
                    'message' => "Tagihan Perjaldin {$tagihan->nomor_tagihan} dikembalikan oleh Operator BLU karena kendala pagu/revisi.",
                    'url' => route('perjaldins.show', $tagihan->id),
                    'icon' => 'assignment_return',
                    'color' => 'danger'
                ]));
            }

            return redirect()->route('spps.perjaldin.index')->with('success', 'Dokumen Perjaldin berhasil dikembalikan untuk direvisi.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengembalikan dokumen: ' . $e->getMessage()]);
        }
    }

    // ===================================================================
    // SPP HONOR
    // ===================================================================

    public function honorIndex()
    {
        $honorariums = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->whereIn('status', ['DISETUJUI', 'PROSES_SPP', 'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP'])
            ->with(['detailHonorarium', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.honor_index', compact('honorariums'));
    }

    public function detailHonor($honorarium_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with([
                'detailHonorarium',
                'arsipDokumen',
                'potonganTagihan',
                'logs.user',
                'dipa',
                'spps.dipaRevisionItem.coa',
                'spps.ppkVerifikator',
                'spps.dibuatOleh',
                'spps.arsipDokumen',
                'spps.logs.user',
                'spps.workflowInstances.approvals.assignedUser',
                'spps.workflowInstances.approvals.actedByUser',
            ])
            ->findOrFail($honorarium_id);

        $sppModel = $tagihan->spps->sortByDesc('created_at')->first();
        $itemDipaId = $sppModel?->dipa_revision_item_id ?? $tagihan->dipa_revision_item_id;
        $selectedBudgetItem = \App\Models\DetailDipa::with(['coa', 'dipaRevision.masterDipa'])->find($itemDipaId);
        
        $masterDipa = $selectedBudgetItem?->dipaRevision?->masterDipa ?? $tagihan->dipa;
        $riwayatRevisiDipa = $selectedBudgetItem?->dipaRevision;
        
        
        $ppkUser = \App\Models\User::find($tagihan->ppk_user_id) ?? \App\Models\User::role('PPK')->orderByDisplayName()->first();
        $kasubbagUser = \App\Models\User::find($tagihan->kasubbag_user_id) ?? \App\Models\User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderByDisplayName()->first();
        $koordinatorUser = \App\Models\User::find($tagihan->koordinator_keuangan_user_id) ?? \App\Models\User::role('Koordinator Keuangan')->orderByDisplayName()->first();
        
        $skHonorarium = $tagihan->arsipDokumen->firstWhere('jenis_dokumen', 'SK Honorarium');

        // Nominatif & Rekap Honorarium diterbitkan sistem ber-TTE QR otomatis
        // setelah workflow honorarium APPROVED (App\Support\TagihanDocumentTte).
        $isHonorariumTteApproved = \App\Support\TagihanDocumentTte::isApproved($tagihan);

        $documentStatuses = collect([
            [
                'key' => 'nominatif_honorarium_tte',
                'label' => 'Nominatif Honorarium (TTE)',
                'url' => $isHonorariumTteApproved ? route('honorarium.pdf-nominatif', $tagihan->id) : null,
                'path' => null,
                'is_tte' => true,
                'required' => true,
            ],
            [
                'key' => 'rekap_honorarium_tte',
                'label' => 'Rekap Honorarium (TTE)',
                'url' => $isHonorariumTteApproved ? route('honorarium.pdf', $tagihan->id) : null,
                'path' => null,
                'is_tte' => true,
                'required' => true,
            ],
            [
                'key' => 'sk_honorarium',
                'label' => 'SK Honorarium',
                'url' => $skHonorarium ? \Illuminate\Support\Facades\Storage::url($skHonorarium->path_file) : null,
                'path' => $skHonorarium?->path_file,
                'is_tte' => false,
                'required' => false,
            ],
        ])->map(function ($item) {
            $isAvailable = !empty($item['url']);
            if (! $item['required']) {
                $status = $isAvailable ? 'ready' : 'not_required';
            } elseif ($isAvailable) {
                $status = !empty($item['is_tte']) ? 'tte' : 'ready';
            } else {
                $status = 'missing';
            }

            return array_merge($item, [
                'status' => $status,
                'is_available' => $isAvailable,
            ]);
        })->values();

        $semuaPunyaRekening = $tagihan->detailHonorarium->every(fn($item) => filled($item->rekening) && filled($item->nama_rekening));
        $mainDocumentsReady = $documentStatuses
            ->whereIn('key', ['nominatif_honorarium_tte', 'rekap_honorarium_tte'])
            ->every(fn ($item) => in_array($item['status'], ['tte', 'ready']));
            
        $draftReady = $sppModel
            && filled($sppModel->nomor_spp)
            && filled($sppModel->tanggal_spp)
            && (float) $sppModel->nominal_spp > 0
            && filled($selectedBudgetItem?->coa)
            && filled($sppModel->ppk_verifikator_id);

        $readinessChecklist = collect([
            [
                'label' => 'Item DIPA / COA tersedia',
                'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing',
                'hint' => filled($selectedBudgetItem?->coa)
                    ? 'Item anggaran aktif sudah terpilih untuk SPP.'
                    : 'Honorarium belum memiliki item DIPA / COA aktif.',
            ],
            [
                'label' => 'Validasi rekening semua penerima',
                'status' => $semuaPunyaRekening ? 'ready' : 'missing',
                'hint' => $semuaPunyaRekening
                    ? 'Rekening bank untuk semua orang sudah tersedia.'
                    : 'Masih ada penerima honorarium yang datanya kurang / invalid.',
            ],
            [
                'label' => 'Dokumen pendukung utama tersedia',
                'status' => $mainDocumentsReady ? 'ready' : 'missing',
                'hint' => $mainDocumentsReady
                    ? 'Daftar Nominatif dan BAST sudah tersedia.'
                    : 'Masih ada dokumen utama yang belum terlampir.',
            ],
            [
                'label' => 'Verifikator PPK dipilih',
                'status' => filled($sppModel?->ppk_verifikator_id) ? 'ready' : 'missing',
                'hint' => filled($sppModel?->ppk_verifikator_id)
                    ? 'Verifikator PPK sudah ditentukan.'
                    : 'Pilih verifikator PPK pada draft SPP.',
            ],
            [
                'label' => 'Draft SPP sudah lengkap',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint' => $draftReady
                    ? 'Nomor, tanggal, nilai, dan data draft SPP sudah lengkap.'
                    : 'Draft SPP belum lengkap atau belum disimpan.',
            ],
        ])->values();

        $sppStatus = $sppModel?->status ?? 'Belum Dibuat';
        $workflowSummary = match ($sppStatus) {
            'DRAFT' => [
                'label' => 'Draft tersimpan',
                'tone' => 'warning',
                'description' => 'Draft masih bisa diubah oleh Operator BLU dan belum diajukan.',
                'edit_state' => 'editable',
            ],
            'Revisi' => [
                'label' => 'Perlu revisi',
                'tone' => 'danger',
                'description' => 'Dokumen dikembalikan revisi. Perbaiki draft lalu ajukan ulang.',
                'edit_state' => 'editable',
            ],
            'Menunggu Verifikasi' => [
                'label' => 'Menunggu verifikasi PPK',
                'tone' => 'info',
                'description' => 'Dokumen sudah diajukan dan sedang diverifikasi PPK.',
                'edit_state' => 'locked',
            ],
            'Disetujui PPK' => [
                'label' => 'Disetujui PPK',
                'tone' => 'success',
                'description' => 'SPP telah disetujui PPK dan siap lanjut ke SPM.',
                'edit_state' => 'locked',
            ],
            'DISETUJUI_SPP' => [
                'label' => 'Selesai Diverifikasi',
                'tone' => 'success',
                'description' => 'Disetujui seluruh verifikator dan selesai.',
                'edit_state' => 'locked',
            ],
            default => [
                'label' => 'Belum dibuat',
                'tone' => 'secondary',
                'description' => 'Kumpulkan form draft SPP untuk mematangkan SPP.',
                'edit_state' => 'editable',
            ],
        };

        $readinessIssues = $readinessChecklist
            ->where('status', 'missing')
            ->pluck('hint')
            ->filter()
            ->values();

        $canSubmitToPpk = $sppModel && in_array($sppModel->status, ['DRAFT', 'Revisi']);
        $isReadyToSubmit = $canSubmitToPpk && $readinessIssues->isEmpty();
        $readinessStatus = match ($sppStatus) {
            'Menunggu Verifikasi' => ['label' => 'Dalam Verifikasi', 'class' => 'bg-info', 'message' => 'Dalam antrean verifikasi PPK.'],
            'Disetujui PPK', 'DISETUJUI_SPP', 'APPROVED' => ['label' => 'Terverifikasi', 'class' => 'bg-success', 'message' => 'Telah terverifikasi sepenuhnya.'],
            'Revisi' => ['label' => 'Perlu Revisi', 'class' => 'bg-danger', 'message' => 'Kembali revisi. Lengkapi kekurangan draft.'],
            default => ['label' => $isReadyToSubmit ? 'Siap Diajukan' : 'Belum Lengkap', 'class' => $isReadyToSubmit ? 'bg-success' : 'bg-warning text-dark', 'message' => $isReadyToSubmit ? 'Checklist valid. Klik Ajukan SPP ke PPK.' : 'Ada item checklist mandatory yang kurang.']
        };

        $latestWorkflowInstance = collect($sppModel?->workflowInstances ?? [])->sortByDesc('created_at')->first();
        $ppkApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'PPK');
        $koordinatorApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Koordinator Keuangan');
        $kasubbagApproval = collect($latestWorkflowInstance?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $submittedLog = collect($sppModel?->logs ?? [])->sortByDesc('created_at')->firstWhere('aksi', 'SUBMIT_PPK');

        $activitySummary = [
            ['label' => 'Dibuat oleh', 'value' => $sppModel?->dibuatOleh?->name ?? '-', 'meta' => optional($sppModel?->created_at)->format('d M Y H:i')],
            ['label' => 'Terakhir diperbarui', 'value' => optional($sppModel?->updated_at)->format('d M Y H:i') ?? '-', 'meta' => 'Pembaharuan log SPP Honorarium'],
            ['label' => 'Diajukan ke PPK', 'value' => optional($submittedLog?->created_at)->format('d M Y H:i') ?? 'Belum diajukan', 'meta' => null],
            ['label' => 'Status SPP', 'value' => $workflowSummary['label'], 'meta' => null],
        ];

        $recentActivities = collect($sppModel?->logs ?? [])
            ->sortByDesc('created_at')->take(4)
            ->map(function ($log) {
                return ['title' => str_replace('_', ' ', $log->aksi), 'time' => optional($log->created_at)->format('d M Y H:i'), 'actor' => $log->user?->name, 'note' => $log->catatan];
            })->values();

        $autoNomorSpp = \App\Services\DocumentNumberingService::generateSppNumber(date('Y'));

        return view('spps.detail_honor', compact(
            'tagihan', 'sppModel', 'selectedBudgetItem', 'masterDipa', 'riwayatRevisiDipa', 'ppkUser', 'kasubbagUser', 'koordinatorUser', 'documentStatuses', 'readinessChecklist',
            'readinessIssues', 'workflowSummary', 'activitySummary', 'recentActivities', 'isReadyToSubmit', 'readinessStatus',
            'ppkApproval', 'koordinatorApproval', 'kasubbagApproval', 'autoNomorSpp'
        ));
    }

    public function storeHonor(Request $request, $honorarium_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($honorarium_id);
        $existingSpp = $tagihan->spps()->latest()->first();

        $request->validate([
            'nomor_spp' => ['required', 'string', Rule::unique('dokumen_spp', 'nomor_spp')->ignore($existingSpp?->id)],
            'tanggal_spp' => 'required|date',
            'ppk_verifikator_id' => 'required|exists:users,id',
            'uraian' => 'nullable|string'
        ]);

        $defaultBudgetItemId = $existingSpp?->dipa_revision_item_id ?? $tagihan->dipa_revision_item_id;

        if (!$defaultBudgetItemId) {
            return back()->withInput()->withErrors(['error' => 'Honorarium belum memiliki item anggaran/DIPA.']);
        }

        DB::transaction(function () use ($request, $tagihan, $existingSpp, $defaultBudgetItemId) {
            // Bentuk/perbaharui baris potongan_tagihan PPh 21 honorarium dari
            // agregasi detail_honorarium.pph sebelum dokumen SPP disimpan.
            $this->syncPotonganPajakHonor($tagihan);

            $tagihan->refresh();

            $spp = DokumenSpp::updateOrCreate(
                ['id' => $existingSpp?->id],
                [
                    'tagihan_id' => $tagihan->id,
                    'dipa_revision_item_id' => $defaultBudgetItemId,
                    'kategori_pembayaran' => optional($tagihan->mekanisme_pembayaran)->sppKategoriPembayaran() ?? 'SP2D BLU - TRF',
                    'jenis_tagihan' => $request->jenis_tagihan ?? 'NON REMUNERASI',
                    'nominal_spp' => $tagihan->total_netto,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'status' => $existingSpp && $existingSpp->status === 'Revisi' ? 'Revisi' : 'DRAFT',
                    'dibuat_oleh_id' => auth()->id(),
                    'ppk_verifikator_id' => $request->ppk_verifikator_id,
                    'uraian' => $request->uraian ?? $tagihan->deskripsi,
                ]
            );

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => $tagihan->status,
                'aksi' => $existingSpp ? 'UPDATE_DRAFT_SPP' : 'CREATE_DRAFT_SPP',
                'catatan' => 'Draft dokumen SPP Honorarium disimpan oleh Operator BLU.',
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('spps.honor.detail', $tagihan->id)->with('success', 'Draft SPP Honorarium berhasil disimpan.');
    }

    /**
     * Bentuk/perbaharui baris potongan_tagihan PPh 21 honorarium dari agregasi
     * detail_honorarium.pph. Dipanggil di dalam transaksi storeHonor.
     *
     * - Satu baris potongan_tagihan (jenis_potongan = PAJAK) per jenis pajak.
     * - Menolak revisi jika baris pajak sudah memiliki kode_billing atau ntpn.
     * - Menyinkronkan tagihan.total_potongan & tagihan.total_netto.
     */
    private function syncPotonganPajakHonor(Tagihan $tagihan): void
    {
        $details = $tagihan->detailHonorarium()->get();
        $totalPphFromDetail = (float) $details->sum('pph');
        $totalDpp = (float) $details->sum('nilai_honor');

        // Guard revisi: jangan ubah baris pajak yang sudah masuk tahap billing/setor.
        $lockedExists = $tagihan->potonganTagihan()
            ->where('jenis_potongan', 'PAJAK')
            ->where(function ($q) {
                $q->whereNotNull('kode_billing')->orWhereNotNull('ntpn');
            })
            ->exists();

        if ($lockedExists) {
            throw ValidationException::withMessages([
                'error' => 'Revisi pajak honorarium tidak diperbolehkan setelah Kode Billing/NTPN terbentuk.',
            ]);
        }

        // Hapus baris pajak honor lama (beserta arsipnya) — aman karena belum di-billing.
        $oldPotongan = $tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->get();
        foreach ($oldPotongan as $old) {
            $old->arsipDokumen()->delete();
            $old->delete();
        }

        // Hitung ulang total bruto dari detail honor agar konsisten.
        $totalBruto = (float) ($tagihan->total_bruto ?: $details->sum('nilai_honor'));
        $totalPotonganPajak = 0.0;

        if ($totalPphFromDetail > 0) {
            $pajak = MasterTarifPajak::where('status_aktif', true)
                ->where('kode_pajak', 'PPH21-TER')
                ->first()
                ?? MasterTarifPajak::where('status_aktif', true)
                    ->where('jenis_pajak', 'like', '%21%')
                    ->orderByDesc('berlaku_mulai')
                    ->first();

            if (! $pajak) {
                throw ValidationException::withMessages([
                    'error' => 'Tarif pajak PPh 21 aktif belum tersedia di Master Tarif Pajak. Hubungi admin untuk menambahkannya.',
                ]);
            }

            PotonganTagihan::create([
                'tagihan_id' => $tagihan->id,
                'pajak_id' => $pajak->id,
                'jenis_potongan' => 'PAJAK',
                'deskripsi' => 'PPh 21 honorarium',
                'dpp' => max(0, $totalDpp),
                'persentase_tarif_snapshot' => $pajak->persentase,
                'nama_pajak_snapshot' => $pajak->jenis_pajak,
                'nominal_potongan' => $totalPphFromDetail,
            ]);

            $totalPotonganPajak = $totalPphFromDetail;

            // Konsistensi: jumlah baris pajak harus sama dengan total pph detail.
            if (round($totalPotonganPajak, 2) !== round($totalPphFromDetail, 2)) {
                throw ValidationException::withMessages([
                    'error' => 'Selisih PPh 21 honorarium tidak konsisten.',
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => PotonganTagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_baru' => 'CREATED',
                'aksi' => 'CREATE_PAJAK_HONOR',
                'catatan' => 'Pembentukan potongan PPh 21 honorarium: Rp ' . number_format($totalPphFromDetail, 0, ',', '.'),
                'ip_address' => request()->ip(),
            ]);
        }

        // Sinkronisasi total tagihan.
        $tagihan->update([
            'total_potongan' => $totalPotonganPajak,
            'total_netto' => max(0, $totalBruto - $totalPotonganPajak),
        ]);
    }

    public function submitHonorToPpk($honorarium_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->with('spps')->findOrFail($honorarium_id);
        $spp = $tagihan->spps()->latest()->first();

        if (!$spp) return back()->withErrors(['error' => 'Draft SPP belum dibuat.']);
        if (!in_array($spp->status, ['DRAFT', 'Revisi'])) return back()->withErrors(['error' => 'SPP tidak siap diajukan (Bukan mode DRAFT).']);
        if (!$spp->ppk_verifikator_id) return back()->withErrors(['error' => 'Verifikator PPK belum dipilih.']);

        DB::transaction(function () use ($tagihan, $spp) {
            $statusSebelumnya = $spp->status;
            $spp->update(['status' => 'Menunggu Verifikasi']);

            app(WorkflowService::class)->startWorkflow('SPP_HONORARIUM_PPK', $spp, $spp->ppk_verifikator_id);
            
            if (!in_array($tagihan->status, ['PROSES_SPP', 'SPP_TERBIT'])) {
                $tagihan->update(['status' => 'PROSES_SPP']);
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'Menunggu Verifikasi',
                'aksi' => 'SUBMIT_PPK',
                'catatan' => 'Dokumen SPP Honorarium diajukan ke PPK untuk verifikasi.',
                'ip_address' => request()->ip(),
            ]);
        });

        // Notifikasi ke verifikator PPK
        $selectedPpk = User::find($spp->ppk_verifikator_id);
        if ($selectedPpk) {
            Notification::send($selectedPpk, new WorkflowNotification([
                'title' => 'SPP Honorarium Diajukan',
                'message' => "SPP Honorarium ({$spp->nomor_spp}) menunggu verifikasi Anda.",
                'url' => route('verifikasi-spp.honor.index'),
                'icon' => 'receipt_long',
                'color' => 'primary',
            ]));
        }

        return redirect()->route('spps.honor.detail', $tagihan->id)->with('success', 'SPP honorarium berhasil diajukan ke PPK.');
    }

    // ===================================================================
    // SPP KONTRAK
    // ===================================================================

    public function kontrakIndex()
    {
        $contracts = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->whereIn('status', ['READY_FOR_SPP', 'PROSES_SPP', 'SPP_TERBIT'])
            ->with(['detailKontrak.kontrakTermin.kontrak.vendor', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.kontrak_index', compact('contracts'));
    }

    public function detailKontrak($contract_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->with([
                'detailKontrak.arsipDokumen',
                'detailKontrak.kontrakTermin.kontrak.vendor.rekening',
                'detailKontrak.kontrakTermin.kontrak.dipa.activeRevision.items.coa',
                'potonganTagihan',
                'logs.user',
                'spps.dipaRevisionItem.coa',
                'spps.ppkVerifikator',
                'spps.dibuatOleh',
                'spps.arsipDokumen',
                'spps.logs.user',
                'spps.workflowInstances.approvals.assignedUser',
                'spps.workflowInstances.approvals.actedByUser',
            ])
            ->findOrFail($contract_id);

        $detailKontrak = $tagihan->detailKontrak;
        $termin = $detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();
        $budgetItems = collect(optional(optional($kontrak?->dipa)->activeRevision)->items)
            ->filter(fn ($item) => $item->status_aktif)
            ->values();
        $sppModel = $tagihan->spps->sortByDesc('created_at')->first();
        $selectedBudgetItem = $sppModel?->dipaRevisionItem ?? $budgetItems->first();
        $ppkUser = \App\Models\User::find($tagihan->ppk_user_id) ?? \App\Models\User::role('PPK')->orderByDisplayName()->first();
        $kasubbagUser = \App\Models\User::find($tagihan->kasubbag_user_id) ?? \App\Models\User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderByDisplayName()->first();
        $koordinatorUser = \App\Models\User::find($tagihan->koordinator_keuangan_user_id) ?? \App\Models\User::role('Koordinator Keuangan')->orderByDisplayName()->first();
        $pajaks = MasterTarifPajak::orderBy('jenis_pajak')->get();
        $potonganTagihans = collect($tagihan->potonganTagihan);
        $potonganPajak = $potonganTagihans->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
        $isPelunasan = ($termin?->jenis_termin ?? null) === 'PELUNASAN';
        $requiresTaxDocuments = $potonganPajak->isNotEmpty();

        $documentStatuses = collect([
            [
                'key' => 'bapp',
                'label' => 'BAPP',
                'path' => $detailKontrak?->file_bapp,
                'required' => true,
            ],
            [
                'key' => 'bast',
                'label' => 'BAST',
                'path' => $detailKontrak?->file_bast,
                'required' => $isPelunasan,
            ],
            [
                'key' => 'bap',
                'label' => 'BAP',
                'path' => $detailKontrak?->file_bap,
                'required' => true,
            ],
            [
                'key' => 'invoice',
                'label' => 'Invoice',
                'path' => $detailKontrak?->file_invoice,
                'required' => true,
            ],
            [
                'key' => 'faktur_pajak',
                'label' => 'Faktur Pajak',
                'path' => $detailKontrak?->file_faktur_pajak,
                'required' => $requiresTaxDocuments,
            ],
            [
                'key' => 'lampiran_lainnya',
                'label' => 'Lampiran Lainnya',
                'path' => $detailKontrak?->file_lampiran_lainnya,
                'required' => false,
            ],
        ])->map(function ($item) {
            $isAvailable = !empty($item['path']);
            $status = !$item['required']
                ? 'not_required'
                : ($isAvailable ? 'ready' : 'missing');

            return array_merge($item, [
                'status' => $status,
                'is_available' => $isAvailable,
            ]);
        })->values();

        $rekeningReady = filled($rekening?->nama_bank)
            && filled($rekening?->nomor_rekening)
            && filled($rekening?->nama_rekening);
        $mainDocumentsReady = $documentStatuses
            ->whereIn('key', ['bapp', 'bast', 'bap', 'invoice'])
            ->every(fn ($item) => in_array($item['status'], ['ready', 'not_required']));
        $taxDocumentsReady = !$requiresTaxDocuments || $documentStatuses
            ->whereIn('key', ['faktur_pajak'])
            ->every(fn ($item) => in_array($item['status'], ['ready', 'not_required']));
        // Catatan: E-Billing dipindahkan ke tahap setelah SP2D disetujui (diunggah oleh Operator BLU),
        // sehingga tidak lagi menjadi syarat di tahap SPP.
        $draftReady = $sppModel
            && filled($sppModel->nomor_spp)
            && filled($sppModel->tanggal_spp)
            && (float) $sppModel->nominal_spp > 0
            && filled($selectedBudgetItem?->coa)
            && filled($sppModel->ppk_verifikator_id);

        $readinessChecklist = collect([
            [
                'label' => 'Item DIPA / COA tersedia',
                'status' => filled($selectedBudgetItem?->coa) ? 'ready' : 'missing',
                'hint' => filled($selectedBudgetItem?->coa)
                    ? 'Item anggaran aktif sudah terpilih untuk SPP.'
                    : 'Kontrak belum memiliki item DIPA / COA aktif.',
            ],
            [
                'label' => 'Rekening vendor tersedia',
                'status' => $rekeningReady ? 'ready' : 'missing',
                'hint' => $rekeningReady
                    ? 'Rekening vendor siap dipakai untuk pembayaran.'
                    : 'Data rekening vendor belum lengkap.',
            ],
            [
                'label' => 'Dokumen pendukung utama tersedia',
                'status' => $mainDocumentsReady ? 'ready' : 'missing',
                'hint' => $mainDocumentsReady
                    ? 'BAPP, BAP, BAST/Invoice utama sudah tersedia.'
                    : 'Masih ada dokumen utama tagihan yang belum terlampir.',
            ],
            [
                'label' => 'Verifikator PPK dipilih',
                'status' => filled($sppModel?->ppk_verifikator_id) ? 'ready' : 'missing',
                'hint' => filled($sppModel?->ppk_verifikator_id)
                    ? 'Verifikator PPK sudah ditentukan.'
                    : 'Pilih verifikator PPK pada draft SPP.',
            ],
            [
                'label' => 'Draft SPP sudah lengkap',
                'status' => $draftReady ? 'ready' : 'missing',
                'hint' => $draftReady
                    ? 'Nomor, tanggal, nilai, dan data draft SPP sudah lengkap.'
                    : 'Draft SPP belum lengkap atau belum disimpan.',
            ],
            [
                'label' => 'Faktur pajak tersedia jika relevan',
                'status' => $taxDocumentsReady ? 'ready' : 'missing',
                'hint' => $taxDocumentsReady
                    ? ($requiresTaxDocuments ? 'Dokumen pajak pendukung sudah tersedia.' : 'Dokumen pajak tidak wajib untuk kasus ini.')
                    : 'Potongan pajak ada, tetapi faktur pajak belum lengkap.',
            ],
        ])->values();

        $sppStatus = $sppModel?->status ?? 'Belum Dibuat';
        $workflowSummary = match ($sppStatus) {
            'DRAFT' => [
                'label' => 'Draft tersimpan',
                'tone' => 'warning',
                'description' => 'Draft masih bisa diubah oleh Operator BLU dan belum dikirim ke PPK.',
                'edit_state' => 'editable',
            ],
            'Revisi' => [
                'label' => 'Perlu revisi',
                'tone' => 'danger',
                'description' => 'PPK mengembalikan dokumen. Draft bisa disunting lalu diajukan ulang.',
                'edit_state' => 'editable',
            ],
            'Menunggu Verifikasi' => [
                'label' => 'Menunggu verifikasi PPK',
                'tone' => 'info',
                'description' => 'Dokumen sudah diajukan dan sementara terkunci sampai PPK memberi keputusan.',
                'edit_state' => 'locked',
            ],
            'Disetujui PPK' => [
                'label' => 'Disetujui PPK',
                'tone' => 'success',
                'description' => 'SPP telah lolos verifikasi PPK dan siap diproses ke tahap berikutnya.',
                'edit_state' => 'locked',
            ],
            default => [
                'label' => 'Belum dibuat',
                'tone' => 'secondary',
                'description' => 'Draft SPP belum disimpan. Operator BLU masih perlu menyiapkan dokumen.',
                'edit_state' => 'editable',
            ],
        };

        $readinessIssues = $readinessChecklist
            ->where('status', 'missing')
            ->pluck('hint')
            ->filter()
            ->values();

        $canSubmitToPpk = $sppModel && in_array($sppModel->status, ['DRAFT', 'Revisi']);
        $isReadyToSubmit = $canSubmitToPpk && $readinessIssues->isEmpty();
        $readinessStatus = match ($sppStatus) {
            'Menunggu Verifikasi' => [
                'label' => 'Dalam Verifikasi',
                'class' => 'bg-info',
                'message' => 'SPP sudah diajukan dan sedang menunggu proses verifikasi.',
            ],
            'Disetujui PPK', 'APPROVED' => [
                'label' => 'Terverifikasi',
                'class' => 'bg-success',
                'message' => 'SPP sudah lolos verifikasi dan tidak lagi berada pada tahap pengecekan draft.',
            ],
            'Revisi' => [
                'label' => 'Perlu Revisi',
                'class' => 'bg-danger',
                'message' => 'SPP dikembalikan untuk diperbaiki. Lengkapi catatan revisi sebelum diajukan ulang.',
            ],
            default => [
                'label' => $isReadyToSubmit ? 'Siap Diajukan' : 'Belum Lengkap',
                'class' => $isReadyToSubmit ? 'bg-success' : 'bg-warning text-dark',
                'message' => $isReadyToSubmit
                    ? 'Checklist draft sudah terpenuhi dan SPP siap diajukan ke PPK.'
                    : 'Masih ada item draft yang perlu dilengkapi sebelum diajukan ke PPK.',
            ],
        };

        $latestWorkflowInstance = collect($sppModel?->workflowInstances ?? [])
            ->sortByDesc('created_at')
            ->first();
        
        $ppkApproval = collect($latestWorkflowInstance?->approvals ?? [])
            ->firstWhere('role_code', 'PPK');
        $koordinatorApproval = collect($latestWorkflowInstance?->approvals ?? [])
            ->firstWhere('role_code', 'Koordinator Keuangan');
        $kasubbagApproval = collect($latestWorkflowInstance?->approvals ?? [])
            ->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');


        $submittedLog = collect($sppModel?->logs ?? [])
            ->sortByDesc('created_at')
            ->firstWhere('aksi', 'SUBMIT_PPK');

        $activitySummary = [
            [
                'label' => 'Dibuat oleh',
                'value' => $sppModel?->dibuatOleh?->name ?? '-',
                'meta' => optional($sppModel?->created_at)->format('d M Y H:i') ?? '-',
            ],
            [
                'label' => 'Terakhir diperbarui',
                'value' => optional($sppModel?->updated_at)->format('d M Y H:i') ?? '-',
                'meta' => $sppModel ? 'Draft terakhir disimpan / diperbarui.' : 'Belum ada draft SPP.',
            ],
            [
                'label' => 'Diajukan ke PPK',
                'value' => optional($submittedLog?->created_at)->format('d M Y H:i') ?? 'Belum diajukan',
                'meta' => $submittedLog?->user?->name ? 'Oleh ' . $submittedLog->user->name : null,
            ],
            [
                'label' => 'Verifikator PPK',
                'value' => $sppModel?->ppkVerifikator?->name ?? '-',
                'meta' => $ppkApproval?->status ? 'Status: ' . $ppkApproval->status : null,
            ],
            [
                'label' => 'Verifikator Koordinator Keuangan',
                'value' => $koordinatorUser?->name ?? '-',
                'meta' => $koordinatorApproval?->status ? 'Status: ' . $koordinatorApproval->status : null,
            ],
            [
                'label' => 'Verifikator Kasubbag',
                'value' => $kasubbagUser?->name ?? '-',
                'meta' => $kasubbagApproval?->status ? 'Status: ' . $kasubbagApproval->status : null,
            ],
            [
                'label' => 'Status workflow',
                'value' => $workflowSummary['label'],
                'meta' => $workflowSummary['description'],
            ],
        ];

        $recentActivities = collect($sppModel?->logs ?? [])
            ->sortByDesc('created_at')
            ->take(4)
            ->map(function ($log) {
                $title = match ($log->aksi) {
                    'CREATE_DRAFT_SPP' => 'Draft SPP dibuat',
                    'UPDATE_DRAFT_SPP' => 'Draft SPP diperbarui',
                    'SUBMIT_PPK' => 'SPP diajukan ke PPK',
                    default => str_replace('_', ' ', $log->aksi ?? 'Aktivitas'),
                };

                return [
                    'title' => $title,
                    'time' => optional($log->created_at)->format('d M Y H:i'),
                    'actor' => $log->user?->name,
                    'note' => $log->catatan,
                ];
            })
            ->values();
        $autoNomorSpp = \App\Services\DocumentNumberingService::generateSppNumber(date('Y'));

        return view('spps.detail_kontrak', compact(
            'tagihan',
            'detailKontrak',
            'termin',
            'kontrak',
            'budgetItems',
            'autoNomorSpp',
            'selectedBudgetItem',
            'sppModel',
            'ppkUser',
            'kasubbagUser',
            'koordinatorUser',
            'pajaks',
            'documentStatuses',
            'readinessChecklist',
            'readinessIssues',
            'workflowSummary',
            'activitySummary',
            'recentActivities',
            'isReadyToSubmit',
            'readinessStatus',
            'ppkApproval',
            'koordinatorApproval',
            'kasubbagApproval',
        ));
    }

    public function storeKontrak(Request $request, $contract_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->with(['detailKontrak.kontrakTermin.kontrak.dipa.activeRevision.items'])
            ->findOrFail($contract_id);

        $existingSpp = $tagihan->spps()->latest()->first();

        $request->validate([
            'jumlah_uang' => 'required|numeric',
            'nomor_spp' => [
                'required',
                'string',
                Rule::unique('dokumen_spp', 'nomor_spp')->ignore($existingSpp?->id),
            ],
            'tanggal_spp' => 'required|date',
            'ppk_verifikator_id' => 'required|exists:users,id',
            'pajak' => 'nullable|array',
            'pajak.*.id' => 'required|exists:master_tarif_pajak,id',
            'pajak.*.dpp' => 'required|numeric|min:0',
            'pajak.*.nominal' => 'required|numeric|min:0',
            'file_faktur_pajak' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $kontrak = $tagihan->detailKontrak?->kontrakTermin?->kontrak;
        $defaultBudgetItemId = $existingSpp?->dipa_revision_item_id
            ?? collect(optional(optional($kontrak?->dipa)->activeRevision)->items)
                ->firstWhere('status_aktif', true)?->id;

        if (!$defaultBudgetItemId) {
            return back()->withInput()->withErrors([
                'error' => 'Kontrak ini belum memiliki item DIPA aktif yang dapat dipakai untuk dokumen SPP.',
            ]);
        }

        DB::transaction(function () use ($request, $tagihan, $existingSpp, $defaultBudgetItemId) {
            $potonganAngsuranUangMuka = (float) $tagihan->potonganTagihan()
                ->where('jenis_potongan', 'ANGSURAN_UANG_MUKA')
                ->sum('nominal_potongan');

            $tagihan->potonganTagihan()
                ->where('jenis_potongan', 'PAJAK')
                ->get()
                ->each(function ($potongan) {
                    $potongan->arsipDokumen()->delete();
                    $potongan->delete();
                });

            $totalPotonganPajak = 0;
            foreach ($request->input('pajak', []) as $pj) {
                $pajakModel = MasterTarifPajak::find($pj['id']);
                $nominal = (float) ($pj['nominal'] ?? 0);
                $totalPotonganPajak += $nominal;

                PotonganTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'pajak_id' => $pj['id'],
                    'jenis_potongan' => 'PAJAK',
                    'deskripsi' => 'Potongan pajak pada saat pembuatan dokumen SPP.',
                    'dpp' => (float) ($pj['dpp'] ?? 0),
                    'persentase_tarif_snapshot' => $pajakModel?->persentase,
                    'nama_pajak_snapshot' => $pajakModel?->jenis_pajak,
                    'nominal_potongan' => $nominal,
                ]);
            }

            $totalPotongan = $potonganAngsuranUangMuka + $totalPotonganPajak;
            $totalNetto = max(0, (float) $tagihan->total_bruto - $totalPotongan);

            $tagihan->update([
                'total_potongan' => $totalPotongan,
                'total_netto' => $totalNetto,
            ]);

            $spp = DokumenSpp::updateOrCreate(
                ['id' => $existingSpp?->id],
                [
                    'tagihan_id' => $tagihan->id,
                    'dipa_revision_item_id' => $defaultBudgetItemId,
                    'kategori_pembayaran' => optional($tagihan->mekanisme_pembayaran)->sppKategoriPembayaran() ?? 'SP2D BLU - TRF',
                    'jenis_tagihan' => $request->jenis_tagihan ?? 'NON REMUNERASI',
                    'nominal_spp' => $totalNetto,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'status' => $existingSpp && $existingSpp->status === 'Revisi' ? 'Revisi' : 'DRAFT',
                    'dibuat_oleh_id' => auth()->id(),
                    'ppk_verifikator_id' => $request->ppk_verifikator_id,
                ]
            );

            if ($request->hasFile('file_faktur_pajak') && $tagihan->detailKontrak) {
                $spp->loadMissing('tagihan.detailKontrak');

                $tagihan->detailKontrak->arsipDokumen()
                    ->where('jenis_dokumen', 'FAKTUR_PAJAK')
                    ->delete();

                $tagihan->detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'FAKTUR_PAJAK',
                    'nama_file_asli' => $request->file('file_faktur_pajak')->getClientOriginalName(),
                    'path_file' => $request->file('file_faktur_pajak')->store('tagihan/pajak', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_faktur_pajak')->getMimeType(),
                    'ukuran_file' => $request->file('file_faktur_pajak')->getSize(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            $statusSebelumnya = $tagihan->status;

            // Jangan ubah status tagihan saat hanya simpan draft SPP
            // Status PROSES_SPP hanya diset saat benar-benar submit ke PPK via submitKontrakToPpk()

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => $statusSebelumnya,
                'aksi' => $existingSpp ? 'UPDATE_DRAFT_SPP' : 'CREATE_DRAFT_SPP',
                'catatan' => 'Draft dokumen SPP kontrak disimpan oleh Operator BLU. Verifikator PPK dipilih: ' . optional(User::find($request->ppk_verifikator_id))->name,
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('spps.kontrak.detail', $tagihan->id)->with('success', 'Draft SPP kontrak berhasil disimpan.');
    }

    /**
     * Submit SPP Kontrak ke PPK — memulai workflow approval.
     */
    public function submitKontrakToPpk($contract_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->with(['spps'])
            ->findOrFail($contract_id);

        $spp = $tagihan->spps()->latest()->first();

        if (!$spp) {
            return back()->withErrors(['error' => 'Dokumen SPP belum dibuat. Silakan simpan draft terlebih dahulu.']);
        }

        if (!in_array($spp->status, ['DRAFT', 'Revisi'])) {
            return back()->withErrors(['error' => 'SPP tidak dalam status yang bisa diajukan (harus DRAFT atau Revisi).']);
        }

        if (!$spp->ppk_verifikator_id) {
            return back()->withErrors(['error' => 'Verifikator PPK belum dipilih. Silakan edit draft dan pilih verifikator.']);
        }

        DB::transaction(function () use ($tagihan, $spp) {
            $statusSebelumnya = $spp->status;

            $spp->update(['status' => 'Menunggu Verifikasi']);

            // Start workflow
            app(WorkflowService::class)->startWorkflow('SPP_KONTRAK_PPK', $spp, $spp->ppk_verifikator_id);

            // Update parent tagihan status if needed
            if (!in_array($tagihan->status, ['PROSES_SPP', 'SPP_TERBIT'])) {
                $tagihan->update(['status' => 'PROSES_SPP']);
            }

            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'Menunggu Verifikasi',
                'aksi' => 'SUBMIT_PPK',
                'catatan' => 'Dokumen SPP kontrak diajukan ke PPK untuk verifikasi.',
                'ip_address' => request()->ip(),
            ]);
        });

        // Notifikasi ke PPK assigned
        $selectedPpk = User::find($spp->ppk_verifikator_id);
        if ($selectedPpk) {
            Notification::send($selectedPpk, new WorkflowNotification([
                'title' => 'SPP Kontrak Diajukan',
                'message' => "SPP Kontrak ({$spp->nomor_spp}) menunggu verifikasi Anda.",
                'url' => route('verifikasi-spp.kontrak.index'),
                'icon' => 'receipt_long',
                'color' => 'primary',
            ]));
        }

        return redirect()->route('spps.kontrak.detail', $tagihan->id)->with('success', 'SPP kontrak berhasil diajukan ke PPK untuk verifikasi.');
    }

    // ===================================================================
    // CETAK PDF
    // ===================================================================

    public function cetakPdf($spp_id)
    {
        require_once app_path('Helpers/TerbilangHelper.php');

        // Pertama, cek menggunakan model alur baru (DokumenSpp)
        $dokumenSpp = \App\Models\DokumenSpp::with([
            'dipaRevisionItem.coa',
            'dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.pihak.rekening',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'tagihan.detailPerjaldin',
            'tagihan.detailHonorarium',
            'tagihan.dipa',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.potonganTagihan.pajak',
            'tagihan.potonganTagihan.akunPotongan',
            'ppkVerifikator.profilable',
            'workflowInstance.approvals',
        ])->find($spp_id);

        if ($dokumenSpp) {
            $spp = $dokumenSpp;
            $sppable = $dokumenSpp->tagihan;
            $jumlahUang = $spp->nominal_spp;
            $uraianSupplier = PaymentPdfReference::uraianForTagihan($sppable, $spp->uraian ?? null);
            $kodeCoa = $spp->dipaRevisionItem?->coa?->kode_mak_lengkap
                ?? $sppable?->dipaRevisionItem?->coa?->kode_mak_lengkap
                ?? $spp->akun_mak
                ?? '-';
            $potonganPajak = collect($sppable?->potonganTagihan ?? [])
                ->filter(fn ($potongan) => $potongan->jenis_potongan !== 'ANGSURAN_UANG_MUKA')
                ->values();
            $jumlahPotonganPajak = $potonganPajak->sum('nominal_potongan');
            $pdfReference = PaymentPdfReference::forTagihan($sppable);
            $dipaInfo = PaymentPdfReference::dipaForSpp($spp);
            $supplierInfo = PaymentPdfReference::supplierForTagihan($sppable, $uraianSupplier);
            
            $terbilang = terbilang_rupiah($jumlahUang);

            $pdf = Pdf::loadView('spps.pdf', compact(
                'spp',
                'sppable',
                'jumlahUang',
                'terbilang',
                'uraianSupplier',
                'kodeCoa',
                'potonganPajak',
                'jumlahPotonganPajak',
                'pdfReference',
                'dipaInfo',
                'supplierInfo'
            ));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream('SPP-BLU-' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
        }

        // Jika tidak ditemukan, fallback ke alur legacy (Spp)
        $spp = Spp::with([
            'sppable.pihak.rekening',
            'sppable.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'sppable.detailPerjaldin',
            'sppable.detailHonorarium',
            'sppable.dipa',
            'sppable.dipaRevisionItem.dipaRevision.masterDipa',
            'sppable.potonganTagihan.pajak',
            'sppable.potonganTagihan.akunPotongan',
            'dipaRevisionItem.coa',
            'dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.pihak.rekening',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'workflowInstance.approvals',
        ])->findOrFail($spp_id);
        $sppable = $spp->sppable;
        
        $jumlahUang = $spp->jumlah_uang;
        $uraianSupplier = PaymentPdfReference::uraianForTagihan($sppable, $spp->uraian ?? null);
        $kodeCoa = $spp->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $spp->tagihan?->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $spp->akun_mak
            ?? '-';
        $potonganPajak = collect($sppable?->potonganTagihan ?? [])
            ->filter(fn ($potongan) => $potongan->jenis_potongan !== 'ANGSURAN_UANG_MUKA')
            ->values();
        $jumlahPotonganPajak = $potonganPajak->sum('nominal_potongan');
        $pdfReference = PaymentPdfReference::forTagihan($sppable);
        $dipaInfo = PaymentPdfReference::dipaForSpp($spp);
        $supplierInfo = PaymentPdfReference::supplierForTagihan($sppable, $uraianSupplier);
        
        $terbilang = terbilang_rupiah($jumlahUang);

        $pdf = Pdf::loadView('spps.pdf', compact(
            'spp',
            'sppable',
            'jumlahUang',
            'terbilang',
            'uraianSupplier',
            'kodeCoa',
            'potonganPajak',
            'jumlahPotonganPajak',
            'pdfReference',
            'dipaInfo',
            'supplierInfo'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPP-BLU-' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
    }

    private function buildBudgetOptions($dipa)
    {
        return collect(optional(optional($dipa)->activeRevision)->items)
            ->filter(fn ($item) => $item->status_aktif && $item->coa)
            ->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'coa' => $item->coa->kode_mak_lengkap,
                    'description' => $item->coa->nama_akun,
                ];
            })
            ->values();
    }

    // ===================================================================
    // UPLOAD FILE SPP BERTANDATANGAN
    // ===================================================================

    /**
     * Upload file SPP Bertandatangan (PDF scan bertanda tangan basah).
     * Dipanggil dari halaman SPM (sebelum SPM bisa dibuat).
     */
    public function uploadSignedSpp(Request $request, $spp_id)
    {
        $spp = DokumenSpp::findOrFail($spp_id);

        // Hanya boleh upload jika SPP sudah disetujui
        $approvedStatuses = ['APPROVED', 'DISETUJUI_FINAL', 'DISETUJUI_SPP', 'Disetujui PPK', 'SPP_TERBIT'];
        if (!in_array($spp->status, $approvedStatuses)) {
            return back()->withErrors(['error' => 'SPP belum disetujui oleh semua verifikator.']);
        }

        $request->validate([
            'file_spp_ttd' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'file_spp_ttd.required' => 'File SPP Bertandatangan wajib diunggah.',
            'file_spp_ttd.mimes' => 'File harus berformat PDF, JPG, atau PNG.',
            'file_spp_ttd.max' => 'Ukuran file maksimal 10MB.',
        ]);

        DB::transaction(function () use ($request, $spp) {
            $file = $request->file('file_spp_ttd');
            $namaAsli = $file->getClientOriginalName();
            $path = $file->store('arsip_spp_signed/' . date('Y'), 'public');

            // Nonaktifkan arsip lama (jika ada re-upload)
            $spp->arsipDokumen()
                ->where('jenis_dokumen', DokumenSpp::SPP_SIGNED_ARCHIVE_TYPE)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Buat arsip baru
            $spp->arsipDokumen()->create([
                'jenis_dokumen' => DokumenSpp::SPP_SIGNED_ARCHIVE_TYPE,
                'nama_file_asli' => $namaAsli,
                'path_file' => $path,
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ]);

            // Log
            LogStatusDokumen::create([
                'dokumen_type' => DokumenSpp::class,
                'dokumen_id' => $spp->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $spp->status,
                'status_baru' => $spp->status,
                'aksi' => 'UPLOAD_SPP_BERTANDATANGAN',
                'catatan' => "File SPP Bertandatangan diunggah: {$namaAsli}",
                'ip_address' => request()->ip(),
            ]);
        });

        return back()->with('success', 'File SPP Bertandatangan berhasil diunggah. Anda sekarang dapat membuat draft SPM.');
    }
}
