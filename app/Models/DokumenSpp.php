<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenSpp extends Model
{
    use SoftDeletes;

    public const STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE = 'STANDING_INSTRUCTION_FINAL_TTD';

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

    public function tagihanPerjaldinKomponen()
    {
        return $this->belongsTo(TagihanPerjaldinKomponen::class, 'tagihan_perjaldin_komponen_id');
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

    /**
     * Get the latest workflow instance (singular) — used by SppPerjaldinWorkflowService.
     */
    public function workflowInstance()
    {
        return $this->morphOne(WorkflowInstance::class, 'workflowable')->latestOfMany();
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function standingInstruction()
    {
        return $this->hasOne(StandingInstruction::class, 'dokumen_spp_id');
    }

    public function signedStandingInstructionArsip()
    {
        return $this->morphOne(ArsipDokumen::class, 'documentable')
            ->where('jenis_dokumen', self::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE)
            ->where('is_active', true)
            ->latestOfMany('uploaded_at');
    }

    public function hasFinalSignedStandingInstruction(): bool
    {
        $standingInstruction = $this->relationLoaded('standingInstruction')
            ? $this->standingInstruction
            : $this->standingInstruction()->first();

        if (!$standingInstruction || $standingInstruction->status !== 'FINAL') {
            return false;
        }

        return $this->arsipDokumen()
            ->where('jenis_dokumen', self::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE)
            ->where('is_active', true)
            ->exists();
    }
}
