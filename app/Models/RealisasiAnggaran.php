<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RealisasiAnggaran extends Model
{
    use SoftDeletes;

    protected $table = 'realisasi_anggaran';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pencairan' => 'date',
        'nominal_cair' => 'decimal:2',
    ];

    public function dipaRevisionItem()
    {
        return $this->belongsTo(DetailDipa::class, 'dipa_revision_item_id');
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
