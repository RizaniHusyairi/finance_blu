<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekeningBank extends Model
{
    protected $table = 'rekening_bank';

    protected $fillable = [
        'pemilik_type',
        'pemilik_id',
        'nama_bank',
        'nomor_rekening',
        'nama_rekening',
    ];

    public function pemilik()
    {
        return $this->morphTo();
    }
}
