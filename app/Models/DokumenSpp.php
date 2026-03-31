<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenSpp extends Model
{
    protected $table = 'dokumen_spp';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_spp' => 'date',
    ];
}
