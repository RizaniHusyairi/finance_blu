<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KontrakTermin extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_termin';
    protected $guarded = ['id'];

    public function detailKontrak()
    {
        return $this->hasOne(DetailKontrak::class, 'kontrak_termin_id');
    }

    public function kontrak()
    {
        return $this->belongsTo(KontrakPengadaan::class, 'kontrak_pengadaan_id');
    }
}
