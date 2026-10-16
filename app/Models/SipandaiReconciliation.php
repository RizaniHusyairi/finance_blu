<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SipandaiReconciliation extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'snapshot' => 'array',
        'comparison' => 'array',
        'fetched_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function fetchedBy()
    {
        return $this->belongsTo(User::class, 'fetched_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
