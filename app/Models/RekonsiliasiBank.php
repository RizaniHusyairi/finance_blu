<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekonsiliasiBank extends Model
{
    protected $table = 'rekonsiliasi_bank';
    protected $guarded = ['id'];

    protected $casts = [
        'nominal_mutasi' => 'decimal:2',
        'nominal_sistem' => 'decimal:2',
        'selisih' => 'decimal:2',
        'direkonsiliasi_pada' => 'datetime',
    ];

    public function detailMutasiBank()
    {
        return $this->belongsTo(DetailMutasiBank::class, 'detail_mutasi_bank_id');
    }

    public function bku()
    {
        return $this->belongsTo(BukuKasUmum::class, 'bku_id');
    }

    public function transaksiPenerimaan()
    {
        return $this->belongsTo(TransaksiPenerimaan::class, 'transaksi_penerimaan_id');
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function direkonsiliasiOleh()
    {
        return $this->belongsTo(User::class, 'direkonsiliasi_oleh');
    }

    public function logs()
    {
        return $this->hasMany(RekonsiliasiBankLog::class, 'rekonsiliasi_bank_id');
    }
}
