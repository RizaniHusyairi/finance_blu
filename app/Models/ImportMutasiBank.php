<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportMutasiBank extends Model
{
    protected $table = 'import_mutasi_bank';
    protected $guarded = ['id'];

    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'uploaded_at' => 'datetime',
    ];

    public function rekeningBank()
    {
        return $this->belongsTo(RekeningBank::class, 'rekening_bank_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function detailMutasiBanks()
    {
        return $this->hasMany(DetailMutasiBank::class, 'import_mutasi_bank_id');
    }
}
