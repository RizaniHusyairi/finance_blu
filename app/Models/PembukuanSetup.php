<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Identitas satker (kop dokumen BKU) — sheet SETUP File 1 & kop File 2.
 * Diperlakukan singleton: ambil baris pertama via current().
 */
class PembukuanSetup extends Model
{
    protected $table = 'pembukuan_setup';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_dipa' => 'date',
        'tahun_anggaran' => 'integer',
    ];

    public static function current(): ?self
    {
        return static::query()->orderBy('id')->first();
    }
}
