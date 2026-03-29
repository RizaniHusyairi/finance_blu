<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perjaldin extends Model
{
    protected $primaryKey = 'perjaldin_id';

    protected $fillable = [
        'uraian',
        'no_bast',
        'status',
        'is_ppk_approved',
        'is_kasubag_approved',
        'catatan_revisi',
        'revisi_oleh',
    ];

    protected $casts = [
        'is_ppk_approved'     => 'boolean',
        'is_kasubag_approved' => 'boolean',
    ];

    public function isFullyApproved(): bool
    {
        return $this->is_ppk_approved && $this->is_kasubag_approved;
    }

    public function spps()
    {
        return $this->morphMany(Spp::class, 'sppable');
    }

    public function pejabats()
    {
        return $this->hasMany(Pejabat::class, 'perjaldin_id', 'perjaldin_id');
    }

    public function logs()
    {
        return $this->hasMany(PerjaldinLog::class, 'perjaldin_id', 'perjaldin_id')->latest();
    }
}
