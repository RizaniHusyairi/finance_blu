<?php

namespace App\Models;

class Perjaldin extends Tagihan
{
    public function pejabatats()
    {
        return $this->detailPerjaldin();
    }

    public function pejabats()
    {
        return $this->detailPerjaldin();
    }

    public function spps()
    {
        return $this->hasMany(Spp::class, 'tagihan_id');
    }

    public function getPerjaldinIdAttribute()
    {
        return $this->id;
    }

    public function getUraianAttribute()
    {
        return $this->deskripsi;
    }

    public function getNoBastAttribute()
    {
        return null;
    }
}
