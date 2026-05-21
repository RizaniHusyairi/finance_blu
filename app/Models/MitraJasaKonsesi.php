<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraJasaKonsesi extends Model
{
    use SoftDeletes;

    protected $table = 'mitra_jasa_konsesi';
    protected $guarded = ['id'];
    protected $casts = [
        'persentase_konsesi' => 'decimal:4',
        'nilai_tetap' => 'decimal:2',
        'nilai_minimum_guarantee' => 'decimal:2',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'status_aktif' => 'boolean',
    ];

    public function mitraJasa()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function kontrakMitraJasa()
    {
        return $this->belongsTo(KontrakMitraJasa::class, 'kontrak_mitra_jasa_id');
    }

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }

    public function penjualan()
    {
        return $this->hasMany(MitraJasaPenjualan::class, 'mitra_jasa_konsesi_id');
    }

    public function getLabelJenisKonsesiAttribute(): string
    {
        return match ($this->jenis_konsesi) {
            'persen_omzet' => 'Persentase Omzet',
            'nilai_tetap' => 'Nilai Tetap',
            'minimum_guarantee' => 'Minimum Guarantee',
            'kombinasi' => 'Kombinasi',
            default => str_replace('_', ' ', (string) $this->jenis_konsesi),
        };
    }
}
