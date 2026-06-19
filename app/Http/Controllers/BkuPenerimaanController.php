<?php

namespace App\Http\Controllers;

use App\Enums\JenisRekening;
use App\Enums\KodeBuku;
use App\Models\RekeningBank;
use App\Services\Pembukuan\BukuPembantuService;
use App\Services\Pembukuan\DokumenPembukuanService;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

/**
 * BKU Bendahara Penerimaan — partisi buku_kas_umum (peran=PENERIMAAN, buku=BKU),
 * sumbernya baris rekening koran terklasifikasi (Fase 3).
 */
class BkuPenerimaanController extends Controller
{
    /** Kolom yang boleh dijadikan kunci sortir tampilan. */
    private const SORTABLE = ['tanggal', 'kode', 'uraian', 'penerimaan', 'pengeluaran', 'saldo'];

    public function __construct(
        private readonly BukuPembantuService $bukuService,
        private readonly DokumenPembukuanService $dokumen,
        private readonly PembukuanService $pembukuan,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['rekening_bank_id', 'start_date', 'end_date', 'search', 'sort', 'dir']);

        $buku = $this->bukuService->buildBuku(KodeBuku::BKU->value, 'PENERIMAAN', $filters);

        // Pencarian level-tampilan: saring baris yang ditampilkan TANPA mengubah
        // saldo berjalan (yang dihitung atas ledger penuh) maupun ringkasan periode.
        $entries = $buku['entries'];
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $angka = preg_replace('/[^0-9]/', '', $search);

            $entries = $entries->filter(function ($e) use ($needle, $angka) {
                $akun = $e->akunPendapatan;
                $hay = mb_strtolower(trim(
                    ($e->uraian ?? '') . ' '
                    . ($akun->kode_gabungan ?? '') . ' ' . ($akun->uraian_jenis ?? '') . ' '
                    . ($e->jenis_transaksi?->label() ?? '') . ' ' . ($e->nomor_bukti ?? '')
                ));

                if ($needle !== '' && str_contains($hay, $needle)) {
                    return true;
                }

                return $angka !== '' && str_contains(preg_replace('/[^0-9]/', '', (string) $e->nominal), $angka);
            })->values();
        }

        // Sortir tampilan: tata ulang baris TANPA menghitung ulang saldo berjalan
        // (yang dianotasi kronologis di service) maupun ringkasan periode.
        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'tanggal';
        $dir = ($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $entries = $this->bukuService->sortEntries($entries, $sort, $dir);

        $data = [
            'buku' => $buku,
            'entries' => $entries,
            'filters' => $filters,
            'search' => $search,
            'sort' => $sort,
            'dir' => $dir,
            'rekening' => $this->resolvePenerimaanRekening(),
        ];

        return $request->boolean('partial')
            ? view('pembukuan.penerimaan._content', $data)
            : view('pembukuan.penerimaan.index', $data);
    }

    public function pdf(Request $request)
    {
        return $this->dokumen->streamBukuPdf(KodeBuku::BKU->value, 'PENERIMAAN',
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }

    public function excel(Request $request)
    {
        return $this->dokumen->streamBukuExcel(KodeBuku::BKU->value, 'PENERIMAAN',
            $request->only(['rekening_bank_id', 'start_date', 'end_date']));
    }

    /** Rekening Penerimaan aktif dari Setup (untuk header). */
    private function resolvePenerimaanRekening(): ?RekeningBank
    {
        return RekeningBank::query()
            ->where('status_aktif', true)
            ->where('jenis_rekening', JenisRekening::PENERIMAAN->value)
            ->orderByDesc('is_terkunci')->orderByDesc('is_default')->orderBy('id')
            ->first();
    }
}
