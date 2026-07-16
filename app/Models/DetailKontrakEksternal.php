<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Detail tagihan atas kontrak eksternal (Surat Pesanan e-Purchasing/INAPROC)
 * yang dibuat dan ditandatangani di luar sistem. File-file mengikuti pola
 * DetailKontrak: tersimpan sebagai ArsipDokumen (morph) dan diakses lewat
 * accessor file_* berbasis jenis_dokumen.
 */
class DetailKontrakEksternal extends Model
{
    use SoftDeletes;

    protected $table = 'detail_kontrak_eksternal';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_surat_pesanan' => 'date',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function kontrakEksternalTermin()
    {
        return $this->belongsTo(KontrakEksternalTermin::class, 'kontrak_eksternal_termin_id');
    }

    /** Master kontrak eksternal (via termin). */
    public function kontrakEksternal(): ?KontrakEksternal
    {
        return $this->kontrakEksternalTermin?->kontrak;
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

    public function getFileSuratPesananAttribute()
    {
        // Arsip Surat Pesanan hidup di master kontrak eksternal; fallback ke
        // arsip milik detail (tagihan lama sebelum era master).
        return $this->kontrakEksternal()?->file_surat_pesanan
            ?? $this->resolveDocumentPath(['SURAT_PESANAN']);
    }

    public function getFileFakturPajakAttribute()
    {
        return $this->resolveDocumentPath(['FAKTUR_PAJAK']);
    }

    public function getFileInvoiceAttribute()
    {
        return $this->resolveDocumentPath(['INVOICE']);
    }

    public function getFileKwitansiAttribute()
    {
        return $this->resolveDocumentPath(['KWITANSI']);
    }

    public function getFileBastAttribute()
    {
        return $this->resolveDocumentPath(['BAST']);
    }
}
