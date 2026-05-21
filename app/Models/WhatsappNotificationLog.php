<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNotificationLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'response_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function tagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }
}
