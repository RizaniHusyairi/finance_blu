<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentNumber extends Model
{
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_RESERVED = 'RESERVED';
    public const STATUS_USED = 'USED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const SOURCE_INTERNAL = 'INTERNAL';
    public const SOURCE_EXTERNAL = 'EXTERNAL';

    protected $fillable = [
        'document_key',
        'sequence_group',
        'series_prefix',
        'suffix_code',
        'tahun',
        'running_number',
        'number_padding',
        'full_number',
        'status',
        'usage_source',
        'reserved_by',
        'reserved_at',
        'used_by',
        'used_at',
        'documentable_type',
        'documentable_id',
        'notes',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'running_number' => 'integer',
        'number_padding' => 'integer',
        'reserved_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function reservedBy()
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function documentable()
    {
        return $this->morphTo();
    }
}
