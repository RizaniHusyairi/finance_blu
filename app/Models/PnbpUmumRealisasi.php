<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PnbpUmumRealisasi extends Model
{
    protected $table = 'pnbp_umum_realisasis';
    protected $guarded = ['id'];

    protected $casts = [
        'nilai' => 'decimal:2',
        'tahun' => 'integer',
        'bulan' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(PnbpUmumItem::class, 'pnbp_umum_item_id');
    }
}
