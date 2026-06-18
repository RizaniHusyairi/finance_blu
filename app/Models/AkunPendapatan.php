<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master akun pendapatan BLU (sheet `Akun Pendapatan` File 2).
 *
 * Identitas baku: (kode_akun, kode_jenis) — mis. (424919, 921) "Tagihan Listrik".
 * `kode_gabungan` adalah label akun yang tampil di BKU Penerimaan (mis. 424919.92).
 */
class AkunPendapatan extends Model
{
    protected $table = 'akun_pendapatan';
    protected $guarded = ['id'];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    public function coa()
    {
        return $this->belongsTo(MasterCoa::class, 'coa_id');
    }

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }

    public function labelLengkap(): string
    {
        return trim($this->uraian_akun . ($this->uraian_jenis ? ' - ' . $this->uraian_jenis : ''));
    }
}
