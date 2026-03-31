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
        return $this->hasMany(DokumenSpp::class, 'tagihan_id');
    }
}
