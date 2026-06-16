<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogPerubahanTarifPjp2u extends Model
{
    protected $table = 'log_perubahan_tarif_pjp2u';

    protected $guarded = ['id'];

    protected $casts = [
        'tarif_lama' => 'decimal:2',
        'tarif_baru' => 'decimal:2',
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public const TIPE_REVISI_RESMI = 'revisi_resmi';
    public const TIPE_DISKON = 'diskon';
    public const TIPE_KOREKSI = 'koreksi';

    public const TIPE_LABEL = [
        self::TIPE_REVISI_RESMI => 'Revisi Resmi',
        self::TIPE_DISKON => 'Diskon / Penyesuaian',
        self::TIPE_KOREKSI => 'Koreksi',
    ];

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSelisihAttribute(): float
    {
        return (float) $this->tarif_baru - (float) $this->tarif_lama;
    }

    public function getPersentaseSelisihAttribute(): ?float
    {
        $lama = (float) $this->tarif_lama;
        if ($lama <= 0) {
            return null;
        }

        return (((float) $this->tarif_baru - $lama) / $lama) * 100;
    }

    public function getTipeLabelAttribute(): string
    {
        return self::TIPE_LABEL[$this->tipe_perubahan] ?? ucfirst($this->tipe_perubahan);
    }
}
