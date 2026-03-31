<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailDipa extends Model
{
    protected $table = 'detail_dipas';

    protected $fillable = [
        'dipa_id',
        'coa_id',
        'nilai_pagu',
    ];

    protected $casts = [
        'nilai_pagu' => 'decimal:2',
    ];

    public function dipa()
    {
        return $this->belongsTo(MasterDipa::class, 'dipa_id');
    }

    public function coa()
    {
        return $this->belongsTo(MasterCoa::class, 'coa_id');
    }
}
