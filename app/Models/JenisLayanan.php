<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisLayanan extends Model
{
    use HasFactory;

    protected $table = 'master_jenis_layanan';

    protected $guarded = ['id'];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    public function kategoriLayanan(): HasMany
    {
        return $this->hasMany(KategoriLayanan::class, 'jenis_layanan_id')
            ->orderBy('urutan')
            ->orderBy('id');
    }

    public function itemTarifLayanan(): HasMany
    {
        return $this->hasMany(ItemTarifLayanan::class, 'jenis_layanan_id')
            ->orderBy('urutan')
            ->orderBy('id');
    }
}
