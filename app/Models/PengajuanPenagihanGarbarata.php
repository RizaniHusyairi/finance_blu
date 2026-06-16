<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanPenagihanGarbarata extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_penagihan_garbarata';
    protected $guarded = ['id'];
    protected $casts = [
        'periode_tahun' => 'integer',
        'periode_bulan' => 'integer',
        'jumlah_pemakaian' => 'integer',
        'total_rentang' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public const STATUS_DIAJUKAN = 'DIAJUKAN';
    public const STATUS_DISETUJUI = 'DISETUJUI';
    public const STATUS_DITOLAK = 'DITOLAK';

    public const STATUS_LABEL = [
        self::STATUS_DIAJUKAN => 'Perlu Validasi',
        self::STATUS_DISETUJUI => 'Siap Ditagih',
        self::STATUS_DITOLAK => 'Ditolak',
    ];

    public const BULAN_LABEL = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function mitra()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function pemakaian()
    {
        return $this->hasMany(PemakaianGarbarata::class, 'pengajuan_penagihan_garbarata_id');
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
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABEL[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DISETUJUI => 'bg-success',
            self::STATUS_DITOLAK => 'bg-danger',
            default => 'bg-warning text-dark',
        };
    }

    public function getPeriodeLabelAttribute(): string
    {
        $bulan = self::BULAN_LABEL[$this->periode_bulan] ?? sprintf('%02d', $this->periode_bulan);
        return "{$bulan} {$this->periode_tahun}";
    }
}
