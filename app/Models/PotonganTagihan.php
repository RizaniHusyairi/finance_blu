<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PotonganTagihan extends Model
{
    use SoftDeletes;

    protected $table = 'potongan_tagihan';
    protected $guarded = ['id'];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function pajak()
    {
        return $this->belongsTo(MasterTarifPajak::class, 'pajak_id');
    }

    public function akunPotongan()
    {
        return $this->belongsTo(MasterCoa::class, 'akun_potongan_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function getFileFakturPajakAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'FAKTUR_PAJAK'))->path_file;
    }

    public function getFileBuktiSetorPajakAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'BUKTI_SETOR_PAJAK'))->path_file;
    }
}
