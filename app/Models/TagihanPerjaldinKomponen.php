<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanPerjaldinKomponen extends Model
{
    protected $table = 'tagihan_perjaldin_komponen';
    protected $guarded = ['id'];

    protected $casts = [
        'total_nominal' => 'decimal:2',
        'jumlah_peserta' => 'integer',
    ];

    // ── Status constants ─────────────────────────────────────────────
    public const STATUS_MENUNGGU_COA = 'MENUNGGU_COA';
    public const STATUS_SIAP_BUAT_SPP = 'SIAP_BUAT_SPP';
    public const STATUS_SPP_DRAFT = 'SPP_DRAFT';
    public const STATUS_PENDING_PPK = 'PENDING_PPK';
    public const STATUS_PENDING_KASUBBAG = 'PENDING_KASUBBAG';
    public const STATUS_REVISI_PPK = 'REVISI_PPK';
    public const STATUS_REVISI_KASUBBAG = 'REVISI_KASUBBAG';
    public const STATUS_DITOLAK_PPK = 'DITOLAK_PPK';
    public const STATUS_DITOLAK_KASUBBAG = 'DITOLAK_KASUBBAG';
    public const STATUS_DISETUJUI_SPP = 'DISETUJUI_SPP';
    public const STATUS_LANJUT_SPM = 'LANJUT_SPM';
    public const STATUS_SELESAI = 'SELESAI'; // Untuk NPI/SP2D

    // ── Relations ────────────────────────────────────────────────────

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function dipaRevisionItem()
    {
        return $this->belongsTo(DetailDipa::class, 'dipa_revision_item_id');
    }

    public function dokumenSpp()
    {
        return $this->hasOne(DokumenSpp::class, 'tagihan_perjaldin_komponen_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * Apakah komponen ini sudah memiliki dokumen turunan (SPP/SPM/NPI/SP2D).
     */
    public function hasDokumenTurunan(): bool
    {
        return $this->dokumenSpp()->exists();
    }

    /**
     * Sinkronisasi status_proses berdasarkan keberadaan dokumen turunan.
     * Panggil setelah ada perubahan di rantai dokumen.
     */
    public function syncStatusFromDocuments(): string
    {
        $spp = $this->dokumenSpp()->with('spm.npi.sp2d')->first();

        if (!$spp) {
            $status = $this->dipa_revision_item_id
                ? self::STATUS_SIAP_BUAT_SPP
                : self::STATUS_MENUNGGU_COA;
        } else {
            // Evaluasi SPM/NPI/SP2D
            if ($spp->spm?->npi?->sp2d) {
                $status = self::STATUS_SELESAI;
            } elseif ($spp->spm?->npi) {
                $status = self::STATUS_LANJUT_SPM;
            } elseif ($spp->spm) {
                $status = self::STATUS_LANJUT_SPM;
            } else {
                // Bergantung pada delegasi SppPerjaldinWorkflowService yang
                // mengembalikan status DRAFT, PENDING_PPK, dll
                $statusMap = [
                    'DRAFT' => self::STATUS_SPP_DRAFT,
                    'PENDING_PPK' => self::STATUS_PENDING_PPK,
                    'PENDING_KASUBBAG' => self::STATUS_PENDING_KASUBBAG,
                    'REVISI_PPK' => self::STATUS_REVISI_PPK,
                    'REVISI_KASUBBAG' => self::STATUS_REVISI_KASUBBAG,
                    'DITOLAK_PPK' => self::STATUS_DITOLAK_PPK,
                    'DITOLAK_KASUBBAG' => self::STATUS_DITOLAK_KASUBBAG,
                    'DISETUJUI_SPP' => self::STATUS_DISETUJUI_SPP,
                ];

                $status = $statusMap[$spp->status] ?? self::STATUS_SPP_DRAFT;
            }
        }

        if ($this->status_proses !== $status) {
            $this->update(['status_proses' => $status]);
        }

        return $status;
    }

    /**
     * Label badge warna untuk status proses.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status_proses) {
            self::STATUS_MENUNGGU_COA => 'bg-secondary',
            self::STATUS_SIAP_BUAT_SPP => 'bg-info text-dark',
            self::STATUS_SPP_DRAFT => 'bg-secondary',
            self::STATUS_PENDING_PPK, self::STATUS_PENDING_KASUBBAG => 'bg-primary',
            self::STATUS_REVISI_PPK, self::STATUS_REVISI_KASUBBAG => 'bg-warning text-dark',
            self::STATUS_DITOLAK_PPK, self::STATUS_DITOLAK_KASUBBAG => 'bg-danger',
            self::STATUS_DISETUJUI_SPP => 'bg-success',
            self::STATUS_LANJUT_SPM => 'bg-indigo',
            self::STATUS_SELESAI => 'bg-success',
            default => 'bg-secondary',
        };
    }

    /**
     * Label readable untuk status proses.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_proses) {
            self::STATUS_MENUNGGU_COA => 'Menunggu COA',
            self::STATUS_SIAP_BUAT_SPP => 'Siap Buat SPP',
            self::STATUS_SPP_DRAFT => 'SPP Draft',
            self::STATUS_PENDING_PPK => 'Menunggu PPK',
            self::STATUS_PENDING_KASUBBAG => 'Menunggu Kasubbag',
            self::STATUS_REVISI_PPK => 'Revisi PPK',
            self::STATUS_REVISI_KASUBBAG => 'Revisi Kasubbag',
            self::STATUS_DITOLAK_PPK => 'Ditolak PPK',
            self::STATUS_DITOLAK_KASUBBAG => 'Ditolak Kasubbag',
            self::STATUS_DISETUJUI_SPP => 'Disetujui SPP',
            self::STATUS_LANJUT_SPM => 'Lanjut SPM',
            self::STATUS_SELESAI => 'Selesai',
            default => $this->status_proses,
        };
    }
}
