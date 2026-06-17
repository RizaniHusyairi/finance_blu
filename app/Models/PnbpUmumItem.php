<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PnbpUmumItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function realisasis()
    {
        return $this->hasMany(PnbpUmumRealisasi::class, 'pnbp_umum_item_id');
    }
}
