<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekonsiliasiBankLog extends Model
{
    protected $table = 'rekonsiliasi_bank_logs';
    protected $guarded = ['id'];

    public function rekonsiliasiBank()
    {
        return $this->belongsTo(RekonsiliasiBank::class, 'rekonsiliasi_bank_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
