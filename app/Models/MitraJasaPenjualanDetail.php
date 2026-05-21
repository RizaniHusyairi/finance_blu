<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraJasaPenjualanDetail extends Model
{
    use SoftDeletes;

    protected $table = 'mitra_jasa_penjualan_details';
    protected $guarded = ['id'];
    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date',
        'total_omzet' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function penjualan()
    {
        return $this->belongsTo(MitraJasaPenjualan::class, 'mitra_jasa_penjualan_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
