<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    protected $casts = [
        'date' => 'date',
        'bast_date' => 'date',
        'spp_date' => 'date',
        'spm_date' => 'date',
        'npi_date' => 'date',
        'sp2d_date' => 'date',
        'gross_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function term()
    {
        return $this->belongsTo(ContractTerm::class, 'term_id');
    }
    
    public function taxes()
    {
        return $this->hasMany(TransactionTax::class, 'transaction_id');
    }
    
    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class, 'transaction_id');
    }
    
    public function bkuLogs()
    {
        return $this->hasMany(BkuLog::class, 'transaction_id');
    }

    public function honorariumItems()
    {
        return $this->hasMany(HonorariumItem::class, 'transaction_id');
    }

    public function getAmountAttribute(): float
    {
        return (float) $this->gross_amount;
    }

    public function setAmountAttribute($value): void
    {
        $amount = (float) $value;

        $this->attributes['gross_amount'] = $amount;
        $this->attributes['net_amount'] = $amount;
    }

    public function syncNetAmount(): void
    {
        $grossAmount = (float) $this->gross_amount;
        $taxTotal = (float) $this->taxes()->sum('amount');

        $this->forceFill([
            'net_amount' => max($grossAmount - $taxTotal, 0),
        ])->save();
    }
}
