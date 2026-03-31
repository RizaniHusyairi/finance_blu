<?php

namespace App\Models;

class Budget extends MasterDipa
{
    public function getYearAttribute()
    {
        return $this->tahun_anggaran;
    }
}
