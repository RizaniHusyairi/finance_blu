<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenSp2d extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_sp2d';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_sp2d' => 'date',
    ];

    public function npi()
    {
        return $this->belongsTo(DokumenNpi::class, 'npi_id');
    }
}
