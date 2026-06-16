<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemakaianGarbarata extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pemakaian_garbarata';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal' => 'date',
        'docking_at' => 'datetime',
        'undocking_at' => 'datetime',
        'durasi_menit' => 'integer',
        'jumlah_rentang' => 'integer',
        'nomor_avio' => 'integer',
        'bobot_ton' => 'decimal:2',
        'tarif_garbarata' => 'decimal:2',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SIAP = 'SIAP_DITAGIH';
    public const STATUS_DIAJUKAN = 'DIAJUKAN';
    public const STATUS_TERTAGIH = 'TERTAGIH';

    public const STATUS_LABEL = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SIAP => 'Siap Ditagih',
        self::STATUS_DIAJUKAN => 'Dikunci Rekap',
        self::STATUS_TERTAGIH => 'Tertagih',
    ];

    public function mitra()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function layanan()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }

    public function tagihan()
    {
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }

    public function permohonan()
    {
        return $this->belongsTo(PermohonanNonSchedule::class, 'permohonan_non_schedule_id');
    }

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanPenagihanGarbarata::class, 'pengajuan_penagihan_garbarata_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_TERTAGIH => 'bg-success',
            self::STATUS_DIAJUKAN => 'bg-warning text-dark',
            self::STATUS_SIAP => 'bg-primary',
            default => 'bg-secondary',
        };
    }

    public function getTotalGarbarataAttribute(): float
    {
        return (float) ($this->tarif_garbarata ?? 0) * (int) ($this->jumlah_rentang ?? 0);
    }

    public static function computeDuration(?string $docking, ?string $undocking): int
    {
        if (! $docking || ! $undocking) {
            return 0;
        }
        try {
            $d = \Carbon\Carbon::parse($docking);
            $u = \Carbon\Carbon::parse($undocking);
            return max(0, $d->diffInMinutes($u, false));
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function computeRentang(int $durasiMenit): int
    {
        return $durasiMenit > 0 ? max(1, (int) ceil($durasiMenit / 120)) : 0;
    }
}
