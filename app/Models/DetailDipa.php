<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailDipa extends Model
{
    protected $table = 'dipa_revision_items';

    protected $fillable = [
        'dipa_revision_id',
        'coa_id',
        'nilai_pagu',
        'status_aktif',
    ];

    protected $casts = [
        'nilai_pagu' => 'decimal:2',
        'status_aktif' => 'boolean',
    ];

    public function dipaRevision()
    {
        return $this->belongsTo(RiwayatRevisiDipa::class, 'dipa_revision_id');
    }

    public function dipa()
    {
        return $this->hasOneThrough(MasterDipa::class, RiwayatRevisiDipa::class, 'id', 'id', 'dipa_revision_id', 'master_dipa_id');
    }

    public function coa()
    {
        return $this->belongsTo(MasterCoa::class, 'coa_id');
    }

    public function realisasiAnggarans()
    {
        return $this->hasMany(RealisasiAnggaran::class, 'dipa_revision_item_id')->where('status', 'TERCATAT');
    }

    public function getTotalRealisasiAttribute()
    {
        // Calculate from relation, ensuring to use sum if loaded, else query DB
        return $this->relationLoaded('realisasiAnggarans') 
            ? $this->realisasiAnggarans->sum('nominal_cair')
            : $this->realisasiAnggarans()->sum('nominal_cair');
    }

    public function getSisaPaguAttribute()
    {
        return $this->nilai_pagu - $this->total_realisasi;
    }
}
