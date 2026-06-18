<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KontrakMitraJasa extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_mitra_jasa';

    protected $fillable = [
        'mitra_jasa_id',
        'nomor_kontrak',
        'nama_kontrak',
        'jenis_dokumen',
        'tanggal_kontrak',
        'tanggal_mulai',
        'tanggal_selesai',
        'file_kontrak',
        'status_kontrak',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_kontrak' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Jenis dokumen dasar kontrak (kode => label). Setiap layanan yang ditagihkan
     * harus punya dasar dokumen; jenisnya menyesuaikan sifat layanan.
     */
    public const JENIS_DOKUMEN = [
        'KONTRAK' => 'Kontrak',
        'PERJANJIAN_KERJA_SAMA' => 'Perjanjian Kerja Sama',
        'PERJANJIAN_KONSESI' => 'Perjanjian Konsesi',
        'SK_REGULASI_TARIF' => 'SK / Regulasi Tarif',
        'SPK' => 'Surat Perintah Kerja (SPK)',
        'SURAT_PERMOHONAN' => 'Surat Permohonan',
        'BERITA_ACARA' => 'Berita Acara',
        'REKAP_PEMAKAIAN' => 'Rekap Pemakaian',
        'DOKUMEN_LAINNYA' => 'Dokumen Lainnya',
    ];

    public function mitraJasa()
    {
        return $this->belongsTo(MitraJasa::class);
    }

    public function konsesi()
    {
        return $this->hasMany(MitraJasaKonsesi::class, 'kontrak_mitra_jasa_id');
    }

    public function penjualan()
    {
        return $this->hasMany(MitraJasaPenjualan::class, 'kontrak_mitra_jasa_id');
    }

    public function tagihanJasa()
    {
        return $this->hasMany(TagihanJasa::class, 'kontrak_mitra_jasa_id');
    }

    public function layananJasa()
    {
        return $this->belongsToMany(LayananJasa::class, 'kontrak_mitra_jasa_layanan', 'kontrak_mitra_jasa_id', 'layanan_jasa_id')
            ->withPivot(['created_by'])
            ->withTimestamps();
    }

    public function pjp2u()
    {
        return $this->hasMany(MitraJasaPjp2u::class, 'kontrak_mitra_jasa_id');
    }
}
