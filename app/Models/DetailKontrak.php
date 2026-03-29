<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKontrak extends Model
{
    protected $table = 'detail_kontrak';
    protected $guarded = ['id'];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function kontrakTermin()
    {
        return $this->belongsTo(KontrakTermin::class, 'kontrak_termin_id');
    }
}
