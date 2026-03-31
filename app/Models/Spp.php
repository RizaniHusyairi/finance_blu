<?php

namespace App\Models;

class Spp extends DokumenSpp
{
    public function getSppIdAttribute()
    {
        return $this->id;
    }

    public function getStatusSppAttribute()
    {
        return $this->status;
    }

    public function setStatusSppAttribute($value)
    {
        $this->attributes['status'] = $value;
    }

    public function sppable()
    {
        return $this->tagihan();
    }
}
