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

    protected function resolveDocumentPath(array $jenisDokumen): ?string
    {
        $arsip = $this->relationLoaded('arsipDokumen')
            ? $this->arsipDokumen
            : $this->arsipDokumen()->get();

        $dokumen = $arsip->first(function ($item) use ($jenisDokumen) {
            return $item->is_active && in_array($item->jenis_dokumen, $jenisDokumen, true);
        }) ?? $arsip->first(function ($item) use ($jenisDokumen) {
            return in_array($item->jenis_dokumen, $jenisDokumen, true);
        });

        return optional($dokumen)->path_file;
    }

    public function getFileBappAttribute()
    {
        return $this->resolveDocumentPath(['BAPP_FINAL_TTD', 'BAPP']);
    }

    public function getFileBastAttribute()
    {
        return $this->resolveDocumentPath(['BAST_FINAL_TTD', 'BAST']);
    }

    public function getFileBapAttribute()
    {
        return $this->resolveDocumentPath(['BAP_FINAL_TTD', 'BAP']);
    }

    public function getFileInvoiceAttribute()
    {
        return $this->resolveDocumentPath(['INVOICE']);
    }

    public function getFileKwitansiAttribute()
    {
        return $this->resolveDocumentPath(['KWITANSI']);
    }

    public function getFileLampiranLainnyaAttribute()
    {
        return $this->resolveDocumentPath(['LAMPIRAN_LAINNYA']);
    }

    public function getFileFakturPajakAttribute()
    {
        return $this->resolveDocumentPath(['FAKTUR_PAJAK']);
    }
}
