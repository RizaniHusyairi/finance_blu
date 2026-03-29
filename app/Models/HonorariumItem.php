<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorariumItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'honor_amount' => 'decimal:2',
        'pph_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}