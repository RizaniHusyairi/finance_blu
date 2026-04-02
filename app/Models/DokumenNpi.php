<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenNpi extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_npi';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_npi' => 'date',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED_BENPEN = 'SUBMITTED_BENPEN';
    public const STATUS_REJECTED_BENPEN = 'REJECTED_BENPEN';
    public const STATUS_SUBMITTED_PPK = 'SUBMITTED_PPK';
    public const STATUS_REJECTED_PPK = 'REJECTED_PPK';
    public const STATUS_SUBMITTED_KASUBAG = 'SUBMITTED_KASUBAG';
    public const STATUS_REJECTED_KASUBAG = 'REJECTED_KASUBAG';
    public const STATUS_APPROVED_KASUBAG = 'APPROVED_KASUBAG';

    public function spm()
    {
        return $this->belongsTo(DokumenSpm::class, 'spm_id');
    }

    public function bendaharaPenerimaan()
    {
        return $this->belongsTo(User::class, 'bendahara_penerimaan_id');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function sp2d()
    {
        return $this->hasOne(DokumenSp2d::class, 'npi_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function getSpmIdAttribute()
    {
        return $this->spm_id;
    }

    public function getSppIdAttribute()
    {
        return optional($this->spm)->spp_id;
    }

    public function getNomorSpmAttribute()
    {
        return optional(optional($this->spm)->spp)->spm?->nomor_spm ?? optional($this->spm)->nomor_spm;
    }

    public function getNomorSppAttribute()
    {
        return optional(optional($this->spm)->spp)->nomor_spp;
    }

    public function getJumlahUangAttribute()
    {
        return optional(optional($this->spm)->spp)->nominal_spp;
    }

    public function getStatusSppAttribute()
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED_BENPEN => 'Menunggu Verifikasi Bendahara Penerimaan',
            self::STATUS_REJECTED_BENPEN => 'Revisi Bendahara Penerimaan',
            self::STATUS_SUBMITTED_PPK => 'Menunggu Verifikasi PPK NPI',
            self::STATUS_REJECTED_PPK => 'Revisi NPI',
            self::STATUS_SUBMITTED_KASUBAG => 'Menunggu Verifikasi Kasubbag',
            self::STATUS_REJECTED_KASUBAG => 'Revisi Kasubbag',
            self::STATUS_APPROVED_KASUBAG => 'NPI Terbit',
            default => 'Draft NPI',
        };
    }

    public function getCatatanRevisiAttribute()
    {
        return optional(
            $this->logs()
                ->whereIn('status_baru', [
                    self::STATUS_REJECTED_BENPEN,
                    self::STATUS_REJECTED_PPK,
                    self::STATUS_REJECTED_KASUBAG,
                ])
                ->latest()
                ->first()
        )->catatan;
    }
}
