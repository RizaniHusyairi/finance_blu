<?php

namespace App\Models;

class MasterMitraVendor extends MasterPihak
{
    protected $table = 'master_pihak';

    protected static function booted(): void
    {
        static::addGlobalScope('vendor_only', function ($query) {
            $query->whereIn('jenis_entitas', ['BADAN_USAHA', 'INSTANSI', 'SATKER', 'KOLEKTIF']);
        });
    }
}
