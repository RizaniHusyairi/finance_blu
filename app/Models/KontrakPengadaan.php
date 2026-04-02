<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KontrakPengadaan extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_pengadaan';
    protected $guarded = ['id'];

    public function vendor()
    {
        return $this->belongsTo(MasterPihak::class, 'vendor_id');
    }

    public function dipa()
    {
        return $this->belongsTo(MasterDipa::class, 'master_dipa_id');
    }

    public function dipaRevisionItem()
    {
        return $this->belongsTo(DetailDipa::class, 'dipa_revision_item_id');
    }

    public function addendums()
    {
        return $this->hasMany(KontrakAddendum::class, 'kontrak_pengadaan_id');
    }

    public function termin()
    {
        return $this->hasMany(KontrakTermin::class, 'kontrak_pengadaan_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function getFileSpkAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'SPK'))->path_file;
    }

    public function getFileSpmkAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'SPMK'))->path_file;
    }

    public function getFileRingkasanKontrakAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'RINGKASAN_KONTRAK'))->path_file;
    }

    public function getFileJaminanUangMukaAttribute()
    {
        return optional($this->arsipDokumen->firstWhere('jenis_dokumen', 'JAMINAN_UANG_MUKA'))->path_file;
    }

    public function getTotalTerserapAttribute()
    {
        return $this->termin()->where('status_termin', 'SUDAH_DITAGIH')->sum('nilai_bruto_termin');
    }

    public function getPersentaseSerapanAttribute()
    {
        $totalTerserap = $this->total_terserap;
        if ((float) $this->nilai_total_kontrak === 0.0) {
            return 0;
        }

        return round(($totalTerserap / $this->nilai_total_kontrak) * 100, 2);
    }
}
