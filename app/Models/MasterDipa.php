<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDipa extends Model
{
    protected $table = 'master_dipas';

    protected $fillable = [
        'nomor_dipa',
        'tahun_anggaran',
        'total_pagu',
        'revisi_ke',
        'tanggal_disahkan',
    ];

    protected $casts = [
        'tanggal_disahkan' => 'date',
        'total_pagu'       => 'decimal:2',
    ];

    public function detailDipas()
    {
        return $this->hasMany(DetailDipa::class, 'dipa_id');
    }

    public function riwayatRevisi()
    {
        return $this->hasMany(RiwayatRevisiDipa::class, 'master_dipa_id');
    }
}
