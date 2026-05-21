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
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

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
