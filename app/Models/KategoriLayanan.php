<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriLayanan extends Model
{
    use HasFactory;

    protected $table = 'master_kategori_layanan';

    protected $guarded = ['id'];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class, 'jenis_layanan_id');
    }

    public function itemTarifLayanan(): HasMany
    {
        return $this->hasMany(ItemTarifLayanan::class, 'kategori_layanan_id')
            ->orderBy('urutan')
            ->orderBy('id');
    }
}
