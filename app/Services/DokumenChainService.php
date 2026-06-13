<?php

namespace App\Services;

use App\Models\DetailDipa;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Mesin rantai dokumen pencairan untuk halaman Proses Tagihan terpadu.
 *
 * Setelah tagihan disetujui 6 verifikator, COA dipilih PPK, dan
 * Standing Instruction disetujui KPA, service ini men-generate draft
 * SPP+SPM+NPI+SP2D sekaligus. SPP & SPM diajukan/diverifikasi paralel;
 * NPI baru dapat diajukan setelah SPP dan SPM disetujui verifikatornya.
 * Setelah ketiganya disetujui, Bendahara Pengeluaran mengunggah bukti
 * transfer yang sekaligus mengajukan SP2D ke PPK (1 step).
 */
class DokumenChainService
{
    /** Status tagihan yang menandakan verifikasi 6-verifikator sudah selesai. */
    public const TAGIHAN_READY_STATUSES = ['READY_FOR_SPP', 'DISETUJUI_KONTRAK', 'DISETUJUI_PERJALDIN', 'DISETUJUI', 'PROSES_SPP'];

    /**
     * Label bagian yang dapat ditandai saat rantai dikembalikan ke pembuat
     * tagihan. SP2D sengaja tidak termasuk: SP2D hanya penerbitan akhir yang
     * ikut dibatalkan otomatis — tidak ada substansinya yang direvisi terpisah.
     */
    public const RETURNABLE_PARTS = [
        'tagihan' => 'Data Tagihan & Dokumen Pendukung',
        'spp' => 'SPP',
        'spm' => 'SPM',
        'npi' => 'NPI',
    ];

    private const WORKFLOW_SPP = [
        'KONTRAK' => 'SPP_KONTRAK_PPK',
        'HONORARIUM' => 'SPP_HONORARIUM_PPK',
        'PERJALDIN' => 'SPP_PERJALDIN',
    ];

    private const WORKFLOW_SPM = [
        'KONTRAK' => 'SPM_KONTRAK_PPSPM',
        'HONORARIUM' => 'SPM_HONORARIUM_PPSPM',
        'PERJALDIN' => 'SPM_PERJALDIN_PPSPM',
    ];

    private const WORKFLOW_NPI = [
        'KONTRAK' => 'NPI_KONTRAK',
        'HONORARIUM' => 'NPI_HONORARIUM',
        'PERJALDIN' => 'NPI_PERJALDIN',
    ];

    private const WORKFLOW_SP2D = [
        'KONTRAK' => 'SP2D_KONTRAK',
        'HONORARIUM' => 'SP2D_HONORARIUM',
        'PERJALDIN' => 'SP2D_PERJALDIN',
    ];

    public function __construct(
        private WorkflowService $workflowService,
        private BudgetRealizationService $budgetRealizationService,
        private BkuPostingService $bkuPostingService,
    ) {
    }

    // ─────────────────────────────────────────────────────────────────
    // Pembacaan state rantai
    // ─────────────────────────────────────────────────────────────────

    /**
     * SPP rantai terpadu milik tagihan. Untuk perjaldin hanya SPP konsolidasi
     * (bukan SPP per-komponen alur lama).
     */
    public function chainSpp(Tagihan $tagihan): ?DokumenSpp
    {
        $query = $tagihan->spps()->with('spm.npi.sp2d');

        if ($tagihan->tipe_tagihan === 'PERJALDIN') {
            $query->whereNull('tagihan_perjaldin_komponen_id');
        }

        return $query->latest('id')->first();
    }

    /** Apakah tagihan masih memakai rantai per-komponen alur lama (perjaldin). */
    public function hasLegacyKomponenChain(Tagihan $tagihan): bool
    {
        return $tagihan->tipe_tagihan === 'PERJALDIN'
            && $tagihan->spps()->whereNotNull('tagihan_perjaldin_komponen_id')->exists();
    }

    public function isTagihanFullyApproved(Tagihan $tagihan): bool
    {
        return in_array($tagihan->status, self::TAGIHAN_READY_STATUSES, true)
            || $tagihan->status === 'SELESAI';
    }

    public function isCoaComplete(Tagihan $tagihan): bool
    {
        if ($tagihan->tipe_tagihan === 'PERJALDIN') {
            $komponens = $tagihan->komponenPerjaldin()->where('total_nominal', '>', 0)->get();

            return $komponens->isNotEmpty()
                && $komponens->every(fn ($k) => ! empty($k->dipa_revision_item_id));
        }

        return ! empty($tagihan->dipa_revision_item_id);
    }

    public function isKpaApproved(Tagihan $tagihan): bool
    {
        return $tagihan->kpa_approval_status === 'APPROVED';
    }

    /**
     * Khusus tagihan KONTRAK: Operator BLU wajib memilih tipe pajak
     * (baris potongan_tagihan jenis PAJAK) sebelum draft rantai dibuat.
     */
    public function isPajakTipeDipilih(Tagihan $tagihan): bool
    {
        if ($tagihan->tipe_tagihan !== 'KONTRAK') {
            return true;
        }

        return $tagihan->potonganTagihan()->where('jenis_potongan', 'PAJAK')->exists();
    }

    /** Khusus tagihan KONTRAK: faktur pajak wajib diunggah sebelum draft rantai dibuat. */
    public function hasFakturPajak(Tagihan $tagihan): bool
    {
        if ($tagihan->tipe_tagihan !== 'KONTRAK') {
            return true;
        }

        return (bool) $tagihan->detailKontrak?->file_faktur_pajak;
    }

    public function isPajakKontrakComplete(Tagihan $tagihan): bool
    {
        return $this->isPajakTipeDipilih($tagihan) && $this->hasFakturPajak($tagihan);
    }

