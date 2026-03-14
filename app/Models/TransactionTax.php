<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionTax extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
