<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
    protected $table = 'tagihan';
    protected $guarded = ['id'];

    public function detailKontrak()
    {
        return $this->hasOne(DetailKontrak::class, 'tagihan_id');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function detailPerjaldin()
    {
        return $this->hasMany(DetailPerjaldin::class, 'tagihan_id');
    }

    public function detailHonorarium()
    {
        return $this->hasMany(DetailHonorarium::class, 'tagihan_id');
    }
}
