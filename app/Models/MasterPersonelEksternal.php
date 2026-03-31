<?php

namespace App\Models;

class MasterPersonelEksternal extends MasterPihak
{
    protected $table = 'master_pihak';

    protected static function booted(): void
    {
        static::addGlobalScope('personel_only', function ($query) {
            $query->where('jenis_entitas', 'PERORANGAN');
        });
    }
}
