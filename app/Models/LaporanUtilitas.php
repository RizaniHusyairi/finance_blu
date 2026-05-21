<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanUtilitas extends Model
{
    protected $fillable = [
        'mitra_jasa_id',
        'layanan_jasa_id',
        'jenis',
        'tipe_perhitungan',
        'nomor_meter',
        'bulan',
        'tahun',
        'stan_awal',
        'stan_akhir',
        'file_bukti_awal',
        'file_bukti',
        'pemakaian',
        'tarif_per_unit',
        'total_biaya',
        'status',
        'catatan_admin_jasa',
        'tagihan_jasa_id',
        'created_by'
    ];

    protected $casts = [
        'tarif_per_unit' => 'decimal:2',
        'total_biaya' => 'decimal:2',
    ];

    public function mitraJasa()
    {
        return $this->belongsTo(MitraJasa::class);
    }

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class);
    }

    public function tagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
