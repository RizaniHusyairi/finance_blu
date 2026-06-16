<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LayananJasa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'layanan_jasas';
    protected $guarded = ['id'];
    protected $appends = [
        'kode_pembayaran_lengkap',
    ];
    protected $casts = [
        'tarif_dasar' => 'decimal:2',
        'persentase_konsesi' => 'decimal:4',
        'is_active' => 'boolean',
        'is_leaf' => 'boolean',
        'mendukung_konsesi' => 'boolean',
        'wajib_tagihan_terpisah' => 'boolean',
        'jumlah_hari_jatuh_tempo' => 'integer',
        'masa_toleransi_hari' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(LayananJasa::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(LayananJasa::class, 'parent_id');
    }

    public function mitras()
    {
        return $this->belongsToMany(MitraJasa::class, 'mitra_jasa_layanan', 'layanan_jasa_id', 'mitra_jasa_id')
            ->withPivot(['status_aktif', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'created_by'])
            ->withTimestamps();
    }

    public function adminJasa()
    {
        return $this->belongsToMany(User::class, 'admin_jasa_layanan', 'layanan_jasa_id', 'user_id')
            ->withPivot(['status_aktif', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'created_by'])
            ->withTimestamps();
    }

    public function tarifPeriodes()
    {
        return $this->hasMany(LayananJasaTarif::class, 'layanan_jasa_id');
    }

    /**
     * Tarif efektif pada tanggal tertentu (memperhitungkan diskon berjadwal).
     * Shortcut ke App\Services\TarifLayananService::resolve().
     */
    public function tarifEfektif(\Illuminate\Support\Carbon|string|null $tanggal = null, ?int $mitraJasaId = null): float
    {
        return app(\App\Services\TarifLayananService::class)->resolve($this, $tanggal, $mitraJasaId)['tarif'];
    }

    public function scopeLeaves($query)
    {
        return $query->where('is_leaf', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getNamaLengkapAttribute()
    {
        $names = [$this->nama_layanan];
        $parent = $this->parent;

        // Keep a small guard so bad imported hierarchy data cannot loop forever.
        $depth = 0;
        while ($parent && $depth < 10) {
            array_unshift($names, $parent->nama_layanan);
            $parent = $parent->parent;
            $depth++;
        }

        return implode(' > ', $names);
    }

    public function getKodePembayaranLengkapAttribute()
    {
        $kodeMak = trim((string) ($this->kode_mak ?? ''));
        $kodeJenisPembayaran = trim((string) ($this->kode_jenis_pembayaran ?? ''));

        if ($kodeMak === '' || $kodeJenisPembayaran === '') {
            return null;
        }

        return $kodeMak . '.' . $kodeJenisPembayaran;
    }

    public function isPjp2u()
    {
        $nama = (string) $this->nama_lengkap;

        // Garbarata / Bis Layanan Penumpang di Apron mengandung kata "Penumpang"
        // tetapi BUKAN layanan PJP2U — kecualikan secara eksplisit.
        if (stripos($nama, 'Garbarata') !== false || stripos($nama, 'Apron') !== false) {
            return false;
        }

        // PJP2U = Pelayanan Jasa Penumpang Pesawat Udara. Cocokkan token resmi
        // "PJP2U" atau frasa lengkapnya, bukan sekadar kata "Penumpang".
        return stripos($nama, 'PJP2U') !== false
            || stripos($nama, 'Penumpang Pesawat Udara') !== false;
    }
}
