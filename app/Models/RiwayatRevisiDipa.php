<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiwayatRevisiDipa extends Model
{
    use SoftDeletes;

    protected $table = 'dipa_revisions';

    protected $fillable = [
        'master_dipa_id',
        'nomor_revisi',
        'tanggal_revisi',
        'total_pagu',
        'file_dokumen_dipa',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'tanggal_revisi' => 'date',
        'total_pagu' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function masterDipa()
    {
        return $this->belongsTo(MasterDipa::class, 'master_dipa_id');
    }

    public function items()
    {
        return $this->hasMany(DetailDipa::class, 'dipa_revision_id');
    }

    public function getPaguBaruAttribute()
    {
        return $this->total_pagu;
    }

    public function getPaguSebelumnyaAttribute()
    {
        return 0;
    }
}
