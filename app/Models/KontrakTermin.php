<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontrakTermin extends Model
{
    protected $table = 'kontrak_termin';
    protected $guarded = ['id'];

    public function detailKontrak()
    {
        return $this->hasOne(DetailKontrak::class, 'kontrak_termin_id');
    }
}
