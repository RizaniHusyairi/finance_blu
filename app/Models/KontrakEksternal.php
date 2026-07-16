<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master Kontrak Eksternal: kontrak/Surat Pesanan yang ditandatangani di luar
 * sistem (e-Purchasing/INAPROC). Menyimpan skema termin + vendor + verifikator;
 * tiap termin ditagih satu per satu (pola Manajemen SPK).
 */
class KontrakEksternal extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_eksternal';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_surat_pesanan' => 'date',
        'nilai_total_kontrak' => 'decimal:2',
        'ada_uang_muka' => 'boolean',
        'nilai_uang_muka' => 'decimal:2',
        'diaktifkan_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(MasterPihak::class, 'vendor_id');
    }

    public function termin()
    {
        return $this->hasMany(KontrakEksternalTermin::class, 'kontrak_eksternal_id')->orderBy('termin_ke');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function ppkUser()
    {
        return $this->belongsTo(User::class, 'ppk_user_id');
    }

    public function ppspmUser()
    {
        return $this->belongsTo(User::class, 'ppspm_user_id');
    }

    public function koordinatorKeuanganUser()
    {
        return $this->belongsTo(User::class, 'koordinator_keuangan_user_id');
    }

    public function bendaharaPengeluaranUser()
    {
        return $this->belongsTo(User::class, 'bendahara_pengeluaran_user_id');
    }

    public function bendaharaPenerimaanUser()
    {
        return $this->belongsTo(User::class, 'bendahara_penerimaan_user_id');
    }

    public function kasubbagUser()
    {
        return $this->belongsTo(User::class, 'kasubbag_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Path arsip Surat Pesanan aktif milik master. */
    public function getFileSuratPesananAttribute(): ?string
    {
        $arsip = $this->arsipDokumen
            ->where('jenis_dokumen', 'SURAT_PESANAN')
            ->sortByDesc('id');

        return optional($arsip->firstWhere('is_active', true) ?? $arsip->first())->path_file;
    }

    /** Σ bruto termin yang sudah ditagih. */
    public function getTotalTerserapAttribute(): float
    {
        return (float) $this->termin->where('status_termin', 'SUDAH_DITAGIH')->sum('nilai_bruto_termin');
    }

    public function getPersentaseSerapanAttribute(): float
    {
        $total = (float) $this->nilai_total_kontrak;

        return $total > 0 ? round($this->total_terserap / $total * 100, 2) : 0.0;
    }

    /** Skema & data kontrak hanya bisa diubah selama DRAFT. */
    public function isEditable(): bool
    {
        return $this->status_kontrak === 'DRAFT';
    }
}
