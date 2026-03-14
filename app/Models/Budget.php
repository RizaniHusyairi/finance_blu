<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'initial_budget' => 'decimal:2',
        'realized_budget' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
    ];

    public function getAmountAttribute(): float
    {
        return (float) $this->initial_budget;
    }
}
