<?php

namespace App\Models;

class Transaction extends Tagihan
{
    protected $fillable = [
        'nomor_tagihan',
        'tipe_tagihan',
        'master_dipa_id',
        'dipa_revision_item_id',
        'pihak_id',
        'deskripsi',
        'total_bruto',
        'total_potongan',
        'total_netto',
        'status',
        'created_by',
        'transaction_number',
        'date',
        'budget_id',
        'type',
        'description',
        'gross_amount',
        'net_amount',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            $transaction->prepareLegacyAttributesForSave();
        });
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class, 'dipa_revision_item_id');
    }

    public function honorariumItems()
    {
        return $this->detailHonorarium();
    }

    public function spps()
    {
        return $this->hasMany(Spp::class, 'tagihan_id');
    }

    public function getTypeAttribute()
    {
        return $this->tipe_tagihan;
    }

    public function getDescriptionAttribute()
    {
        return $this->deskripsi;
    }

    public function getGrossAmountAttribute()
    {
        return $this->total_bruto;
    }

    public function getTransactionNumberAttribute()
    {
        return $this->nomor_tagihan;
    }

    public function getBastNumberAttribute()
    {
        return null;
    }

    public function getBastDateAttribute()
    {
        return null;
    }

    private function prepareLegacyAttributesForSave(): void
    {
        $attributes = $this->getAttributes();

        $this->attributes['nomor_tagihan'] = $attributes['nomor_tagihan']
            ?? $attributes['transaction_number']
            ?? ('TRX-' . now()->format('YmdHis'));

        $this->attributes['tipe_tagihan'] = $this->normalizeLegacyType(
            $attributes['tipe_tagihan'] ?? $attributes['type'] ?? null
        );

        $this->attributes['deskripsi'] = $attributes['deskripsi']
            ?? $attributes['description']
            ?? 'Transaksi legacy';

        $grossAmount = $attributes['total_bruto'] ?? $attributes['gross_amount'] ?? 0;
        $netAmount = $attributes['total_netto'] ?? $attributes['net_amount'] ?? $grossAmount;

        $this->attributes['total_bruto'] = $grossAmount;
        $this->attributes['total_netto'] = $netAmount;
        $this->attributes['total_potongan'] = $attributes['total_potongan'] ?? max(0, (float) $grossAmount - (float) $netAmount);
        $this->attributes['status'] = $attributes['status'] ?? 'DRAFT';
        $this->attributes['created_by'] = $attributes['created_by'] ?? auth()->id() ?? User::query()->value('id');

        if (empty($this->attributes['dipa_revision_item_id']) && ! empty($attributes['budget_id'])) {
            $this->attributes['dipa_revision_item_id'] = $attributes['budget_id'];
        }

        if (empty($this->attributes['master_dipa_id'])) {
            $budget = Budget::with('dipaRevision.masterDipa')->find($this->attributes['dipa_revision_item_id'] ?? null);
            $masterDipaId = $budget?->dipaRevision?->master_dipa_id;

            if ($masterDipaId) {
                $this->attributes['master_dipa_id'] = $masterDipaId;
            }
        }

        foreach (['transaction_number', 'date', 'budget_id', 'type', 'description', 'gross_amount', 'net_amount'] as $legacyAttribute) {
            unset($this->attributes[$legacyAttribute]);
        }
    }

    private function normalizeLegacyType(?string $type): string
    {
        return match ($type) {
            'PERJALDIN', 'KONTRAK', 'HONORARIUM' => $type,
            default => 'HONORARIUM',
        };
    }
}
