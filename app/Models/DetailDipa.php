<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailDipa extends Model
{
    use SoftDeletes;

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
}
