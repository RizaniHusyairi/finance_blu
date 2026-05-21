<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function tagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }
}
