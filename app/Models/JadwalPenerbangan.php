<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPenerbangan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_penerbangan';
    protected $guarded = ['id'];
    protected $casts = [
        'sched_arrival' => 'datetime:H:i',
        'sched_departure' => 'datetime:H:i',
        'aktif' => 'boolean',
    ];

    public function mitra()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }
}
