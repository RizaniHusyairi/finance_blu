<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailMutasiBank extends Model
{
    protected $table = 'detail_mutasi_bank';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'debit' => 'decimal:2',
        'kredit' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function importMutasiBank()
    {
        return $this->belongsTo(ImportMutasiBank::class, 'import_mutasi_bank_id');
    }

    public function rekonsiliasiBanks()
    {
        return $this->hasMany(RekonsiliasiBank::class, 'detail_mutasi_bank_id');
    }
}
