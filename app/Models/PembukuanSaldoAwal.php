<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Saldo awal per (rekening, kode_buku, peran) yang menjadi titik mulai saldo
 * berjalan pada [[App\Models\BukuKasUmum]]::recalculateRunningBalance().
 */
class PembukuanSaldoAwal extends Model
{
    protected $table = 'pembukuan_saldo_awal';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_berlaku' => 'date',
        'nominal' => 'decimal:2',
        'kode_buku' => 'integer',
    ];

    public function rekeningBank()
    {
        return $this->belongsTo(RekeningBank::class, 'rekening_bank_id');
    }
}
