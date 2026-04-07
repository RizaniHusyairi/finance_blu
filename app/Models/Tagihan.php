<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tagihan extends Model
{
    use SoftDeletes;

    protected $table = 'tagihan';
    protected $guarded = ['id'];

    public function pihak()
    {
        return $this->belongsTo(MasterPihak::class, 'pihak_id');
    }

    public function dipa()
    {
        return $this->belongsTo(MasterDipa::class, 'master_dipa_id');
    }

    public function dipaRevisionItem()
    {
        return $this->belongsTo(DetailDipa::class, 'dipa_revision_item_id');
    }

    public function detailKontrak()
    {
        return $this->hasOne(DetailKontrak::class, 'tagihan_id');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function detailPerjaldin()
    {
        return $this->hasMany(DetailPerjaldin::class, 'tagihan_id');
    }

    public function detailHonorarium()
    {
        return $this->hasMany(DetailHonorarium::class, 'tagihan_id');
    }

    public function potonganTagihan()
    {
        return $this->hasMany(PotonganTagihan::class, 'tagihan_id');
    }

    public function potongans()
    {
        return $this->potonganTagihan();
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function spps()
    {
        return $this->hasMany(Spp::class, 'tagihan_id');
    }

    public function workflowInstances()
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    public function getWaktuVerifikasiPpkAttribute()
    {
        $log = $this->relationLoaded('logs')
            ? $this->logs->firstWhere('status_baru', 'READY_FOR_SPP') ?? $this->logs->firstWhere('status_baru', 'DISETUJUI_PPK')
            : $this->logs()->whereIn('status_baru', ['READY_FOR_SPP', 'DISETUJUI_PPK'])->latest()->first();

        return optional($log)->created_at;
    }
}
