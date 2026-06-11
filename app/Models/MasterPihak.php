<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterPihak extends Model
{
    use SoftDeletes;

    protected $table = 'master_pihak';

    protected $fillable = [
        'kategori',
        'jenis_entitas',
        'kode_pihak',
        'npwp',
        'nama_pihak',
        'nama_penanggung_jawab',
        'jabatan_penandatangan',
        'tipe_supplier',
        'alamat',
        'email',
        'no_telepon',
        'status_aktif',
        // Alias virtual (punya mutator) agar bisa di-mass-assign dari form vendor.
        // setNamaPerusahaanAttribute -> nama_pihak, setNamaDirekturAttribute -> nama_penanggung_jawab.
        'nama_perusahaan',
        'nama_direktur',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    /**
     * Subclass (MasterMitraVendor, MasterPersonelEksternal) berbagi tabel master_pihak,
     * jadi semua relasi polymorphic harus menyimpan satu morph type yang sama agar
     * data yang ditulis lewat subclass tetap terbaca saat diakses lewat MasterPihak.
     */
    public function getMorphClass()
    {
        return MasterPihak::class;
    }

    public function rekening()
    {
        return $this->morphMany(RekeningBank::class, 'pemilik');
    }

    public function user()
    {
        return $this->morphOne(User::class, 'profilable');
    }

    public function layananJasa()
    {
        return $this->belongsToMany(LayananJasa::class, 'mitra_layanan_jasa', 'mitra_id', 'layanan_jasa_id')
            ->withPivot(['status_aktif', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'created_by'])
            ->withTimestamps();
    }

    public function layananJasaAktif()
    {
        return $this->layananJasa()
            ->wherePivot('status_aktif', true)
            ->where(function ($query) {
                $query->whereNull('mitra_layanan_jasa.tanggal_mulai')
                    ->orWhereDate('mitra_layanan_jasa.tanggal_mulai', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('mitra_layanan_jasa.tanggal_selesai')
                    ->orWhereDate('mitra_layanan_jasa.tanggal_selesai', '>=', now()->toDateString());
            });
    }

    public function getNamaPerusahaanAttribute()
    {
        return $this->nama_pihak;
    }

    public function setNamaPerusahaanAttribute($value)
    {
        $this->attributes['nama_pihak'] = $value;
    }

    public function getNamaDirekturAttribute()
    {
        return $this->nama_penanggung_jawab;
    }

    public function setNamaDirekturAttribute($value)
    {
        $this->attributes['nama_penanggung_jawab'] = $value;
    }
}
