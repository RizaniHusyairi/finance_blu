<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterTarifPajak extends Model
{
    use HasFactory;

    protected $table = 'master_tarif_pajak';
    protected $guarded = ['id'];
}
