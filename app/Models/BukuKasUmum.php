<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BukuKasUmum extends Model
{
    use SoftDeletes;

    protected $table = 'buku_kas_umum';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'nominal' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
    ];

    public function sumberRekening()
    {
        return $this->belongsTo(RekeningBank::class, 'sumber_rekening_id');
    }

    public function referensiPengeluaran()
    {
        return $this->belongsTo(Tagihan::class, 'referensi_pengeluaran_id');
    }

    public function referensiPenerimaan()
    {
        return $this->belongsTo(TransaksiPenerimaan::class, 'referensi_penerimaan_id');
    }
}
