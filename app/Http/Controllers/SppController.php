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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            ->whereIn('status', ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'])
            ->with(['detailPerjaldin', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.perjaldin_index', compact('perjaldins'));
    }

    public function detailPerjaldin($perjaldin_id)
    {
        $perjaldin = Perjaldin::with([
            'pejabats.pegawai',
            'spps.dipaRevisionItem.coa',
            'dipa.activeRevision.items.coa',
        ])->findOrFail($perjaldin_id);
        
        // Menghitung total tiap kategori
        $kategoriTotals = [
            'Tiket' => 0,
            'Transport' => 0,
            'Penginapan' => 0,
            'Uang Harian' => 0,
            'Uang Representasi' => 0,
        ];

        foreach($perjaldin->pejabats as $p) {
            $kategoriTotals['Tiket'] += (float) $p->biaya_tiket;
            $kategoriTotals['Transport'] += (float) $p->biaya_transport;
            $kategoriTotals['Penginapan'] += (float) $p->biaya_penginapan;
            $kategoriTotals['Uang Harian'] += $p->uang_harian;
            $kategoriTotals['Uang Representasi'] += $p->uang_representasi;
        }

        $budgets = $this->buildBudgetOptions($perjaldin->dipa);

        return view('spps.detail_perjaldin', compact('perjaldin', 'kategoriTotals', 'budgets'));
    }

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
            'url' => route('verifikasi-ppk.spp.index'),
            'icon' => 'receipt_long',
            'color' => 'primary'
        ]));

        return redirect()->route('spps.perjaldin.detail', $perjaldin_id)->with('success', 'SPP '.$request->kategori_biaya.' berhasil diterbitkan. Silakan cetak PDF.');
    }

    // ===================================================================
    // SPP HONOR
    // ===================================================================

    public function honorIndex()
    {
        $honorariums = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->whereIn('status', ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'])
            ->with(['detailHonorarium', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.honor_index', compact('honorariums'));
    }

    public function detailHonor($honorarium_id)
    {
        $honorarium = Transaction::with([
                'budget.activeRevision.items.coa',
                'honorariumItems',
                'spps.dipaRevisionItem.coa',
            ])
            ->where('tipe_tagihan', 'HONORARIUM')
            ->findOrFail($honorarium_id);

        $budgets = $this->buildBudgetOptions($honorarium->budget);

        return view('spps.detail_honor', compact('honorarium', 'budgets'));
    }

    public function storeHonor(Request $request, $honorarium_id)
    {
        $request->validate([
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

        $honorarium = Transaction::where('tipe_tagihan', 'HONORARIUM')->findOrFail($honorarium_id);

        $uraianText = 'Pembayaran Belanja Barang - ' . $honorarium->description;

        DB::transaction(function () use ($request, $honorarium, $uraianText) {
            $honorarium->spps()->updateOrCreate(
                [
                    'kategori_biaya' => 'Honorarium',
                ],
                [
                    'jumlah_uang' => $request->jumlah_uang,
                    'uraian' => $uraianText,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'tahun_anggaran' => $request->tahun_anggaran,
                    'nomor_dipa' => $request->nomor_dipa,
                    'tanggal_dipa' => $request->tanggal_dipa,
                    'no_kontrak' => $honorarium->bast_number ?? null,
                    'tgl_kontrak' => $honorarium->bast_date ?? null,
                    'akun_mak' => $request->akun_mak,
                    'penandatangan_nama' => $request->penandatangan_nama,
                    'penandatangan_nip' => $request->penandatangan_nip,
                    'jenis_tagihan' => 'NON REMUNERASI',
                    'jatuh_tempo' => 'Segera',
                    'cara_bayar' => 'SP2D BLU - TRF',
                    'status_spp' => 'Menunggu Verifikasi',
                    'catatan_revisi' => null,
                ]
            );

            if ($honorarium->status === 'Disetujui PPK') {
                $honorarium->update(['status' => 'Proses SPP']);
            }
        });

        // Notify PPK
        $ppks = \App\Models\User::role('PPK')->get();
        \Illuminate\Support\Facades\Notification::send($ppks, new \App\Notifications\WorkflowNotification([
            'title' => 'SPP Honor Baru',
            'message' => "SPP Honorarium ({$honorarium->transaction_number}) menunggu verifikasi Anda.",
            'url' => route('verifikasi-ppk.spp.index'),
            'icon' => 'receipt_long',
            'color' => 'primary'
        ]));

        return redirect()->route('spps.honor.detail', $honorarium_id)->with('success', 'SPP Honorarium berhasil diterbitkan.');
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
        $ppkUsers = User::role('PPK')->orderBy('name')->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderBy('name')->first();
        $pajaks = MasterTarifPajak::orderBy('jenis_pajak')->get();
        $potonganTagihans = collect($tagihan->potonganTagihan);
        $potonganPajak = $potonganTagihans->filter(fn ($item) => $item->jenis_potongan !== 'ANGSURAN_UANG_MUKA');
        $isPelunasan = ($termin?->jenis_termin ?? null) === 'PELUNASAN';
        $ebillingDocument = $sppModel?->arsipDokumen?->firstWhere('jenis_dokumen', 'E_BILLING');
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
                'key' => 'ebilling',
                'label' => 'E-Billing',
                'path' => $ebillingDocument?->path_file,
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
            ->whereIn('key', ['faktur_pajak', 'ebilling'])
            ->every(fn ($item) => in_array($item['status'], ['ready', 'not_required']));
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

        return view('spps.detail_kontrak', compact(
            'tagihan',
            'detailKontrak',
            'termin',
            'kontrak',
            'budgetItems',
            'selectedBudgetItem',
            'sppModel',
            'ppkUsers',
            'kasubbagUser',
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
            'file_ebilling' => 'nullable|file|mimes:pdf|max:5120',
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
                    'kategori_pembayaran' => 'SP2D BLU - TRF',
                    'jenis_tagihan' => 'NON REMUNERASI',
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

            if ($request->hasFile('file_ebilling')) {
                $spp->arsipDokumen()
                    ->where('jenis_dokumen', 'E_BILLING')
                    ->delete();

                $spp->arsipDokumen()->create([
                    'jenis_dokumen' => 'E_BILLING',
                    'nama_file_asli' => $request->file('file_ebilling')->getClientOriginalName(),
                    'path_file' => $request->file('file_ebilling')->store('spp/ebilling', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_ebilling')->getMimeType(),
                    'ukuran_file' => $request->file('file_ebilling')->getSize(),
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
                'url' => route('verifikasi-ppk.spp.index'),
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
        $spp = Spp::with('sppable')->findOrFail($spp_id);
        $sppable = $spp->sppable;
        
        $jumlahUang = $spp->jumlah_uang;
        $uraianSupplier = $spp->uraian;
        
        // Buat logic terbilang menggunakan helper rupiah
        $terbilang = terbilang_rupiah($jumlahUang);

        $pdf = Pdf::loadView('spps.pdf', compact('spp', 'sppable', 'jumlahUang', 'terbilang', 'uraianSupplier'));
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
}
