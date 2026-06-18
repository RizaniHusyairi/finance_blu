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
        'kode_buku' => 'integer',
    ];

    /**
     * Hitung ulang `saldo_akhir` secara KRONOLOGIS (tanggal_transaksi ASC, id ASC),
     * per kombinasi (rekening, PERAN, kode_buku).
     *
     * Model SILABI memisahkan dua BKU (Penerimaan vs Pengeluaran) dan banyak buku
     * pembantu (Tunai, Bank, …) — masing-masing punya saldo berjalan sendiri. Maka
     * recompute dijalankan per (peran, kode_buku) dalam satu rekening, bukan dicampur.
     *
     * Kenapa kronologis? Baris BKU ditulis beberapa service independen yang
     * mengestimasi saldo dari "baris terakhir"; transaksi back-dated bisa membuat
     * saldo salah. Recompute ini jadi sumber kebenaran. Backward-compatible:
     * data lama (kode_buku=1) berperilaku sama seperti sebelumnya.
     *
     * @param int      $rekeningId rekening sumber
     * @param int|null $kodeBuku   batasi ke satu buku; null = semua buku rekening
     */
    public static function recalculateRunningBalance(int $rekeningId, ?int $kodeBuku = null): void
    {
        $rekening = RekeningBank::find($rekeningId);

        // Kombinasi (peran, kode_buku) yang ada untuk rekening ini.
        $combos = static::query()
            ->where('sumber_rekening_id', $rekeningId)
            ->when($kodeBuku !== null, fn ($q) => $q->where('kode_buku', $kodeBuku))
            ->select('peran', 'kode_buku')
            ->distinct()
            ->get();

        foreach ($combos as $combo) {
            $peran = $combo->peran ?? 'PENGELUARAN';
            $buku = (int) ($combo->kode_buku ?? 1);

            [$running, $saldoAwalTanggal] = static::saldoAwalSeed($rekening, $peran, $buku);

            $rows = static::query()
                ->where('sumber_rekening_id', $rekeningId)
                ->where('peran', $peran)
                ->where('kode_buku', $buku)
                ->when($saldoAwalTanggal, fn ($q) => $q->whereDate('tanggal_transaksi', '>=', $saldoAwalTanggal))
                ->orderBy('tanggal_transaksi')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $nominal = (float) $row->nominal;

                // DEBIT_MASUK menambah saldo, KREDIT_KELUAR mengurangi.
                $running += $row->arus_kas === 'DEBIT_MASUK' ? $nominal : -$nominal;

                if (abs((float) $row->saldo_akhir - $running) >= 0.005) {
                    $row->saldo_akhir = $running;
                    $row->saveQuietly(); // jangan bump updated_at
                }
            }
        }
    }

    /**
     * Titik mulai saldo berjalan untuk satu (rekening, peran, kode_buku):
     * prioritas baris pembukuan_saldo_awal; fallback kolom rekening.saldo_awal
     * (hanya untuk BKU peran asli rekening — kompat data lama).
     *
     * @return array{0: float, 1: string|null}  [saldo awal, tanggal mulai]
     */
    public static function saldoAwalSeed(?RekeningBank $rekening, string $peran, int $kodeBuku): array
    {
        $sa = PembukuanSaldoAwal::query()
            ->where('rekening_bank_id', $rekening?->id)
            ->where('kode_buku', $kodeBuku)
            ->where('peran', $peran)
            ->orderByDesc('tanggal_berlaku')
            ->first();

        if ($sa) {
            return [(float) $sa->nominal, optional($sa->tanggal_berlaku)->toDateString()];
        }

        $nativePeran = ($rekening?->jenis_rekening?->value === 'PENERIMAAN') ? 'PENERIMAAN' : 'PENGELUARAN';
        if ($kodeBuku === 1 && $peran === $nativePeran) {
            return [(float) ($rekening?->saldo_awal ?? 0), $rekening?->saldo_awal_per_tanggal];
        }

        return [0.0, null];
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

    public function rekonsiliasiBanks()
    {
        return $this->hasMany(RekonsiliasiBank::class, 'bku_id');
    }

    public function akunPendapatan()
    {
        return $this->belongsTo(AkunPendapatan::class, 'akun_pendapatan_id');
    }

    public function transaksiPembukuan()
    {
        return $this->belongsTo(TransaksiPembukuan::class, 'transaksi_pembukuan_id');
    }

    public function detailMutasiBank()
    {
        return $this->belongsTo(DetailMutasiBank::class, 'detail_mutasi_bank_id');
    }

    public function kodeTransaksiRef()
    {
        return $this->belongsTo(KodeTransaksi::class, 'kode_transaksi', 'kode');
    }
}
