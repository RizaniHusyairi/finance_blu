<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KontrakAddendum extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    public const TYPE_TAMBAH_KURANG_NILAI = 'TAMBAH_KURANG_NILAI';
    public const TYPE_PERPANJANGAN_WAKTU = 'PERPANJANGAN_WAKTU';
    public const TYPE_GANTI_SPESIFIKASI = 'GANTI_SPESIFIKASI';
    public const TYPE_KOMBINASI = 'KOMBINASI';

    protected $table = 'kontrak_addendum';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_addendum' => 'date',
        'tanggal_selesai_lama' => 'date',
        'tanggal_selesai_baru' => 'date',
        'nilai_kontrak_lama' => 'float',
        'nilai_kontrak_baru' => 'float',
        'jangka_waktu_lama' => 'integer',
        'jangka_waktu_baru' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public static function jenisOptions(): array
    {
        return [
            self::TYPE_TAMBAH_KURANG_NILAI,
            self::TYPE_PERPANJANGAN_WAKTU,
            self::TYPE_GANTI_SPESIFIKASI,
            self::TYPE_KOMBINASI,
        ];
    }

    public function kontrakUtama()
    {
        return $this->belongsTo(KontrakPengadaan::class, 'kontrak_pengadaan_id');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function getStatusWorkflowAttribute(): string
    {
        if ($this->status_addendum === self::STATUS_APPROVED) {
            return self::STATUS_APPROVED;
        }

        return $this->status_proses ?: self::STATUS_DRAFT;
    }

    public function getJenisLabelAttribute(): string
    {
        return str_replace('_', ' ', $this->jenis_addendum ?? '-');
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status_workflow === self::STATUS_DRAFT;
    }

    public function getCanBeSubmittedAttribute(): bool
    {
        return in_array($this->status_workflow, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function getCanBeReviewedAttribute(): bool
    {
        return $this->status_workflow === self::STATUS_SUBMITTED;
    }

    public function getHasValueChangeAttribute(): bool
    {
        return round((float) $this->nilai_kontrak_lama, 2) !== round((float) $this->nilai_kontrak_baru, 2);
    }

    public function getHasDateChangeAttribute(): bool
    {
        return optional($this->tanggal_selesai_lama)->toDateString() !== optional($this->tanggal_selesai_baru)->toDateString();
    }

    public function getHasDurationChangeAttribute(): bool
    {
        return (int) $this->jangka_waktu_lama !== (int) $this->jangka_waktu_baru;
    }

    public function getHasSpecificationChangeAttribute(): bool
    {
        return filled($this->catatan_perubahan_spesifikasi)
            || in_array($this->jenis_addendum, [self::TYPE_GANTI_SPESIFIKASI, self::TYPE_KOMBINASI], true);
    }
}
