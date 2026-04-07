<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailKontrak extends Model
{
    use SoftDeletes;

    protected $table = 'detail_kontrak';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_bapp' => 'date',
        'tanggal_bast' => 'date',
        'tanggal_bap' => 'date',
        'tanggal_invoice' => 'date',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function kontrakTermin()
    {
        return $this->belongsTo(KontrakTermin::class, 'kontrak_termin_id');
    }

    public function termin()
    {
        return $this->kontrakTermin();
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function getFileBappAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'BAPP'))->path_file;
    }

    public function getFileBastAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'BAST'))->path_file;
    }

    public function getFileBapAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'BAP'))->path_file;
    }

    public function getFileInvoiceAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'INVOICE'))->path_file;
    }

    public function getFileKwitansiAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'KWITANSI'))->path_file;
    }

    public function getFileLampiranLainnyaAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'LAMPIRAN_LAINNYA'))->path_file;
    }

    public function getFileFakturPajakAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'FAKTUR_PAJAK'))->path_file;
    }
}
