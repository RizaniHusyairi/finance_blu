<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenNpi extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_npi';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_npi' => 'date',
    ];

    public function spm()
    {
        return $this->belongsTo(DokumenSpm::class, 'spm_id');
    }
}
