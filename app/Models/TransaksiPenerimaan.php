<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiPenerimaan extends Model
{
    use SoftDeletes;

    protected $table = 'transaksi_penerimaan';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_invoice' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'nominal_tagihan' => 'decimal:2',
        'nominal_denda_keterlambatan' => 'decimal:2',
        'total_dibayar' => 'decimal:2',
    ];

    public function mitra()
    {
        return $this->belongsTo(MasterPihak::class, 'mitra_id');
    }

    public function coa()
    {
        return $this->belongsTo(MasterCoa::class, 'coa_id');
    }

    public function bukuKasUmums()
    {
        return $this->hasMany(BukuKasUmum::class, 'referensi_penerimaan_id');
    }
}
