<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTarifLayanan extends Model
{
    use HasFactory;

    protected $table = 'master_item_tarif_layanan';

    protected $guarded = ['id'];

    protected $casts = [
        'tarif' => 'decimal:2',
        'is_billable' => 'boolean',
        'status_aktif' => 'boolean',
    ];

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class, 'jenis_layanan_id');
    }

    public function kategoriLayanan(): BelongsTo
    {
        return $this->belongsTo(KategoriLayanan::class, 'kategori_layanan_id');
    }

    public function getStatusTagihanLabelAttribute(): string
    {
        return $this->is_billable ? 'Dapat Ditagihkan' : 'Tidak Ditagihkan';
    }
}
