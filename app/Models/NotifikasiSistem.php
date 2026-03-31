<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiSistem extends Model
{
    protected $table = 'notifikasi_sistem';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
