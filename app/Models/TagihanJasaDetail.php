<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanJasaDetail extends Model
{
    use HasFactory;

    protected $table = 'tagihan_jasa_details';
    protected $guarded = ['id'];

    public function tagihanJasa()
    {
        return $this->belongsTo(TagihanJasa::class, 'tagihan_jasa_id');
    }

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }
}
