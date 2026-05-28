<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }
}
