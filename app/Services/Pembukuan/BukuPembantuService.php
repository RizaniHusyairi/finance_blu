<?php

namespace App\Services\Pembukuan;

use App\Enums\KodeBuku;
use App\Models\BukuKasUmum;
use App\Models\RekeningBank;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lapisan baca "buku pembantu sebagai partisi BKU".
 *
 * Semua buku (BKU + 11 buku pembantu) adalah partisi `buku_kas_umum` yang
 * difilter (peran, kode_buku). Service ini menghasilkan tampilan ledger baku
 * (Saldo Awal, baris + saldo berjalan, Saldo Akhir, total terima/keluar) untuk
 * buku mana pun, plus pemeriksaan invariant kas Saldo BKU = Tunai + Bank.
 *
 * Saldo berjalan dihitung dari seed (pembukuan_saldo_awal) + akumulasi mutasi,
 * sehingga benar walau stored saldo_akhir tercampur antar-rekening.
 */
class BukuPembantuService
{
    /**
     * Tampilan satu buku untuk satu peran & (opsional) rekening + periode.
     *
     * @return array{kode_buku:int, nama_buku:string, peran:string, saldo_awal:float,
     *               saldo_akhir:float, total_terima:float, total_keluar:float,
     *               jumlah_transaksi:int, entries:\Illuminate\Support\Collection}
     */
    public function buildBuku(int $kodeBuku, string $peran, array $filters = []): array
    {
        $rekeningId = $filters['rekening_bank_id'] ?? null;
        $start = $this->date($filters['start_date'] ?? null);
        $end = $this->date($filters['end_date'] ?? null);

        // Saldo awal periode:
        //  - dengan start_date → saldo s.d. (start - 1 hari) = seed + mutasi pra-periode;
        //  - tanpa start_date  → cukup seed (semua mutasi ditampilkan & diakumulasi di loop).
        if ($start) {
            $saldoAwal = $this->saldoSampai($kodeBuku, $peran, $rekeningId, Carbon::parse($start)->subDay()->toDateString());
        } else {
            $rekening = $rekeningId ? RekeningBank::find($rekeningId) : null;
            [$saldoAwal] = BukuKasUmum::saldoAwalSeed($rekening, $peran, $kodeBuku);
        }

        $entries = $this->baseQuery($kodeBuku, $peran, $rekeningId)
            ->with(['sumberRekening', 'akunPendapatan', 'transaksiPembukuan', 'referensiPengeluaran', 'referensiPenerimaan'])
            ->when($start, fn (Builder $q) => $q->whereDate('tanggal_transaksi', '>=', $start))
            ->when($end, fn (Builder $q) => $q->whereDate('tanggal_transaksi', '<=', $end))
            ->orderBy('tanggal_transaksi')->orderBy('id')
            ->get();

        // Anotasi saldo berjalan per baris (transient) mulai dari saldo awal periode.
        $running = $saldoAwal;
        $totalTerima = 0.0;
        $totalKeluar = 0.0;
        foreach ($entries as $e) {
            $terima = $e->arus_kas === 'DEBIT_MASUK' ? (float) $e->nominal : 0.0;
            $keluar = $e->arus_kas === 'KREDIT_KELUAR' ? (float) $e->nominal : 0.0;
            $running += $terima - $keluar;
            $totalTerima += $terima;
            $totalKeluar += $keluar;
            $e->saldo_berjalan = $running;
        }

        return [
            'kode_buku' => $kodeBuku,
            'nama_buku' => KodeBuku::tryFrom($kodeBuku)?->label() ?? ('Buku ' . $kodeBuku),
            'peran' => $peran,
            'saldo_awal' => $saldoAwal,
            'saldo_akhir' => $running,
            'total_terima' => $totalTerima,
            'total_keluar' => $totalKeluar,
            'jumlah_transaksi' => $entries->count(),
            'entries' => $entries,
        ];
    }

