<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermohonanNonSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'permohonan_non_schedule';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_penerbangan_dari' => 'date',
        'tanggal_penerbangan_sampai' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public const STATUS_DIAJUKAN = 'DIAJUKAN';
    public const STATUS_DISETUJUI = 'DISETUJUI';
    public const STATUS_DITOLAK = 'DITOLAK';

    public const JENIS_KARGO = 'non_schedule_kargo';
    public const JENIS_LAIN = 'non_schedule_lain';

    public const STATUS_LABEL = [
        self::STATUS_DIAJUKAN => 'Diajukan',
        self::STATUS_DISETUJUI => 'Disetujui',
        self::STATUS_DITOLAK => 'Ditolak',
    ];

    public const JENIS_LABEL = [
        self::JENIS_KARGO => 'Non-Schedule Kargo',
        self::JENIS_LAIN => 'Non-Schedule Lainnya',
    ];

    public function mitra()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function tagihan()
    {
        return $this->hasMany(TagihanJasa::class, 'permohonan_non_schedule_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS_LABEL[$this->jenis_penerbangan] ?? $this->jenis_penerbangan;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DISETUJUI => 'bg-success',
            self::STATUS_DITOLAK => 'bg-danger',
            default => 'bg-warning text-dark',
        };
    }
}
