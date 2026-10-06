<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Termin milik master Kontrak Eksternal.
 * status_termin: LOCKED → READY_TO_BILL → DRAFT → DALAM_PROSES → LUNAS.
 * Termin berikutnya terbuka saat SP2D termin ini dieksekusi.
 */
class KontrakEksternalTermin extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_eksternal_termin';

    protected $guarded = ['id'];

    protected $casts = [
        'persentase' => 'float',
    ];

    public function kontrak()
    {
        return $this->belongsTo(KontrakEksternal::class, 'kontrak_eksternal_id');
    }

    public function detailKontrakEksternal()
    {
        return $this->hasOne(DetailKontrakEksternal::class, 'kontrak_eksternal_termin_id');
    }
}
