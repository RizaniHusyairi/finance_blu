<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DokumenSp2d extends Model
{
    use SoftDeletes;

    protected $table = 'dokumen_sp2d';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_sp2d' => 'date',
    ];

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_MENUNGGU_VERIFIKASI = 'MENUNGGU_VERIFIKASI';
    public const STATUS_DISETUJUI_FINAL = 'DISETUJUI_FINAL';
    public const STATUS_MENUNGGU_UPLOAD = 'MENUNGGU_UPLOAD';
    public const STATUS_SP2D_TERBIT = 'SP2D_TERBIT';
    public const STATUS_REVISI = 'REVISI';
    public const STATUS_APPROVED = 'APPROVED'; // Legacy
    public const STATUS_EXECUTED = 'EXECUTED';

    public const SP2D_SIGNED_ARCHIVE_TYPE = 'SP2D_BERTANDATANGAN';

    public function npi()
    {
        return $this->belongsTo(DokumenNpi::class, 'npi_id');
    }

    public function bendaharaPengeluaran()
    {
        return $this->belongsTo(User::class, 'bendahara_pengeluaran_id');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function workflowInstances()
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }


    public function getSppIdAttribute()
    {
        return optional(optional($this->npi)->spm)->spp_id;
    }

    public function getNomorNpiAttribute()
    {
        return optional($this->npi)->nomor_npi;
    }

    public function getNomorSpmAttribute()
    {
        return optional(optional($this->npi)->spm)->nomor_spm;
    }

    public function getNomorSppAttribute()
    {
        return optional(optional(optional($this->npi)->spm)->spp)->nomor_spp;
    }

    public function getJumlahUangAttribute()
    {
        return optional(optional(optional($this->npi)->spm)->spp)->nominal_spp;
    }

    public function getStatusSppAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'SP2D Draft',
            self::STATUS_APPROVED => 'SP2D Terbit',
            self::STATUS_SP2D_TERBIT => 'SP2D Terbit',
            self::STATUS_EXECUTED => 'Lunas',
            default => 'SP2D Draft',
        };
    }

    public function getCatatanBkuAttribute()
    {
        return optional(
            $this->logs()
                ->where('status_baru', self::STATUS_EXECUTED)
                ->latest()
                ->first()
        )->catatan;
    }

    public function getBuktiTransferAttribute()
    {
        return $this->arsipDokumen()
            ->where('jenis_dokumen', 'BUKTI_TRANSFER_SP2D')
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function getSignedArsipAttribute()
    {
        return $this->arsipDokumen()
            ->where('jenis_dokumen', self::SP2D_SIGNED_ARCHIVE_TYPE)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function getHasSignedFileAttribute(): bool
    {
        return $this->signed_arsip !== null;
    }

    public function unlockNextTerminKontrak()
    {
        $terminInfo = $this->npi?->spm?->spp?->tagihan?->detailKontrak?->kontrakTermin;
        
        if ($terminInfo) {
            $nextTermin = \App\Models\KontrakTermin::where('kontrak_pengadaan_id', $terminInfo->kontrak_pengadaan_id)
                ->where('termin_ke', '>', $terminInfo->termin_ke)
                ->orderBy('termin_ke', 'asc')
                ->first();
                
            if ($nextTermin && $nextTermin->status_termin === 'LOCKED') {
                $nextTermin->update(['status_termin' => 'READY_TO_BILL']);
            }
        }
    }
}
