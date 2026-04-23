<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaporanPengesahanBlu extends Model
{
    use SoftDeletes;

    protected $table = 'laporan_pengesahan_blu';
    protected $guarded = ['id'];

    protected $casts = [
        'periode_bulan' => 'integer',
        'tahun' => 'integer',
        'total_penerimaan' => 'decimal:2',
        'total_pengeluaran' => 'decimal:2',
        'saldo_akhir_blu' => 'decimal:2',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'disetujui_kpa_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }
}
