<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractAddendum extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
