<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraJasaPjp2u extends Model
{
    use SoftDeletes;

    protected $table = 'mitra_jasa_pjp2u';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'status_aktif' => 'boolean',
    ];

    public function mitraJasa()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function kontrakMitraJasa()
    {
        return $this->belongsTo(KontrakMitraJasa::class, 'kontrak_mitra_jasa_id');
    }

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }
}
