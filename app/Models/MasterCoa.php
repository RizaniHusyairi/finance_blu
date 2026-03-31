<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCoa extends Model
{
    use SoftDeletes;

    protected $table = 'master_coas';

    protected $fillable = [
        'kd_program',
        'kd_giat',
        'kd_output',
        'kd_suboutput',
        'kd_komponen',
        'kd_subkomponen',
        'kd_akun',
        'kd_item',
        'kode_mak_lengkap',
        'nama_akun',
        'jenis_akun',
        'status_aktif',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];
}
