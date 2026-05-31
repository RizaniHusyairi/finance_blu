<?php

namespace App\Models;

use App\Enums\JenisRekening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekeningBank extends Model
{
    use SoftDeletes;

    protected $table = 'rekening_bank';

    protected $fillable = [
        'pemilik_type',
        'pemilik_id',
        'nama_bank',
        'nomor_rekening',
        'nama_rekening',
        'kode_bank',
        'jenis_rekening',
        'saldo_awal',
        'saldo_awal_per_tanggal',
        'is_default',
        'status_aktif',
    ];

    protected $casts = [
        'jenis_rekening' => JenisRekening::class,
        'saldo_awal' => 'decimal:2',
        'saldo_awal_per_tanggal' => 'date',
        'is_default' => 'boolean',
        'status_aktif' => 'boolean',
    ];

    public function pemilik()
    {
        return $this->morphTo();
    }

    public function importMutasiBanks()
    {
        return $this->hasMany(ImportMutasiBank::class, 'rekening_bank_id');
    }

    public function detailMutasiBanks()
    {
        return $this->hasManyThrough(
            DetailMutasiBank::class,
            ImportMutasiBank::class,
            'rekening_bank_id',
            'import_mutasi_bank_id'
        );
    }

    public function bukuKasUmums()
    {
        return $this->hasMany(BukuKasUmum::class, 'sumber_rekening_id');
    }
}
