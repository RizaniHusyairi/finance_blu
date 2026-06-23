<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArsipDokumen extends Model
{
    use SoftDeletes;

    protected $table = 'arsip_dokumen';
    protected $guarded = ['id'];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * URL terproteksi (auth + role internal) untuk menampilkan arsip inline.
     * Dipakai sebagai pengganti tautan publik `/storage/...` (INF-01).
     */
    public function viewUrl(): string
    {
        return route('arsip.view', $this);
    }

    /**
     * URL terproteksi untuk mengunduh arsip (force download).
     */
    public function downloadUrl(): string
    {
        return route('arsip.download', $this);
    }
}