    /**
     * Sortir koleksi entri ledger untuk TAMPILAN saja.
     *
     * saldo_berjalan sudah dianotasi {@see buildBuku()} dalam urutan kronologis
     * dan tetap valid per baris; sortir di sini hanya menata ulang baris yang
     * tampil — tidak menghitung ulang saldo, total, maupun ringkasan periode.
     *
     * @param  \Illuminate\Support\Collection  $entries  entri yang sudah ber-saldo_berjalan
     * @return \Illuminate\Support\Collection
     */
    public function sortEntries($entries, ?string $sort, ?string $dir = 'asc')
    {
        $desc = strtolower((string) $dir) === 'desc';

        $accessor = match ($sort) {
            'kode' => static fn ($e) => $e->akunPendapatan->kode_gabungan ?? $e->kode_transaksi ?? '',
            'uraian' => static fn ($e) => mb_strtolower((string) $e->uraian),
            'penerimaan' => static fn ($e) => $e->arus_kas === 'DEBIT_MASUK' ? (float) $e->nominal : 0.0,
            'pengeluaran' => static fn ($e) => $e->arus_kas === 'KREDIT_KELUAR' ? (float) $e->nominal : 0.0,
            'saldo' => static fn ($e) => (float) ($e->saldo_berjalan ?? 0),
            default => null, // 'tanggal' atau tak dikenal → pakai urutan kronologis bawaan
        };

        // sortBy stabil (PHP 8): baris dengan kunci sama mempertahankan urutan
        // kronologis (tanggal, id) sehingga tetap rapi sebagai tie-breaker.
        if ($accessor === null) {
            return ($desc ? $entries->reverse() : $entries)->values();
        }

        return $entries->sortBy($accessor, SORT_REGULAR, $desc)->values();
    }

    /**
     * Invariant kas: Saldo BKU (1) harus = Saldo Kas Tunai (2) + Saldo Kas Bank (3).
     *
     * @return array{saldo_bku:float, saldo_tunai:float, saldo_bank:float, selisih:float, seimbang:bool}
     */
    public function invariant(string $peran, array $filters = []): array
    {
        $rekeningId = $filters['rekening_bank_id'] ?? null;
        $asOf = $this->date($filters['end_date'] ?? null);

        $bku = $this->saldoSampai(KodeBuku::BKU->value, $peran, $rekeningId, $asOf);
        $tunai = $this->saldoSampai(KodeBuku::KAS_TUNAI->value, $peran, $rekeningId, $asOf);
        $bank = $this->saldoSampai(KodeBuku::BANK->value, $peran, $rekeningId, $asOf);

        $selisih = round($bku - ($tunai + $bank), 2);

        return [
            'saldo_bku' => $bku,
            'saldo_tunai' => $tunai,
            'saldo_bank' => $bank,
            'selisih' => $selisih,
            'seimbang' => abs($selisih) < 0.005,
        ];
    }

    /**
     * Saldo satu buku s.d. tanggal tertentu (inklusif) = seed + Σ(terima - keluar).
     * Tanpa $asOf = saldo akhir keseluruhan.
     */
    public function saldoSampai(int $kodeBuku, string $peran, ?int $rekeningId, ?string $asOf): float
    {
        $rekening = $rekeningId ? RekeningBank::find($rekeningId) : null;
        [$seed, $seedTanggal] = BukuKasUmum::saldoAwalSeed($rekening, $peran, $kodeBuku);

        $mutasi = $this->baseQuery($kodeBuku, $peran, $rekeningId)
            ->when($seedTanggal, fn (Builder $q) => $q->whereDate('tanggal_transaksi', '>=', $seedTanggal))
            ->when($asOf, fn (Builder $q) => $q->whereDate('tanggal_transaksi', '<=', $asOf))
            ->selectRaw("COALESCE(SUM(CASE WHEN arus_kas='DEBIT_MASUK' THEN nominal ELSE -nominal END),0) AS net")
            ->value('net');

        return round($seed + (float) $mutasi, 2);
    }

    private function baseQuery(int $kodeBuku, string $peran, ?int $rekeningId): Builder
    {
        return BukuKasUmum::query()
            ->where('peran', $peran)
            ->where('kode_buku', $kodeBuku)
            ->when($rekeningId, fn (Builder $q) => $q->where('sumber_rekening_id', $rekeningId));
    }

    private function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
