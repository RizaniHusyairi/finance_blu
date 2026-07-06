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

    /** Staf yang mengunggah manual atas nama vendor (signed_via = MANUAL). */
    public function signedByUser()
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }
}
