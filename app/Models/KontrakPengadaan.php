<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class KontrakPengadaan extends Model
{
    use SoftDeletes;

    protected $table = 'kontrak_pengadaan';
    protected $fillable = [
        'vendor_id',
        'ppk_user_id',
        'master_dipa_id',
        'dipa_revision_item_id',
        'nomor_spk',
        'tanggal_spk',
        'nomor_spmk',
        'tanggal_spmk',
        'nama_pekerjaan',
        'nomor_surat_undangan_pengadaan',
        'nomor_ba_hasil_pengadaan',
        'nilai_total_kontrak',
        'metode_pembayaran',
        'ada_uang_muka',
        'nilai_uang_muka',
        'sisa_uang_muka_belum_lunas',
        'jangka_waktu',
        'satuan_waktu',
        'tanggal_mulai',
        'tanggal_selesai',
        'masa_pemeliharaan_hari',
        'tanggal_mulai_pemeliharaan',
        'tanggal_selesai_pemeliharaan',
        'ketentuan_denda',
        'status_kontrak',
    ];

    public function vendor()
    {
        return $this->belongsTo(MasterPihak::class, 'vendor_id');
    }

    public function ppkUser()
    {
        return $this->belongsTo(User::class, 'ppk_user_id');
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

    public function arsipDokumenAktif()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable')->where('is_active', true);
    }

    public function getFileSpkAttribute()
    {
        return optional($this->arsipDokumen->where('is_active', true)->firstWhere('jenis_dokumen', 'SPK'))->path_file;
    }

    public function getNamaPpkAttribute()
    {
        return optional($this->ppkUser?->pegawai)->nama_lengkap
            ?? $this->ppkUser?->name;
    }

    public function getNipPpkAttribute()
    {
        return optional($this->ppkUser?->pegawai)->nip;
    }

    public function getFileSpmkAttribute()
    {
        return optional($this->arsipDokumen->where('is_active', true)->firstWhere('jenis_dokumen', 'SPMK'))->path_file;
    }

    public function getSpmkFinalTtdArsipAttribute()
    {
        return $this->arsipDokumen
            ->where('is_active', true)
            ->where('jenis_dokumen', 'SPMK_FINAL_TTD')
            ->sortByDesc('created_at')
            ->first();
    }

    public function getFileSpmkFinalTtdAttribute()
    {
        return optional($this->spmk_final_ttd_arsip)->path_file;
    }

    public function getHasSpmkFinalTtdAttribute()
    {
        return !empty($this->file_spmk_final_ttd);
    }

    public function getFileRingkasanKontrakAttribute()
    {
        return $this->file_ringkasan_kontrak_final_ttd
            ?: optional($this->arsipDokumen->where('is_active', true)->firstWhere('jenis_dokumen', 'RINGKASAN_KONTRAK'))->path_file;
    }

    public function getRingkasanKontrakFinalTtdArsipAttribute()
    {
        return $this->arsipDokumen
            ->where('is_active', true)
            ->where('jenis_dokumen', 'RINGKASAN_KONTRAK_FINAL_TTD')
            ->sortByDesc('created_at')
            ->first();
    }

    public function getFileRingkasanKontrakFinalTtdAttribute()
    {
        return optional($this->ringkasan_kontrak_final_ttd_arsip)->path_file;
    }

    public function getHasRingkasanKontrakFinalTtdAttribute()
    {
        return !empty($this->file_ringkasan_kontrak_final_ttd);
    }

    public function getFileJaminanUangMukaAttribute()
    {
        return optional($this->arsipDokumen->where('is_active', true)->firstWhere('jenis_dokumen', 'JAMINAN_UANG_MUKA'))->path_file;
    }

    public function getSpkFinalTtdArsipAttribute()
    {
        return $this->arsipDokumen
            ->where('is_active', true)
            ->where('jenis_dokumen', 'SPK_FINAL_TTD')
            ->sortByDesc('created_at')
            ->first();
    }

    public function getFileSpkFinalTtdAttribute()
    {
        return optional($this->spk_final_ttd_arsip)->path_file;
    }

    public function getHasSpkFinalTtdAttribute()
    {
        return !empty($this->file_spk_final_ttd);
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

    public function canActivateContract()
    {
        // Must reload the relationship to verify fresh data if just written
        return $this->has_spk_final_ttd && $this->has_spmk_final_ttd && $this->has_ringkasan_kontrak_final_ttd;
    }

    public function activateIfDocumentsComplete()
    {
        // Must explicitly refresh in case relations were cached before the new doc was inserted
        $this->refresh();
        if ($this->status_kontrak === 'DRAFT' && $this->canActivateContract()) {
            $this->update(['status_kontrak' => 'AKTIF']);
            
            \App\Models\LogStatusDokumen::create([
                'dokumen_type'      => self::class,
                'dokumen_id'        => $this->id,
                'user_id'           => \Illuminate\Support\Facades\Auth::id(),
                'role_saat_itu'     => \Illuminate\Support\Facades\Auth::user()?->getRoleNames()->first() ?? 'Sistem',
                'status_sebelumnya' => 'DRAFT',
                'status_baru'       => 'AKTIF',
                'aksi'              => 'AUTO_ACTIVATE',
                'catatan'           => 'Kontrak aktif otomatis setelah seluruh dokumen final bertandatangan lengkap.',
                'ip_address'        => request()->ip(),
            ]);
            return true;
        }
        return false;
    }
}
