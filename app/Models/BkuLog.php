<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BkuLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    public function transaction()
    {
        return $this->belongsTo(BluPaymentSubmission::class, 'transaction_id');
    }

    public function bluPaymentSubmission()
    {
        return $this->belongsTo(BluPaymentSubmission::class, 'transaction_id');
    }
}
