<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterDipa extends Model
{
    use SoftDeletes;

    protected $table = 'master_dipas';

    protected $fillable = [
        'nomor_dipa',
        'tahun_anggaran',
        'tanggal_disahkan',
        'revisi_aktif_ke',
        'status_aktif',
    ];

    protected $casts = [
        'tanggal_disahkan' => 'date',
        'status_aktif' => 'boolean',
    ];

    public function revisions()
    {
        return $this->hasMany(RiwayatRevisiDipa::class, 'master_dipa_id');
    }

    public function riwayatRevisi()
    {
        return $this->revisions();
    }

    public function activeRevision()
    {
        return $this->hasOne(RiwayatRevisiDipa::class, 'master_dipa_id')->where('is_active', true);
    }

    public function detailDipas()
    {
        return $this->hasManyThrough(
            DetailDipa::class,
            RiwayatRevisiDipa::class,
            'master_dipa_id',
            'dipa_revision_id',
            'id',
            'id'
        )->where('dipa_revisions.is_active', true);
    }

    public function getTotalPaguAttribute()
    {
        return (float) optional($this->activeRevision)->total_pagu;
    }
}
