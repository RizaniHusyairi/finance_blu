<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LayananJasa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'layanan_jasas';
    protected $guarded = ['id'];
}
