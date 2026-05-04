<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TagihanJasa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tagihan_jasas';
    protected $guarded = ['id'];

    public function mitra()
    {
        return $this->belongsTo(MasterPihak::class, 'mitra_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(TagihanJasaDetail::class, 'tagihan_jasa_id');
    }

    public function workflowInstances()
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    public function workflowInstance()
    {
        return $this->morphOne(WorkflowInstance::class, 'workflowable')->latestOfMany();
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }
}
