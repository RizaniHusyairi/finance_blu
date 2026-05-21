<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraJasa extends Model
{
    use SoftDeletes;

    protected $table = 'mitra_jasa';

    protected $fillable = [
        'kode_mitra',
        'nama_mitra',
        'jenis_mitra',
        'npwp',
        'email',
        'no_telepon',
        'alamat',
        'nama_penanggung_jawab',
        'jabatan_penanggung_jawab',
        'status_aktif',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    public function user()
    {
        return $this->morphOne(User::class, 'profilable');
    }

    public function kontrak()
    {
        return $this->hasMany(KontrakMitraJasa::class);
    }

    public function kontrakAktif()
    {
        return $this->kontrak()->where('status_kontrak', 'AKTIF');
    }

    public function tagihanJasas()
    {
        return $this->hasMany(TagihanJasa::class, 'mitra_jasa_id');
    }

    public function konsesi()
    {
        return $this->hasMany(MitraJasaKonsesi::class, 'mitra_jasa_id');
    }

    public function konsesiAktif()
    {
        return $this->konsesi()
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', now()->toDateString())
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', now()->toDateString());
            });
    }

    public function pjp2u()
    {
        return $this->hasMany(MitraJasaPjp2u::class, 'mitra_jasa_id');
    }

    public function pjp2uAktif()
    {
        return $this->pjp2u()
            ->where('status_aktif', true)
            ->where(function ($query) {
                $query->whereNull('tanggal_mulai')
                    ->orWhereDate('tanggal_mulai', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', now()->toDateString());
            });
    }

    public function penjualan()
    {
        return $this->hasMany(MitraJasaPenjualan::class, 'mitra_jasa_id');
    }

    public function laporanUtilitas()
    {
        return $this->hasMany(LaporanUtilitas::class, 'mitra_jasa_id');
    }

    public function layananJasa()
    {
        return $this->belongsToMany(LayananJasa::class, 'mitra_jasa_layanan', 'mitra_jasa_id', 'layanan_jasa_id')
            ->withPivot(['status_aktif', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'created_by'])
            ->withTimestamps();
    }

    public function layananJasaAktif()
    {
        return $this->layananJasa()
            ->wherePivot('status_aktif', true)
            ->where(function ($query) {
                $query->whereNull('mitra_jasa_layanan.tanggal_mulai')
                    ->orWhereDate('mitra_jasa_layanan.tanggal_mulai', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('mitra_jasa_layanan.tanggal_selesai')
                    ->orWhereDate('mitra_jasa_layanan.tanggal_selesai', '>=', now()->toDateString());
            });
    }

    public function getNamaPihakAttribute(): string
    {
        return $this->nama_mitra;
    }

    public function getJabatanPenandatanganAttribute(): ?string
    {
        return $this->jabatan_penanggung_jawab;
    }

    public function getNamaPenandatanganAttribute(): ?string
    {
        return $this->nama_penanggung_jawab;
    }
}
