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
        return stripos($this->nama_lengkap, 'PJP2U') !== false || stripos($this->nama_lengkap, 'Penumpang') !== false;
    }
}
