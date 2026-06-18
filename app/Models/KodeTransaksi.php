<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master kode transaksi SILABI (sheet `Referensi` File 1).
 *
 * `posting_rules` = daftar aturan distribusi ke buku:
 *   [ ['kode_buku' => 1, 'arah' => 'TERIMA', 'sign' => 1], ... ]
 * Dipakai PostingPembukuanService untuk men-generate baris BKU + buku pembantu
 * dari satu baris jurnal transaksi_pembukuan.
 */
class KodeTransaksi extends Model
{
    protected $table = 'kode_transaksi';
    protected $guarded = ['id'];

    protected $casts = [
        'posting_rules' => 'array',
        'status_aktif' => 'boolean',
    ];

    /** @return array<int, array{kode_buku:int, arah:string, sign:int}> */
    public function postings(): array
    {
        return $this->posting_rules ?? [];
    }
}
