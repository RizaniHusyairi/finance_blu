<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandingInstruction extends Model
{
    use HasFactory;

    protected $table = 'standing_instructions';

    protected $fillable = [
        'dokumen_spp_id',
        'nomor_surat',
        'tanggal_surat',
        'ppk_user_id',
        'kpa_user_id',
        'nama_ppk_snapshot',
        'jabatan_ppk_snapshot',
        'nama_kpa_snapshot',
        'jabatan_kpa_snapshot',
        'rekening_sumber_nomor',
        'rekening_sumber_nama',
        'rekening_sumber_bank',
        'rekening_tujuan_nomor',
        'rekening_tujuan_nama',
        'rekening_tujuan_bank',
        'nominal_transfer',
        'nominal_terbilang',
        'uraian_penggunaan',
        'status',
        'dibuat_oleh_id',
        'difinalkan_oleh_id',
        'finalized_at',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'nominal_transfer' => 'decimal:2',
        'finalized_at' => 'datetime',
    ];

    public function dokumenSpp(): BelongsTo
    {
        return $this->belongsTo(DokumenSpp::class, 'dokumen_spp_id');
    }

    public function ppkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ppk_user_id');
    }

    public function kpaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kpa_user_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    public function difinalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'difinalkan_oleh_id');
    }
}
