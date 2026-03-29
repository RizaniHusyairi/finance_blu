<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPersonelEksternal extends Model
{
    use HasFactory;

    protected $table = 'master_personel_eksternal';
    protected $guarded = ['id'];

    public function detailHonorarium()
    {
        return $this->hasMany(DetailHonorarium::class, 'personel_id');
    }
}
