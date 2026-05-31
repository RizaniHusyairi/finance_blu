<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BukuKasUmum extends Model
{
    use SoftDeletes;

    protected $table = 'buku_kas_umum';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'nominal' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
    ];

    /**
     * Hitung ulang kolom `saldo_akhir` untuk SELURUH baris satu rekening
     * secara KRONOLOGIS (tanggal_transaksi ASC, id ASC).
     *
     * Kenapa kronologis? Karena baris BKU ditulis oleh dua service independen
     * (BkuPostingService & PiutangSyncService) yang masing-masing menghitung
     * saldo dari "baris terakhir". Bila ada transaksi back-dated (mis. SP2D
     * yang tanggalnya lebih awal dari baris yang sudah ada), saldo berjalan
     * jadi salah. View index menampilkan baris urut (tanggal_transaksi, id),
     * jadi saldo tersimpan harus mengikuti urutan yang sama. Recompute ini
     * menjadi sumber kebenaran (source of truth) saldo berjalan.
     */
    public static function recalculateRunningBalance(int $rekeningId): void
    {
        // Saldo awal pembukuan rekening jadi titik mulai saldo berjalan. Bila
        // rekening belum di-set saldo awal, default 0 (perilaku lama).
        $rekening = RekeningBank::find($rekeningId);
        $running = (float) ($rekening?->saldo_awal ?? 0);
        $saldoAwalTanggal = $rekening?->saldo_awal_per_tanggal;

        // Ambil seluruh baris terurut KRONOLOGIS dalam satu query. Sengaja TIDAK
        // memakai chunkById(): kursor "id > lastId"-nya berasumsi urutan = id,
        // sehingga baris ber-id kecil tapi tanggal lebih awal (back-dated) bisa
        // terlewat begitu melewati batas chunk. Jumlah baris BKU per rekening
        // terbatas, jadi memuat sekaligus aman dan benar.
        $rows = static::query()
            ->where('sumber_rekening_id', $rekeningId)
            // Bila tanggal saldo awal di-set, baris pra-periode diabaikan dari
            // perhitungan (saldo awal sudah merangkum transaksi sebelum tanggal itu).
            ->when($saldoAwalTanggal, fn ($q) => $q->whereDate('tanggal_transaksi', '>=', $saldoAwalTanggal))
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $nominal = (float) $row->nominal;

            // DEBIT_MASUK menambah saldo, KREDIT_KELUAR mengurangi.
            $running += $row->arus_kas === 'DEBIT_MASUK' ? $nominal : -$nominal;

            $stored = (float) $row->saldo_akhir;

            // Hanya persist bila berbeda (epsilon compare untuk hindari selisih
            // pembulatan float) dan tanpa membump updated_at.
            if (abs($stored - $running) >= 0.005) {
                $row->saldo_akhir = $running;
                $row->saveQuietly();
            }
        }
    }

    public function sumberRekening()
    {
        return $this->belongsTo(RekeningBank::class, 'sumber_rekening_id');
    }

    public function referensiPengeluaran()
    {
        return $this->belongsTo(Tagihan::class, 'referensi_pengeluaran_id');
    }

    public function referensiPenerimaan()
    {
        return $this->belongsTo(TransaksiPenerimaan::class, 'referensi_penerimaan_id');
    }
}
