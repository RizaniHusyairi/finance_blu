<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogStatusDokumen extends Model
{
    protected $table = 'log_status_dokumen';
    protected $guarded = ['id'];

    public function dokumen()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
