<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagihanJasaPaymentProof extends Model
{
    use SoftDeletes;

    public const STATUS_MENUNGGU = 'MENUNGGU_VERIFIKASI';
    public const STATUS_DITERIMA = 'DITERIMA';
    public const STATUS_DITOLAK = 'DITOLAK';
    public const STATUS_PERLU_PERBAIKAN = 'PERLU_PERBAIKAN';

    protected $table = 'tagihan_jasa_payment_proofs';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_bayar' => 'date',
        'nominal_bayar' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function tagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }

    public function mitraJasa()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DITERIMA => 'Diterima',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_PERLU_PERBAIKAN => 'Perlu Perbaikan',
            default => 'Menunggu Verifikasi',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DITERIMA => 'bg-success',
            self::STATUS_DITOLAK => 'bg-danger',
            self::STATUS_PERLU_PERBAIKAN => 'bg-warning text-dark',
            default => 'bg-info text-dark',
        };
    }
}
