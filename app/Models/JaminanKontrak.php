<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JaminanKontrak extends Model
{
    protected $table = 'jaminan_kontrak';
    protected $guarded = ['id'];

    public function kontrak()
    {
        return $this->belongsTo(KontrakPengadaan::class, 'kontrak_pengadaan_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }
}
