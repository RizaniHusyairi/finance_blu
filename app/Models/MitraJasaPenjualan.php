<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraJasaPenjualan extends Model
{
    use SoftDeletes;

    protected $table = 'mitra_jasa_penjualan';
    protected $guarded = ['id'];
    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
        'total_omzet' => 'decimal:2',
        'persentase_konsesi' => 'decimal:4',
        'nilai_konsesi' => 'decimal:2',
        'nilai_minimum_guarantee' => 'decimal:2',
        'nilai_tagihan' => 'decimal:2',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'penerbangan_details' => 'array',
    ];

    public function mitraJasa()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function konsesi()
    {
        return $this->belongsTo(MitraJasaKonsesi::class, 'mitra_jasa_konsesi_id');
    }

    public function kontrakMitraJasa()
    {
        return $this->belongsTo(KontrakMitraJasa::class, 'kontrak_mitra_jasa_id');
    }

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }

    public function tagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }

    public function sourceTagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class, 'source_tagihan_jasa_id');
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(MitraJasaPenjualanDetail::class, 'mitra_jasa_penjualan_id');
    }

    /**
     * Recalculate parent totals from all detail records.
     */
    public function recalculateTotals(): void
    {
        $details = $this->details()->withoutTrashed()->get();

        $this->update([
            'total_omzet' => $details->sum('total_omzet'),
            'total_transaksi' => $details->sum('total_transaksi'),
        ]);

        // Recalculate konsesi values if layanan has percentage
        $layanan = $this->layananJasa;
        if ($layanan) {
            $persentase = (float) ($this->persentase_konsesi ?: $layanan->persentase_konsesi ?: $layanan->tarif_dasar ?: 0);
            $totalOmzet = (float) $this->fresh()->total_omzet;
            $nilaiKonsesi = $totalOmzet * $persentase / 100;
            $nilaiMinGuar = (float) ($this->nilai_minimum_guarantee ?: 0);
            $nilaiTagihan = max($nilaiKonsesi, $nilaiMinGuar);

            $this->update([
                'nilai_konsesi' => $nilaiKonsesi,
                'nilai_tagihan' => $nilaiTagihan,
            ]);
        }
    }

    /* ── Accessor: Verifikasi konsesi setelah bulan pelaporan berakhir, PJP2U harian langsung ── */

    public function getCanBeVerifiedAttribute(): bool
    {
        if ($this->status !== 'diajukan') {
            return false;
        }

        if ($this->is_pjp2u_report) {
            return true;
        }

        return $this->reportingMonthEnded();
    }

    /* ── Accessor: Tagihan konsesi tersedia setelah bulan pelaporan berakhir, PJP2U harian langsung ── */

    public function getCanCreateTagihanAttribute(): bool
    {
        if ($this->status !== 'diverifikasi' || $this->tagihan_jasa_id) {
            return false;
        }

        if ($this->is_pjp2u_report) {
            return true;
        }

        // Patokan: bulan pelaporan sudah berakhir (konsisten dengan can_be_verified),
        // bukan lagi submitted_at + 1 bulan — agar tidak bergantung kapan mitra submit.
        return $this->reportingMonthEnded();
    }

    /* ── Accessor: Tanggal tagihan tersedia ── */

    public function getTagihanAvailableDateAttribute(): ?string
    {
        if ($this->is_pjp2u_report || $this->reportingMonthEnded()) {
            return 'Sekarang';
        }

        if (! $this->tahun || ! $this->bulan) {
            return null;
        }

        // Tersedia mulai awal bulan setelah bulan pelaporan.
        return Carbon::create((int) $this->tahun, (int) $this->bulan, 1)
            ->addMonthNoOverflow()
            ->format('d/m/Y');
    }

    /**
     * Apakah bulan pelaporan sudah berakhir (sudah ganti bulan)?
     * Gerbang waktu bersama untuk verifikasi & pembuatan tagihan konsesi.
     */
    private function reportingMonthEnded(): bool
    {
        $now = now();

        return ($now->year > (int) $this->tahun)
            || ($now->year === (int) $this->tahun && $now->month > (int) $this->bulan);
    }

    public function getIsPjp2uReportAttribute(): bool
    {
        if (is_array($this->penerbangan_details) && count($this->penerbangan_details) > 0) {
            return true;
        }

        $layanan = $this->relationLoaded('layananJasa')
            ? $this->layananJasa
            : $this->layananJasa()->first();

        return $layanan?->isPjp2u() === true;
    }

    /* ── Accessor: Label status deskriptif ── */

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'diverifikasi' => 'Diverifikasi',
            'ditolak' => 'Ditolak',
            'ditagihkan' => 'Ditagihkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'diajukan' => 'warning',
            'diverifikasi' => 'success',
            'ditolak' => 'danger',
            'ditagihkan' => 'primary',
            default => 'secondary',
        };
    }
}
