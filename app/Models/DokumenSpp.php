<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenSpp extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_spp';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_spp' => 'date',
        'nominal_spp' => 'decimal:2',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function dipaRevisionItem()
    {
        return $this->belongsTo(DetailDipa::class, 'dipa_revision_item_id');
    }

    public function spm()
    {
        return $this->hasOne(DokumenSpm::class, 'spp_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function ppkVerifikator()
    {
        return $this->belongsTo(User::class, 'ppk_verifikator_id');
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    public function workflowInstances()
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }
}
