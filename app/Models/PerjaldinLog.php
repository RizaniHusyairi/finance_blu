<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerjaldinLog extends Model
{
    protected $fillable = [
        'perjaldin_id',
        'user_name',
        'action',
        'catatan',
    ];

    public function perjaldin(): BelongsTo
    {
        return $this->belongsTo(Perjaldin::class, 'perjaldin_id', 'perjaldin_id');
    }
}
