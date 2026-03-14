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
        return $this->belongsTo(Transaction::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
