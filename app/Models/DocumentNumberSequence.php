<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    protected $fillable = [
        'series_prefix',
        'suffix_code',
        'tahun',
        'last_number',
        'number_padding',
        'is_active',
        'keterangan',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'last_number' => 'integer',
        'number_padding' => 'integer',
        'is_active' => 'boolean',
    ];
}