    /**
     * Rantai dokumen masih sepenuhnya berupa draft/revisi (belum ada yang
     * diajukan/diverifikasi), sehingga nominalnya masih aman disesuaikan.
     */
    public function isChainStillDraft(Tagihan $tagihan): bool
    {
        $spp = $this->chainSpp($tagihan);
        if (! $spp) {
            return false;
        }

        $draftish = ['DRAFT', 'Revisi', 'REVISI'];
        $docs = array_filter([$spp, $spp->spm, $spp->spm?->npi, $spp->spm?->npi?->sp2d]);

        foreach ($docs as $doc) {
            if (! in_array($doc->status, $draftish, true)) {
                return false;
            }
        }

        return true;
    }

    /** Daftar prasyarat generate draft yang belum terpenuhi (untuk pesan UI). */
    public function missingDraftPrerequisites(Tagihan $tagihan): array
    {
        $missing = [];

        if (! $this->isTagihanFullyApproved($tagihan)) {
            $missing[] = 'Tagihan belum disetujui penuh oleh seluruh verifikator.';
        }
        if (! $this->isCoaComplete($tagihan)) {
            $missing[] = $tagihan->tipe_tagihan === 'PERJALDIN'
                ? 'COA belum dipilih untuk semua komponen biaya.'
                : 'COA (item DIPA) belum dipilih untuk tagihan ini.';
        }
        if (! $this->isKpaApproved($tagihan)) {
            $missing[] = 'Standing Instruction belum disetujui KPA.';
        }
        if (! $this->isPajakTipeDipilih($tagihan)) {
            $missing[] = 'Tipe pajak belum dipilih oleh Operator BLU untuk tagihan kontrak ini.';
        }
        if (! $this->hasFakturPajak($tagihan)) {
            $missing[] = 'Faktur pajak belum diunggah oleh Operator BLU untuk tagihan kontrak ini.';
        }

        foreach ($this->missingVerifierColumns($tagihan) as $label) {
            $missing[] = "Verifikator {$label} belum ditentukan pada tagihan.";
        }

        return $missing;
    }

    /**
     * Status workflow terakhir milik sebuah dokumen rantai (null bila belum diajukan).
     */
    public function latestInstance($document): ?WorkflowInstance
    {
        if (! $document) {
            return null;
        }

        // getMorphClass(): model alias (mis. Spp extends DokumenSpp) tetap
        // cocok dengan tipe yang disimpan WorkflowService saat pengajuan.
        return WorkflowInstance::where('workflowable_type', $document->getMorphClass())
            ->where('workflowable_id', $document->getKey())
            ->latest('id')
            ->first();
    }

    public function isDocumentApproved($document): bool
    {
        if (! $document) {
            return false;
        }

        return WorkflowInstance::where('workflowable_type', $document->getMorphClass())
            ->where('workflowable_id', $document->getKey())
            ->where('status', 'APPROVED')
            ->exists();
    }

    // ─────────────────────────────────────────────────────────────────
    // Generate draft rantai
    // ─────────────────────────────────────────────────────────────────

