<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontrakAddendum extends Model
{
    protected $table = 'kontrak_addendum';
    protected $guarded = ['id'];

    public function kontrakUtama()
    {
        return $this->belongsTo(KontrakPengadaan::class, 'kontrak_pengadaan_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }
}
