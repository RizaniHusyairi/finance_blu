<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailHonorarium extends Model
{
    use HasFactory;

    protected $table = 'detail_honorarium';
    protected $guarded = ['id'];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function personel()
    {
        return $this->belongsTo(MasterPersonelEksternal::class, 'personel_id');
    }
}
