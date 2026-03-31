<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenSpm extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_spm';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_spm' => 'date',
    ];

    public function spp()
    {
        return $this->belongsTo(DokumenSpp::class, 'spp_id');
    }
}
