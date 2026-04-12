<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterTarifPajak extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_tarif_pajak';
    protected $guarded = ['id'];

    protected $casts = [
        'persentase' => 'float',
        'status_aktif' => 'boolean',
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
    ];
}
