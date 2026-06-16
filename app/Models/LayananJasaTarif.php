<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Periode tarif khusus/diskon untuk sebuah layanan jasa.
 * Lihat App\Services\TarifLayananService untuk resolusi tarif efektif.
 */
class LayananJasaTarif extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'layanan_jasa_tarifs';
    protected $guarded = ['id'];

    protected $casts = [
        'tarif' => 'decimal:2',
        'persen_diskon' => 'decimal:2',
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
        'is_active' => 'boolean',
    ];

    public function layananJasa()
    {
        return $this->belongsTo(LayananJasa::class, 'layanan_jasa_id');
    }

    public function mitra()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Periode yang berlaku pada tanggal tertentu (default hari ini).
     */
    public function scopeBerlakuPada($query, Carbon|string|null $tanggal = null)
    {
        $tanggal = $tanggal ? Carbon::parse($tanggal)->toDateString() : now()->toDateString();

        return $query->where('is_active', true)
            ->whereDate('berlaku_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')
                    ->orWhereDate('berlaku_sampai', '>=', $tanggal);
            });
    }

    /**
     * Status periode relatif terhadap hari ini: AKAN_DATANG | AKTIF | BERAKHIR.
     */
    public function getStatusPeriodeAttribute(): string
    {
        $today = now()->startOfDay();

        if ($this->berlaku_mulai && $this->berlaku_mulai->gt($today)) {
            return 'AKAN_DATANG';
        }

        if ($this->berlaku_sampai && $this->berlaku_sampai->lt($today)) {
            return 'BERAKHIR';
        }

        return 'AKTIF';
    }
}
