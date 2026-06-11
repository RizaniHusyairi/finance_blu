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
 * Setelah tagihan disetujui 6 verifikator, COA dipilih Operator BLU, dan
 * Standing Instruction disetujui KPA, service ini men-generate draft
 * SPP+SPM+NPI+SP2D sekaligus. SPP/SPM/NPI diajukan & diverifikasi paralel
 * (tanpa saling menunggu); setelah ketiganya disetujui, Bendahara Pengeluaran
 * mengunggah bukti transfer yang sekaligus mengajukan SP2D ke PPK (1 step).
 */
class DokumenChainService
{
    /** Status tagihan yang menandakan verifikasi 6-verifikator sudah selesai. */
    public const TAGIHAN_READY_STATUSES = ['READY_FOR_SPP', 'DISETUJUI_KONTRAK', 'DISETUJUI_PERJALDIN', 'DISETUJUI', 'PROSES_SPP'];

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

        return WorkflowInstance::where('workflowable_type', get_class($document))
            ->where('workflowable_id', $document->getKey())
            ->latest('id')
            ->first();
    }

    public function isDocumentApproved($document): bool
    {
        if (! $document) {
            return false;
        }

        return WorkflowInstance::where('workflowable_type', get_class($document))
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
    // Pengajuan paralel SPP / SPM / NPI
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
        $npi = $this->chainSpp($tagihan)?->spm?->npi;
        $this->assertSubmittable($npi, 'NPI');

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
            $documents = array_filter([
                $spp->spm?->npi?->sp2d,
                $spp->spm?->npi,
                $spp->spm,
                $spp,
            ]);

            foreach ($documents as $doc) {
                WorkflowInstance::where('workflowable_type', get_class($doc))
                    ->where('workflowable_id', $doc->getKey())
                    ->whereIn('status', ['IN_PROGRESS', 'DRAFT', 'REVISION'])
                    ->update(['status' => 'REJECTED']);
                $doc->delete();
            }

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
    // Helpers
    // ─────────────────────────────────────────────────────────────────

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
