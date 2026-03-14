<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
    
    public function addendums()
    {
        return $this->hasMany(ContractAddendum::class);
    }
    
    public function terms()
    {
        return $this->hasMany(ContractTerm::class);
    }
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
