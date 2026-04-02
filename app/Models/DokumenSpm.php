<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenSpm extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_spm';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_spm' => 'date',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED_PPSPM = 'SUBMITTED_PPSPM';
    public const STATUS_REJECTED_PPSPM = 'REJECTED_PPSPM';
    public const STATUS_SUBMITTED_KASUBAG = 'SUBMITTED_KASUBAG';
    public const STATUS_REJECTED_KASUBAG = 'REJECTED_KASUBAG';
    public const STATUS_APPROVED_KASUBAG = 'APPROVED_KASUBAG';

    public function spp()
    {
        return $this->belongsTo(DokumenSpp::class, 'spp_id');
    }

    public function ppspm()
    {
        return $this->belongsTo(User::class, 'ppspm_id');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function npi()
    {
        return $this->hasOne(DokumenNpi::class, 'spm_id');
    }

    public function getSppIdAttribute()
    {
        return $this->spp_id;
    }

    public function getTagihanAttribute()
    {
        return optional($this->spp)->tagihan;
    }

    public function getNomorSppAttribute()
    {
        return optional($this->spp)->nomor_spp;
    }

    public function getJumlahUangAttribute()
    {
        return optional($this->spp)->nominal_spp;
    }

    public function getStatusSppAttribute()
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED_PPSPM => 'Menunggu Verifikasi SPM',
            self::STATUS_REJECTED_PPSPM => 'Revisi SPM',
            self::STATUS_SUBMITTED_KASUBAG => 'Menunggu Verifikasi Kasubag',
            self::STATUS_REJECTED_KASUBAG => 'Revisi Kasubbag',
            self::STATUS_APPROVED_KASUBAG => 'SPM Terbit',
            default => 'Draft SPM',
        };
    }

    public function getCatatanRevisiAttribute()
    {
        return optional(
            $this->logs()
                ->whereIn('status_baru', [self::STATUS_REJECTED_PPSPM, self::STATUS_REJECTED_KASUBAG])
                ->latest()
                ->first()
        )->catatan;
    }
}
