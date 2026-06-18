<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Baris jurnal transaksi tunggal Bendahara Pengeluaran (sheet Input_Transaksi).
 * Didistribusikan ke BKU + buku pembantu oleh PostingPembukuanService.
 */
class TransaksiPembukuan extends Model
{
    use SoftDeletes;

    protected $table = 'transaksi_pembukuan';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_kotor' => 'decimal:2',
        'unsur_pajak_ppn' => 'decimal:2',
        'unsur_pajak_pph' => 'decimal:2',
    ];

    public function kodeTransaksi()
    {
        return $this->belongsTo(KodeTransaksi::class, 'kode_transaksi', 'kode');
    }

    public function penerima()
    {
        return $this->belongsTo(MasterPihak::class, 'penerima_id');
    }

    public function kegOutputAkun()
    {
        return $this->belongsTo(MasterCoa::class, 'keg_output_akun_id');
    }

    public function rekeningBank()
    {
        return $this->belongsTo(RekeningBank::class, 'rekening_bank_id');
    }

    public function referensi()
    {
        return $this->morphTo();
    }

    public function bukuKasUmums()
    {
        return $this->hasMany(BukuKasUmum::class, 'transaksi_pembukuan_id');
    }
}
