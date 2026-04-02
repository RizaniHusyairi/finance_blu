<?php

namespace App\Models;

class Budget extends DetailDipa
{
    protected $table = 'dipa_revision_items';

    protected $appends = [
        'coa',
        'description',
        'initial_budget',
        'realized_budget',
        'remaining_budget',
        'year',
        'status_pagu',
        'program_code',
        'activity_code',
        'output_code',
        'suboutput_code',
        'component_code',
        'subcomponent_code',
        'account_code',
        'item_code',
        'catatan',
    ];

    public function realisasi()
    {
        return $this->hasMany(RealisasiAnggaran::class, 'dipa_revision_item_id');
    }

    public function getCoaAttribute()
    {
        return $this->coaRelation()?->kode_mak_lengkap;
    }

    public function getDescriptionAttribute()
    {
        return $this->coaRelation()?->nama_akun;
    }

    public function getInitialBudgetAttribute()
    {
        return (float) $this->nilai_pagu;
    }

    public function getRealizedBudgetAttribute()
    {
        if ($this->relationLoaded('realisasi')) {
            return (float) $this->realisasi->sum('nominal_cair');
        }

        return (float) $this->realisasi()->sum('nominal_cair');
    }

    public function getRemainingBudgetAttribute()
    {
        return (float) $this->initial_budget - (float) $this->realized_budget;
    }

    public function getYearAttribute()
    {
        return $this->masterDipa()?->tahun_anggaran;
    }

    public function getStatusPaguAttribute()
    {
        return $this->masterDipa()?->status_aktif ? 'Aktif' : 'Nonaktif';
    }

    public function getProgramCodeAttribute()
    {
        return $this->coaRelation()?->kd_program;
    }

    public function getActivityCodeAttribute()
    {
        return $this->coaRelation()?->kd_giat;
    }

    public function getOutputCodeAttribute()
    {
        return $this->coaRelation()?->kd_output;
    }

    public function getSuboutputCodeAttribute()
    {
        return $this->coaRelation()?->kd_suboutput;
    }

    public function getComponentCodeAttribute()
    {
        return $this->coaRelation()?->kd_komponen;
    }

    public function getSubcomponentCodeAttribute()
    {
        return $this->coaRelation()?->kd_subkomponen;
    }

    public function getAccountCodeAttribute()
    {
        return $this->coaRelation()?->kd_akun;
    }

    public function getItemCodeAttribute()
    {
        return $this->coaRelation()?->kd_item;
    }

    public function getCatatanAttribute()
    {
        return $this->dipaRevision?->keterangan;
    }

    protected function masterDipa()
    {
        return $this->dipaRevision?->masterDipa;
    }

    protected function coaRelation()
    {
        if ($this->relationLoaded('coa')) {
            return $this->getRelation('coa');
        }

        return $this->coa()->first();
    }
}
