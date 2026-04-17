<?php

namespace App\Models;

class Budget extends DetailDipa
{
    protected $table = 'dipa_revision_items';

    protected $fillable = [
        'dipa_revision_id',
        'coa_id',
        'nilai_pagu',
        'status_aktif',
        'coa',
        'description',
        'initial_budget',
        'realized_budget',
        'remaining_budget',
        'year',
    ];

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

    protected static function booted(): void
    {
        static::creating(function (Budget $budget) {
            $budget->prepareLegacyAttributesForSave();
        });

        static::created(function (Budget $budget) {
            $budget->refreshRevisionTotalPagu();
        });
    }

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

    private function prepareLegacyAttributesForSave(): void
    {
        $attributes = $this->getAttributes();
        $year = (int) ($attributes['year'] ?? now()->year);
        $initialBudget = $attributes['initial_budget'] ?? $attributes['nilai_pagu'] ?? 0;
        $coaCode = $attributes['coa'] ?? null;
        $description = $attributes['description'] ?? 'Pagu anggaran';

        if (empty($this->attributes['coa_id']) && $coaCode) {
            $coa = MasterCoa::firstOrCreate(
                ['kode_mak_lengkap' => $coaCode],
                [
                    'kd_akun' => substr((string) $coaCode, 0, 6),
                    'nama_akun' => $description,
                    'status_aktif' => true,
                ]
            );

            if ($description && $coa->nama_akun !== $description) {
                $coa->forceFill(['nama_akun' => $description])->save();
            }

            $this->attributes['coa_id'] = $coa->id;
        }

        if (empty($this->attributes['dipa_revision_id'])) {
            $masterDipa = MasterDipa::firstOrCreate(
                ['nomor_dipa' => 'LEGACY-' . $year],
                [
                    'tahun_anggaran' => $year,
                    'tanggal_disahkan' => now()->toDateString(),
                    'revisi_aktif_ke' => 0,
                    'status_aktif' => true,
                ]
            );

            $revision = RiwayatRevisiDipa::firstOrCreate(
                [
                    'master_dipa_id' => $masterDipa->id,
                    'nomor_revisi' => 0,
                ],
                [
                    'tanggal_revisi' => now()->toDateString(),
                    'total_pagu' => $initialBudget,
                    'keterangan' => 'Data kompatibilitas budget lama',
                    'is_active' => true,
                ]
            );

            $this->attributes['dipa_revision_id'] = $revision->id;
        }

        $this->attributes['nilai_pagu'] = $initialBudget;
        $this->attributes['status_aktif'] = $attributes['status_aktif'] ?? true;

        foreach (['coa', 'description', 'initial_budget', 'realized_budget', 'remaining_budget', 'year'] as $legacyAttribute) {
            unset($this->attributes[$legacyAttribute]);
        }
    }

    private function refreshRevisionTotalPagu(): void
    {
        if (! $this->dipa_revision_id) {
            return;
        }

        RiwayatRevisiDipa::whereKey($this->dipa_revision_id)->update([
            'total_pagu' => DetailDipa::where('dipa_revision_id', $this->dipa_revision_id)->sum('nilai_pagu'),
        ]);
    }
}
