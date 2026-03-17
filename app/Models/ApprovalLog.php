<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
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
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
