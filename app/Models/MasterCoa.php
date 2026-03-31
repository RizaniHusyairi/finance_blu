<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCoa extends Model
{
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
    ];
}
