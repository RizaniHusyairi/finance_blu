<?php

namespace App\Http\Controllers;

use App\Models\AkunPendapatan;
use App\Models\DetailMutasiBank;
use App\Services\Pembukuan\PembukuanService;
use App\Services\Pembukuan\PostingPenerimaanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Klasifikasi baris rekening koran → akun pendapatan, lalu posting ke BKU
 * Penerimaan (model File 2). Sumber baris: impor koran pada Buku Pembantu Bank.
 */
class KlasifikasiPenerimaanController extends Controller
{
    public function __construct(
        private readonly PostingPenerimaanService $posting,
        private readonly PembukuanService $pembukuan,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['rekening_bank_id', 'start_date', 'end_date']);

        $rows = DetailMutasiBank::query()
            ->with(['akunPendapatan', 'bukuKasUmum', 'importMutasiBank.rekeningBank'])
            ->when($filters['rekening_bank_id'] ?? null,
                fn (Builder $q, $id) => $q->whereHas('importMutasiBank', fn (Builder $s) => $s->where('rekening_bank_id', $id)))
            ->when($filters['start_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_transaksi', '>=', $d))
            ->when($filters['end_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_transaksi', '<=', $d))
            ->orderBy('tanggal_transaksi')->orderBy('id')
            ->paginate(100)->withQueryString();

        return view('pembukuan.klasifikasi.index', [
            'rows' => $rows,
            'filters' => $filters,
            'akunOptions' => AkunPendapatan::orderBy('kode_akun')->orderBy('kode_jenis')->get(),
            'rekeningOptions' => $this->pembukuan->rekeningOptions(),
        ]);
    }

    public function postBatch(Request $request)
    {
        $validated = $request->validate([
            'rekening_bank_id' => ['required', 'integer', 'exists:rekening_bank,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        try {
            $res = $this->posting->postBatch($validated);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal posting massal: ' . $e->getMessage());
        }

        return back()->with('success', "Selesai: {$res['posted']} baris diposting, {$res['classified']} terklasifikasi otomatis, {$res['unclassified']} belum terklasifikasi (perlu set manual).");
    }

    /** Set akun pendapatan manual untuk satu baris koran (propagasi ke baris BKU bila sudah diposting). */
    public function updateAkun(Request $request, int $detail)
    {
        $validated = $request->validate([
            'akun_pendapatan_id' => ['nullable', 'integer', 'exists:akun_pendapatan,id'],
        ]);

        $row = DetailMutasiBank::with('bukuKasUmum')->findOrFail($detail);
        $row->akun_pendapatan_id = $validated['akun_pendapatan_id'] ?: null;
        $row->save();

        if ($row->bukuKasUmum) {
            $row->bukuKasUmum->akun_pendapatan_id = $row->akun_pendapatan_id;
            $row->bukuKasUmum->saveQuietly();
        }

        return back()->with('success', 'Akun pendapatan baris diperbarui.');
    }
}
