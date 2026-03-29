<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spp extends Model
{
    protected $primaryKey = 'spp_id';

    protected $fillable = [
        'sppable_id',
        'sppable_type',
        'kategori_biaya',
        'jumlah_uang',
        'uraian',
        'status_spp',
        'catatan_revisi',
        'nomor_spp',
        'tanggal_spp',
        'nomor_spm',
        'tanggal_spm',
        'penandatangan_spm_nama',
        'penandatangan_spm_nip',
        'nomor_npi',
        'tanggal_npi',
        'nomor_sp2d',
        'tanggal_sp2d',
        'catatan_bku',
        'tahun_anggaran',
        'nomor_dipa',
        'tanggal_dipa',
        'no_kontrak',
        'tgl_kontrak',
        'akun_mak',
        'jenis_tagihan',
        'jatuh_tempo',
        'cara_bayar',
        'penandatangan_nama',
        'penandatangan_nip',
    ];

    public function sppable()
    {
        return $this->morphTo();
    }
}