    /**
     * Generate draft SPP+SPM+NPI+SP2D bila seluruh prasyarat terpenuhi.
     * Idempoten: mengembalikan SPP yang sudah ada tanpa membuat ulang.
     */
    public function maybeGenerateDraftChain(Tagihan $tagihan, ?User $actor = null): ?DokumenSpp
    {
        return DB::transaction(function () use ($tagihan, $actor) {
            $tagihan = Tagihan::whereKey($tagihan->id)->lockForUpdate()->firstOrFail();

            $existing = $this->chainSpp($tagihan);
            if ($existing) {
                return $existing;
            }

            if ($this->hasLegacyKomponenChain($tagihan)) {
                return null; // tagihan berjalan di alur per-komponen lama
            }

            if ($this->missingDraftPrerequisites($tagihan) !== []) {
                return null;
            }

            $this->assertSisaPaguCukup($tagihan);

            $tahun = (int) date('Y');
            $nominal = (float) $tagihan->total_netto;
            $nomorSpp = app(DocumentNumberService::class)->generateByKey('SPP_BLU', $tahun);
            $dipaItemId = $tagihan->tipe_tagihan === 'PERJALDIN'
                ? $tagihan->komponenPerjaldin()->where('total_nominal', '>', 0)->whereNotNull('dipa_revision_item_id')->value('dipa_revision_item_id')
                : $tagihan->dipa_revision_item_id;

            $spp = DokumenSpp::create([
                'tagihan_id' => $tagihan->id,
                'tagihan_perjaldin_komponen_id' => null,
                'dipa_revision_item_id' => $dipaItemId,
                'kategori_pembayaran' => optional($tagihan->mekanisme_pembayaran)->sppKategoriPembayaran() ?? 'SP2D BLU - TRF',
                'jenis_tagihan' => 'NON REMUNERASI',
                'nominal_spp' => $nominal,
                'nomor_spp' => $nomorSpp,
                'tanggal_spp' => now()->toDateString(),
                'status' => 'DRAFT',
                'dibuat_oleh_id' => $actor?->id,
                'ppk_verifikator_id' => $tagihan->ppk_user_id,
            ]);

            $spm = DokumenSpm::create([
                'spp_id' => $spp->id,
                'nomor_spm' => DocumentNumberingService::generateDerivedNumber($nomorSpp, 'SPM'),
                'tanggal_spm' => now()->toDateString(),
                'ppspm_id' => $tagihan->ppspm_user_id,
                'dipa_revision_item_id' => $dipaItemId,
                'tahun_anggaran' => (string) $tahun,
                'jenis_tagihan' => 'NON REMUNERASI',
                'jatuh_tempo' => 'Segera',
                'cara_bayar' => optional($tagihan->mekanisme_pembayaran)->spmCaraBayar() ?? 'SP2D BLU - TRF',
                'nominal_spm' => $nominal,
                'dibuat_oleh_id' => $actor?->id,
                'status' => DokumenSpm::STATUS_DRAFT,
            ]);

            $npi = DokumenNpi::create([
                'spm_id' => $spm->id,
                'nomor_npi' => DocumentNumberingService::generateDerivedNumber($nomorSpp, 'NPI'),
                'tanggal_npi' => now()->toDateString(),
                'bendahara_penerimaan_id' => $tagihan->bendahara_penerimaan_user_id,
                'tahun_anggaran' => (string) $tahun,
                'status' => DokumenNpi::STATUS_DRAFT,
            ]);

            DokumenSp2d::create([
                'npi_id' => $npi->id,
                'nomor_sp2d' => DocumentNumberingService::generateDerivedNumber($nomorSpp, 'SP2D'),
                'tanggal_sp2d' => now()->toDateString(),
                'bendahara_pengeluaran_id' => $tagihan->bendahara_pengeluaran_user_id,
                'status' => DokumenSp2d::STATUS_DRAFT,
            ]);

            if (! in_array($tagihan->status, ['PROSES_SPP', 'SELESAI'], true)) {
                $tagihan->update(['status' => 'PROSES_SPP']);
            }

            if ($tagihan->tipe_tagihan === 'PERJALDIN') {
                $tagihan->komponenPerjaldin->each->syncStatusFromDocuments();
            }

            $this->log($tagihan, $actor, 'GENERATE_DRAFT_CHAIN',
                "Draft SPP/SPM/NPI/SP2D ({$nomorSpp}) di-generate otomatis setelah COA & persetujuan KPA terpenuhi.");

            return $spp->fresh('spm.npi.sp2d');
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Pengajuan SPP / SPM (paralel) dan NPI (setelah SPP & SPM disetujui)
    // ─────────────────────────────────────────────────────────────────

    public function submitSpp(Tagihan $tagihan, User $actor): void
    {
        $spp = $this->chainSpp($tagihan);
        $this->assertSubmittable($spp, 'SPP');

        DB::transaction(function () use ($tagihan, $spp, $actor) {
            $spp->update(['status' => 'Menunggu Verifikasi']);
            $instance = $this->workflowService->startWorkflow(
                self::WORKFLOW_SPP[$tagihan->tipe_tagihan],
                $spp,
                $spp->ppk_verifikator_id ?: $tagihan->ppk_user_id
            );
            $this->logDokumen($spp, $actor, 'SUBMIT_SPP', 'SPP diajukan untuk verifikasi (alur terpadu).');
            $this->notifyAfterCommit($instance);
        });
    }

    public function submitSpm(Tagihan $tagihan, User $actor): void
    {
        $spm = $this->chainSpp($tagihan)?->spm;
        $this->assertSubmittable($spm, 'SPM');

        DB::transaction(function () use ($tagihan, $spm, $actor) {
            $spm->update(['status' => DokumenSpm::STATUS_MENUNGGU_VERIFIKASI]);
            $instance = $this->workflowService->startWorkflow(
                self::WORKFLOW_SPM[$tagihan->tipe_tagihan],
                $spm,
                $spm->ppspm_id ?: $tagihan->ppspm_user_id
            );
            $this->logDokumen($spm, $actor, 'SUBMIT_SPM', 'SPM diajukan untuk verifikasi (alur terpadu).');
            $this->notifyAfterCommit($instance);
        });
    }

    public function submitNpi(Tagihan $tagihan, User $actor): void
    {
        $spp = $this->chainSpp($tagihan);
        $spm = $spp?->spm;
        $npi = $spm?->npi;
        $this->assertSubmittable($npi, 'NPI');

        // NPI menunggu SPP & SPM selesai diverifikasi terlebih dahulu.
        if (! $this->isDocumentApproved($spp) || ! $this->isDocumentApproved($spm)) {
            throw new \RuntimeException('NPI baru dapat diajukan setelah SPP dan SPM disetujui oleh verifikatornya.');
        }

        DB::transaction(function () use ($tagihan, $npi, $actor) {
            $npi->update(['status' => DokumenNpi::STATUS_MENUNGGU_VERIFIKASI]);
            $instance = $this->workflowService->startWorkflow(
                self::WORKFLOW_NPI[$tagihan->tipe_tagihan],
                $npi,
                $tagihan->ppk_user_id
            );
            $this->logDokumen($npi, $actor, 'SUBMIT_NPI', 'NPI diajukan untuk verifikasi (alur terpadu).');
            $this->notifyAfterCommit($instance);
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Bukti transfer + pengajuan SP2D (1 step PPK)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Upload bukti transfer oleh Bendahara Pengeluaran sekaligus mengajukan
     * SP2D ke PPK. Hanya boleh setelah SPP, SPM, dan NPI semuanya disetujui.
     */
    public function submitSp2d(Tagihan $tagihan, User $actor, UploadedFile $buktiTransfer, ?string $tanggalSp2d = null): void
    {
        $spp = $this->chainSpp($tagihan);
        $spm = $spp?->spm;
        $npi = $spm?->npi;
        $sp2d = $npi?->sp2d;

        if (! $sp2d) {
            throw new \RuntimeException('Draft SP2D belum tersedia pada rantai dokumen tagihan ini.');
        }

        if (! $this->isDocumentApproved($spp) || ! $this->isDocumentApproved($spm) || ! $this->isDocumentApproved($npi)) {
            throw new \RuntimeException('Bukti transfer baru dapat diunggah setelah SPP, SPM, dan NPI semuanya disetujui verifikator.');
        }

        if (! in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI], true)) {
            throw new \RuntimeException('SP2D tidak dalam status draft/revisi sehingga tidak dapat diajukan.');
        }

        DB::transaction(function () use ($tagihan, $sp2d, $actor, $buktiTransfer, $tanggalSp2d) {
            $sp2d->arsipDokumen()
                ->where('jenis_dokumen', 'BUKTI_TRANSFER_SP2D')
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $path = $buktiTransfer->store('sp2d/bukti-transfer', 'local');
            $sp2d->arsipDokumen()->create([
                'jenis_dokumen' => 'BUKTI_TRANSFER_SP2D',
                'nama_file_asli' => $buktiTransfer->getClientOriginalName(),
                'path_file' => $path,
                'disk' => 'local',
                'mime_type' => $buktiTransfer->getMimeType(),
                'ukuran_file' => $buktiTransfer->getSize(),
                'uploaded_by' => $actor->id,
                'uploaded_at' => now(),
                'keterangan' => 'Bukti transfer SP2D (diunggah sebelum SP2D terbit).',
                'is_active' => true,
            ]);

            $sp2d->update([
                'tanggal_sp2d' => $tanggalSp2d ?: now()->toDateString(),
                'bendahara_pengeluaran_id' => $sp2d->bendahara_pengeluaran_id ?: $actor->id,
                'status' => DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI,
            ]);

            $instance = $this->workflowService->startWorkflow(
                self::WORKFLOW_SP2D[$tagihan->tipe_tagihan],
                $sp2d,
                $tagihan->ppk_user_id
            );

            $this->logDokumen($sp2d, $actor, 'SUBMIT_SP2D',
                'Bukti transfer diunggah; SP2D diajukan ke PPK untuk penerbitan.');
            $this->notifyAfterCommit($instance);
        });
    }

    /**
     * Finalisasi setelah PPK menyetujui SP2D: terbit (EXECUTED), realisasi
     * anggaran, tagihan SELESAI, posting BKU (ditunda bila ada pajak belum NTPN).
     * Logika diekstrak dari Sp2dController::catatBku.
     *
     * @return array{deferBkuUntilTax: bool, postedToBku: bool}
     */
    public function finalizeSp2d(DokumenSp2d $sp2d, User $actor, ?string $catatan = null): array
    {
        $deferBkuUntilTax = false;
        $postedToBku = false;

        DB::transaction(function () use ($sp2d, $actor, $catatan, &$deferBkuUntilTax, &$postedToBku) {
            $sp2d->loadMissing('npi.spm.spp.tagihan.potonganTagihan');
            $statusSebelumnya = $sp2d->status;

            $sp2d->update([
                'status' => DokumenSp2d::STATUS_EXECUTED,
                'bendahara_pengeluaran_id' => $sp2d->bendahara_pengeluaran_id ?: $actor->id,
            ]);

            $this->budgetRealizationService->recordFromSp2d($sp2d);

            $tagihan = $sp2d->npi?->spm?->spp?->tagihan;
            if ($tagihan) {
                $statusTagihanSebelumnya = $tagihan->status;
                $tagihan->update(['status' => 'SELESAI']);
                $this->log($tagihan, $actor, 'SP2D_FINAL', 'Tagihan diselesaikan setelah SP2D terbit.', $statusTagihanSebelumnya, 'SELESAI');

                $hasTax = in_array($tagihan->tipe_tagihan, ['KONTRAK', 'HONORARIUM'], true)
                    && $tagihan->potonganTagihan()
                        ->where('jenis_potongan', 'PAJAK')
                        ->where('nominal_potongan', '>', 0)
                        ->exists();

                $hasUnsettledTax = $hasTax
                    && $tagihan->potonganTagihan()
                        ->where('jenis_potongan', 'PAJAK')
                        ->where('nominal_potongan', '>', 0)
                        ->where(function ($q) {
                            $q->whereNull('ntpn')->orWhere('ntpn', '');
                        })
                        ->exists();

                $deferBkuUntilTax = $hasTax && $hasUnsettledTax;

                if (! $deferBkuUntilTax) {
                    $this->bkuPostingService->postTagihanPengeluaran(
                        $tagihan,
                        $sp2d,
                        $catatan ?: null,
                        $hasTax ? (float) ($tagihan->total_bruto ?? $tagihan->total_netto ?? 0) : null
                    );
                    $postedToBku = true;
                }

                if ($tagihan->tipe_tagihan === 'KONTRAK') {
                    $sp2d->unlockNextTerminKontrak();
                }

                if ($tagihan->tipe_tagihan === 'PERJALDIN') {
                    $tagihan->komponenPerjaldin->each->syncStatusFromDocuments();
                }
            }

            $this->logDokumen($sp2d, $actor, 'EXECUTE_PAYMENT',
                $catatan ?: 'SP2D terbit setelah disetujui PPK; tagihan diselesaikan.',
                $statusSebelumnya, DokumenSp2d::STATUS_EXECUTED);

            Notification::send(
                User::role(['Operator Perjaldin', 'Operator BLU'])->get(),
                new WorkflowNotification([
                    'title' => 'SP2D Terbit & Dana Cair',
                    'message' => "SP2D {$sp2d->nomor_sp2d} telah terbit dan tercatat.",
                    'url' => null,
                    'icon' => 'notifications',
                    'color' => 'primary',
                ])
            );
        });

        return ['deferBkuUntilTax' => $deferBkuUntilTax, 'postedToBku' => $postedToBku];
    }

    // ─────────────────────────────────────────────────────────────────
    // Pembatalan rantai setelah reject permanen
    // ─────────────────────────────────────────────────────────────────

    /**
     * Batalkan seluruh rantai (soft delete) agar bisa di-generate ulang.
     * Hanya boleh bila SP2D belum terbit dan ada dokumen yang ditolak permanen.
     */
    public function cancelChain(Tagihan $tagihan, User $actor, ?string $alasan = null): void
    {
        $spp = $this->chainSpp($tagihan);
        if (! $spp) {
            throw new \RuntimeException('Tidak ada rantai dokumen yang dapat dibatalkan.');
        }

        $sp2d = $spp->spm?->npi?->sp2d;
        if ($sp2d && $sp2d->status === DokumenSp2d::STATUS_EXECUTED) {
            throw new \RuntimeException('Rantai tidak dapat dibatalkan karena SP2D sudah terbit.');
        }

        DB::transaction(function () use ($tagihan, $spp, $actor, $alasan) {
            $this->deleteChainDocuments($spp);

            $resetStatus = $tagihan->tipe_tagihan === 'PERJALDIN' ? 'DISETUJUI_PERJALDIN' : 'READY_FOR_SPP';
            $tagihan->update(['status' => $resetStatus]);

            if ($tagihan->tipe_tagihan === 'PERJALDIN') {
                $tagihan->komponenPerjaldin->each->syncStatusFromDocuments();
            }

            $this->log($tagihan, $actor, 'CANCEL_DRAFT_CHAIN',
                $alasan ?: 'Rantai dokumen pencairan dibatalkan untuk dibuat ulang.');
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Pengembalian rantai ke pembuat tagihan (revisi substantif)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Verifikator dokumen rantai mengembalikan tagihan ke pembuatnya:
     * seluruh rantai dibatalkan, persetujuan KPA di-reset, dan tagihan
     * kembali ke status REVISI_{role} agar pembuat dapat memperbaiki data
     * lalu mengajukan ulang lewat alur verifikasi tagihan yang sudah ada.
     *
     * @param array<string,string> $catatanPerBagian key dari RETURNABLE_PARTS => catatan revisi
     */
    public function returnChainToCreator(Tagihan $tagihan, User $actor, array $catatanPerBagian, ?string $catatanUmum = null): void
    {
        $spp = $this->chainSpp($tagihan);
        if (! $spp) {
            throw new \RuntimeException('Tidak ada rantai dokumen yang dapat dikembalikan.');
        }

        $sp2d = $spp->spm?->npi?->sp2d;
        if ($sp2d && $sp2d->status === DokumenSp2d::STATUS_EXECUTED) {
            throw new \RuntimeException('Tagihan tidak dapat dikembalikan karena SP2D sudah terbit.');
        }

        $ringkasan = $this->ringkasanCatatanRevisi($catatanPerBagian, $catatanUmum);

        DB::transaction(function () use ($tagihan, $spp, $actor, $catatanPerBagian, $ringkasan) {
            // Catat permintaan revisi per dokumen sebelum rantai dihapus,
            // agar jejaknya tetap terbaca di log masing-masing dokumen.
            $docsByKey = [
                'spp' => $spp,
                'spm' => $spp->spm,
                'npi' => $spp->spm?->npi,
            ];
            foreach ($catatanPerBagian as $key => $catatan) {
                if (isset($docsByKey[$key]) && $docsByKey[$key]) {
                    $this->logDokumen($docsByKey[$key], $actor, 'REVISI_DIMINTA', $catatan);
                }
            }

            $this->deleteChainDocuments($spp);

            // Kembalikan workflow tagihan ke state REVISION supaya alur
            // perbaikan + pengajuan ulang existing per tipe tagihan terpakai.
            $rolePrefix = $this->revisiRolePrefixFor($actor);
            $instance = WorkflowInstance::where('workflowable_type', Tagihan::class)
                ->where('workflowable_id', $tagihan->id)
                ->latest('id')
                ->first();

            if ($instance) {
                // Utamakan approval ber-role sama dengan prefix status REVISI_{role}
                // agar atribusi konsisten untuk user multi-role.
                $approval = $instance->approvals()
                    ->whereIn('role_code', $this->roleCodeVariantsForPrefix($rolePrefix))
                    ->orderByDesc('urutan_step')
                    ->first()
                    ?? $instance->approvals()
                        ->whereIn('role_code', $this->roleCodeVariantsFor($actor))
                        ->orderByDesc('urutan_step')
                        ->first();

                $approval?->update([
                        'status' => 'REVISION',
                        'acted_by_user_id' => $actor->id,
                        'acted_at' => now(),
                        'catatan' => $ringkasan,
                        'ip_address' => request()->ip(),
                    ]);
                $instance->update(['status' => 'REVISION']);
            }

            $statusLama = $tagihan->status;
            $tagihan->update([
                'status' => "REVISI_{$rolePrefix}",
                // Data tagihan akan berubah — Standing Instruction harus
                // dimintakan ulang ke KPA setelah verifikasi ulang selesai.
                'kpa_approval_status' => null,
                'kpa_approved_at' => null,
                'kpa_approved_by' => null,
                'kpa_approval_notes' => null,
            ]);

            if ($tagihan->tipe_tagihan === 'PERJALDIN') {
                $tagihan->komponenPerjaldin->each->syncStatusFromDocuments();
            }

            $this->log($tagihan, $actor, 'KEMBALI_KE_PEMBUAT',
                "Rantai dokumen pencairan dibatalkan dan tagihan dikembalikan ke pembuatnya untuk revisi.\n{$ringkasan}",
                $statusLama, $tagihan->status);

            $this->notifyCreatorAfterCommit($tagihan, $ringkasan);
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Perbaikan terarah: pajak (Operator BLU) / COA (PPK)
    // ─────────────────────────────────────────────────────────────────

    /** Penanggung jawab perbaikan per target rewind rantai. */
    public const CORRECTION_TARGETS = [
        'pajak' => ['label' => 'Pajak & Faktur Pajak', 'role' => 'Operator BLU', 'marker' => 'PAJAK'],
        'coa' => ['label' => 'Pembebanan COA', 'role' => 'PPK', 'marker' => 'COA'],
    ];

    /**
     * Verifikator dokumen rantai mengembalikan rantai untuk perbaikan pajak
     * (Operator BLU) atau COA (PPK): seluruh rantai dibatalkan dan persetujuan
     * KPA di-reset (Standing Instruction memuat netto/COA), tetapi hasil
     * verifikasi tagihan oleh 6 verifikator TIDAK diulang — tagihan kembali
     * ke status siap-proses dan penanggung jawab memperbaiki bagiannya.
     */
    public function rewindChainForCorrection(Tagihan $tagihan, User $actor, string $target, string $catatan): void
    {
        $info = self::CORRECTION_TARGETS[$target] ?? null;
        if (! $info) {
            throw new \RuntimeException('Target perbaikan tidak dikenali.');
        }

        $spp = $this->chainSpp($tagihan);
        if (! $spp) {
            throw new \RuntimeException('Tidak ada rantai dokumen yang dapat dikembalikan.');
        }

        $sp2d = $spp->spm?->npi?->sp2d;
        if ($sp2d && $sp2d->status === DokumenSp2d::STATUS_EXECUTED) {
            throw new \RuntimeException('Rantai tidak dapat dikembalikan karena SP2D sudah terbit.');
        }

        DB::transaction(function () use ($tagihan, $spp, $actor, $target, $catatan, $info) {
            $this->deleteChainDocuments($spp);

            $statusLama = $tagihan->status;
            $tagihan->update([
                // Status kembali ke siap-proses (hasil verifikasi tagihan tetap
                // berlaku) — draft dibuat ulang setelah prasyarat terpenuhi lagi.
                'status' => $tagihan->tipe_tagihan === 'PERJALDIN' ? 'DISETUJUI_PERJALDIN' : 'READY_FOR_SPP',
                // SI KPA memuat nominal netto & sumber dana — wajib diminta
                // ulang setelah pajak/COA diperbaiki.
                'kpa_approval_status' => null,
                'kpa_approved_at' => null,
                'kpa_approved_by' => null,
                'kpa_approval_notes' => null,
                'chain_correction_target' => $info['marker'],
                'chain_correction_note' => $catatan,
                'chain_correction_requested_by' => $actor->id,
                'chain_correction_requested_at' => now(),
            ]);

            if ($tagihan->tipe_tagihan === 'PERJALDIN') {
                $tagihan->komponenPerjaldin->each->syncStatusFromDocuments();
            }

            $this->log($tagihan, $actor, 'REVISI_' . $info['marker'],
                "Rantai dokumen pencairan dibatalkan untuk perbaikan {$info['label']} oleh {$info['role']}.\n{$catatan}",
                $statusLama, $tagihan->status);

            $this->notifyCorrectionAfterCommit($tagihan, $target, $catatan, $actor);
        });
    }

    /** Hapus penanda perbaikan setelah bagian terkait disimpan ulang. */
    public function clearChainCorrection(Tagihan $tagihan, string $target): void
    {
        $marker = self::CORRECTION_TARGETS[$target]['marker'] ?? null;
        if (! $marker || $tagihan->chain_correction_target !== $marker) {
            return;
        }

        $tagihan->update([
            'chain_correction_target' => null,
            'chain_correction_note' => null,
            'chain_correction_requested_by' => null,
            'chain_correction_requested_at' => null,
        ]);
    }

    /** Notifikasi in-app + WA best-effort ke penanggung jawab perbaikan. */
    private function notifyCorrectionAfterCommit(Tagihan $tagihan, string $target, string $catatan, User $actor): void
    {
        $info = self::CORRECTION_TARGETS[$target];

        DB::afterCommit(function () use ($tagihan, $catatan, $actor, $info) {
            // COA: utamakan PPK yang ditugaskan pada tagihan; fallback ke role.
            $recipients = collect();
            if ($info['role'] === 'PPK' && $tagihan->ppk_user_id) {
                $recipients = User::whereKey($tagihan->ppk_user_id)->get();
            }
            if ($recipients->isEmpty()) {
                try {
                    $recipients = User::role($info['role'])->get();
                } catch (\Throwable) {
                    return;
                }
            }
            if ($recipients->isEmpty()) {
                return;
            }

            $url = route('proses-tagihan.show', $tagihan->id);

            Notification::send($recipients, new WorkflowNotification([
                'title' => "Permintaan Perbaikan {$info['label']}",
                'message' => "Tagihan {$tagihan->nomor_tagihan}: rantai dokumen dibatalkan oleh {$actor->name} untuk perbaikan {$info['label']}. Catatan: {$catatan}",
                'url' => $url,
                'icon' => 'assignment_return',
                'color' => 'warning',
            ]));

            foreach ($recipients as $user) {
                try {
                    if ($user->profilable instanceof \App\Models\MasterPegawai && $user->profilable->nomor_hp) {
                        $phone = preg_replace('/\D+/', '', $user->profilable->nomor_hp);
                        if (strlen($phone) >= 9) {
                            app(WhatsappService::class)->sendMessage($phone,
                                "*Notifikasi SIKEREN*\n\nTagihan {$tagihan->nomor_tagihan} memerlukan perbaikan *{$info['label']}*:\n{$catatan}\n\nSilakan perbaiki melalui:\n{$url}");
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Gagal kirim WA permintaan perbaikan rantai.', [
                        'tagihan_id' => $tagihan->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Notifikasi (in-app + WA best-effort) ke pengaju dokumen rantai saat
     * verifikator meminta revisi satu dokumen saja (kini hanya dipakai SP2D:
     * perbaikan bukti transfer oleh Bendahara Pengeluaran).
     */
    public function notifyDocumentRevisionRequested(Tagihan $tagihan, string $jenis, string $catatan, User $actor): void
    {
        $jenis = strtolower($jenis);
        $role = in_array($jenis, ['spp', 'spm'], true) ? 'Operator BLU' : 'Bendahara Pengeluaran';
        $label = strtoupper($jenis);

        DB::afterCommit(function () use ($tagihan, $role, $label, $catatan, $actor) {
            $recipients = User::role($role)->get();
            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new WorkflowNotification([
                'title' => "Permintaan Revisi {$label}",
                'message' => "{$label} tagihan {$tagihan->nomor_tagihan} diminta revisi oleh {$actor->name}: {$catatan}",
                'url' => route('proses-tagihan.show', $tagihan->id),
                'icon' => 'assignment_return',
                'color' => 'warning',
            ]));
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /** Soft-delete seluruh dokumen rantai dan tutup workflow instance-nya. */
    private function deleteChainDocuments(DokumenSpp $spp): void
    {
        $documents = array_filter([
            $spp->spm?->npi?->sp2d,
            $spp->spm?->npi,
            $spp->spm,
            $spp,
        ]);

        foreach ($documents as $doc) {
            WorkflowInstance::where('workflowable_type', $doc->getMorphClass())
                ->where('workflowable_id', $doc->getKey())
                ->whereIn('status', ['IN_PROGRESS', 'DRAFT', 'REVISION'])
                ->update(['status' => 'REJECTED']);
            $doc->delete();
        }
    }

    /**
     * Prefix role untuk status REVISI_{role} tagihan. Hanya menghasilkan
     * status yang diterima allowedSubmitStatuses pada workflow service
     * tagihan (Perjaldin/Kontrak/Honorarium) agar pengajuan ulang tidak buntu.
     */
    private function revisiRolePrefixFor(User $actor): string
    {
        $map = [
            'PPK' => 'PPK',
            'PPSPM' => 'PPSPM',
            'Bendahara Penerimaan' => 'BENDAHARA_PENERIMAAN',
            'Bendahara Pengeluaran' => 'BENDAHARA_PENGELUARAN',
            'Koordinator Keuangan' => 'KOORDINATOR_KEUANGAN',
            'Kepala Subbagian Keuangan dan Tata Usaha' => 'KASUBBAG',
        ];

        foreach ($actor->getRoleNames() as $name) {
            if (isset($map[$name])) {
                return $map[$name];
            }
        }

        return 'PPK';
    }

    /** Varian role_code untuk satu prefix status REVISI_{role}. */
    private function roleCodeVariantsForPrefix(string $rolePrefix): array
    {
        return match ($rolePrefix) {
            'PPK' => ['PPK'],
            'PPSPM' => ['PPSPM'],
            'BENDAHARA_PENERIMAAN' => ['Bendahara Penerimaan', 'BENDAHARA_PENERIMAAN'],
            'BENDAHARA_PENGELUARAN' => ['Bendahara Pengeluaran', 'BENDAHARA_PENGELUARAN'],
            'KOORDINATOR_KEUANGAN' => ['Koordinator Keuangan', 'KOORDINATOR_KEUANGAN'],
            'KASUBBAG' => ['Kepala Subbagian Keuangan dan Tata Usaha', 'KASUBBAG'],
            default => [$rolePrefix],
        };
    }

    /** Varian role_code milik actor untuk mencocokkan approval workflow tagihan. */
    private function roleCodeVariantsFor(User $actor): array
    {
        $map = [
            'PPK' => ['PPK'],
            'PPSPM' => ['PPSPM'],
            'Koordinator Keuangan' => ['Koordinator Keuangan', 'KOORDINATOR_KEUANGAN'],
            'Kepala Subbagian Keuangan dan Tata Usaha' => ['Kepala Subbagian Keuangan dan Tata Usaha', 'KASUBBAG'],
            'Bendahara Penerimaan' => ['Bendahara Penerimaan', 'BENDAHARA_PENERIMAAN'],
            'Bendahara Pengeluaran' => ['Bendahara Pengeluaran', 'BENDAHARA_PENGELUARAN'],
        ];

        $codes = [];
        foreach ($actor->getRoleNames() as $name) {
            foreach ($map[$name] ?? [$name] as $code) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /** Gabungkan catatan umum + catatan per bagian menjadi satu teks log/notifikasi. */
    private function ringkasanCatatanRevisi(array $catatanPerBagian, ?string $catatanUmum): string
    {
        $parts = [];
        foreach ($catatanPerBagian as $key => $catatan) {
            $label = self::RETURNABLE_PARTS[$key] ?? strtoupper($key);
            $parts[] = "[{$label}] {$catatan}";
        }

        return trim(($catatanUmum ? trim($catatanUmum) . "\n" : '') . implode("\n", $parts));
    }

    /** Notifikasi in-app + WA best-effort ke pembuat tagihan setelah commit. */
    private function notifyCreatorAfterCommit(Tagihan $tagihan, string $ringkasan): void
    {
        DB::afterCommit(function () use ($tagihan, $ringkasan) {
            $recipients = $this->creatorRecipients($tagihan);
            if ($recipients->isEmpty()) {
                return;
            }

            $url = $this->tagihanDetailUrl($tagihan);

            Notification::send($recipients, new WorkflowNotification([
                'title' => 'Tagihan Dikembalikan untuk Revisi',
                'message' => "Tagihan {$tagihan->nomor_tagihan} dikembalikan dari proses pencairan untuk diperbaiki. " . str_replace("\n", ' • ', $ringkasan),
                'url' => $url,
                'icon' => 'assignment_return',
                'color' => 'warning',
            ]));

            foreach ($recipients as $user) {
                try {
                    if ($user->profilable instanceof \App\Models\MasterPegawai && $user->profilable->nomor_hp) {
                        $phone = preg_replace('/\D+/', '', $user->profilable->nomor_hp);
                        if (strlen($phone) >= 9) {
                            app(WhatsappService::class)->sendMessage($phone,
                                "*Notifikasi SIKEREN*\n\nTagihan {$tagihan->nomor_tagihan} dikembalikan untuk revisi:\n{$ringkasan}\n\nSilakan perbaiki dan ajukan ulang melalui:\n{$url}");
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Gagal kirim WA pengembalian tagihan.', [
                        'tagihan_id' => $tagihan->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /** Pembuat tagihan (fallback ke role pembuat sesuai tipe tagihan). */
    private function creatorRecipients(Tagihan $tagihan)
    {
        if ($tagihan->creator) {
            return collect([$tagihan->creator]);
        }

        $role = match ($tagihan->tipe_tagihan) {
            'KONTRAK' => 'Pejabat Pengadaan',
            'PERJALDIN' => 'Operator Perjaldin',
            default => 'Operator BLU',
        };

        try {
            return User::role($role)->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    /** URL halaman detail tagihan milik pembuat sesuai tipe. */
    private function tagihanDetailUrl(Tagihan $tagihan): ?string
    {
        try {
            return match ($tagihan->tipe_tagihan) {
                'KONTRAK' => route('tagihan.kontrak.show', $tagihan->id),
                'HONORARIUM' => route('honorarium.show', $tagihan->id),
                'PERJALDIN' => route('perjaldins.show', $tagihan->id),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    /** Notifikasi WA best-effort ke verifikator step pertama setelah commit. */
    private function notifyAfterCommit(?WorkflowInstance $instance): void
    {
        if (! $instance) {
            return;
        }

        DB::afterCommit(function () use ($instance) {
            $fresh = $instance->fresh();
            if ($fresh) {
                app(WorkflowWaNotifier::class)->notifyPendingApprovals($fresh);
            }
        });
    }

    private function missingVerifierColumns(Tagihan $tagihan): array
    {
        $labels = [
            'ppk_user_id' => 'PPK',
            'ppspm_user_id' => 'PPSPM',
            'bendahara_penerimaan_user_id' => 'Bendahara Penerimaan',
            'bendahara_pengeluaran_user_id' => 'Bendahara Pengeluaran',
        ];

        $missing = [];
        foreach ($labels as $column => $label) {
            if (empty($tagihan->{$column})) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Validasi sisa pagu per item DIPA (lock baris untuk hindari race).
     */
    private function assertSisaPaguCukup(Tagihan $tagihan): void
    {
        if ($tagihan->tipe_tagihan === 'PERJALDIN') {
            $perItem = $tagihan->komponenPerjaldin()
                ->where('total_nominal', '>', 0)
                ->get()
                ->groupBy('dipa_revision_item_id')
                ->map(fn ($group) => (float) $group->sum('total_nominal'));
        } else {
            $perItem = collect([$tagihan->dipa_revision_item_id => (float) $tagihan->total_netto]);
        }

        foreach ($perItem as $itemId => $totalNominal) {
            $item = DetailDipa::whereKey($itemId)
                ->where('status_aktif', true)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw new \RuntimeException('Item DIPA/COA yang dipilih tidak valid atau tidak lagi aktif.');
            }

            $sisaPagu = (float) $item->sisa_pagu;
            if ($totalNominal > $sisaPagu) {
                throw new \RuntimeException(sprintf(
                    'Nominal Rp %s melebihi sisa pagu COA %s (sisa: Rp %s). Pilih COA lain atau revisi anggaran.',
                    number_format($totalNominal, 0, ',', '.'),
                    $item->coa?->kode_mak_lengkap ?? '-',
                    number_format($sisaPagu, 0, ',', '.')
                ));
            }
        }
    }

    private function assertSubmittable($document, string $label): void
    {
        if (! $document) {
            throw new \RuntimeException("Draft {$label} belum tersedia pada rantai dokumen tagihan ini.");
        }

        $submittable = ['DRAFT', 'Revisi', 'REVISI', 'REVISI_PPK', 'REVISI_KASUBBAG', 'DITOLAK_PPK', 'DITOLAK_KASUBBAG'];
        if (! in_array($document->status, $submittable, true)) {
            throw new \RuntimeException("{$label} tidak dalam status draft/revisi sehingga tidak dapat diajukan (status: {$document->status}).");
        }
    }

    private function log(Tagihan $tagihan, ?User $actor, string $aksi, string $catatan, ?string $statusLama = null, ?string $statusBaru = null): void
    {
        LogStatusDokumen::create([
            'dokumen_type' => Tagihan::class,
            'dokumen_id' => $tagihan->id,
            'user_id' => $actor?->id,
            'role_saat_itu' => $actor?->getRoleNames()->first() ?? 'SYSTEM',
            'status_sebelumnya' => $statusLama ?? $tagihan->getOriginal('status'),
            'status_baru' => $statusBaru ?? $tagihan->status,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);
    }

    private function logDokumen($document, ?User $actor, string $aksi, string $catatan, ?string $statusLama = null, ?string $statusBaru = null): void
    {
        LogStatusDokumen::create([
            'dokumen_type' => get_class($document),
            'dokumen_id' => $document->getKey(),
            'user_id' => $actor?->id,
            'role_saat_itu' => $actor?->getRoleNames()->first() ?? 'SYSTEM',
            'status_sebelumnya' => $statusLama ?? $document->getOriginal('status'),
            'status_baru' => $statusBaru ?? $document->status,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);
    }
}
