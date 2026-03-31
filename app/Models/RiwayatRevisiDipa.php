<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatRevisiDipa extends Model
{
    protected $table = 'riwayat_revisi_dipa';

    protected $fillable = [
        'master_dipa_id',
        'nomor_revisi',
        'tanggal_revisi',
        'pagu_sebelumnya',
        'pagu_baru',
        'file_dokumen_dipa',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_revisi'  => 'date',
        'pagu_sebelumnya' => 'decimal:2',
        'pagu_baru'       => 'decimal:2',
    ];

    public function masterDipa()
    {
        return $this->belongsTo(MasterDipa::class, 'master_dipa_id');
    }
}
