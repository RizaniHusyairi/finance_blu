<?php

namespace App\Http\Controllers;

use App\Enums\JenisRekening;
use App\Models\AkunPendapatan;
use App\Models\DetailMutasiBank;
use App\Models\ImportMutasiBank;
use App\Models\LayananJasa;
use App\Models\RekeningBank;
use App\Models\TagihanJasaDetail;
use App\Services\Pembukuan\CmsKoranImportService;
use App\Services\Pembukuan\PembukuanService;
use App\Services\Pembukuan\PostingPenerimaanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Klasifikasi baris rekening koran → akun pendapatan, lalu posting ke BKU
 * Penerimaan (model File 2). Sumber baris: impor koran pada Buku Pembantu Bank.
 */
class KlasifikasiPenerimaanController extends Controller
{
    public function __construct(
        private readonly PostingPenerimaanService $posting,
        private readonly PembukuanService $pembukuan,
        private readonly CmsKoranImportService $importer,
    ) {
    }

    /**
     * Impor file rekening koran format CMS BTN (.xls) → baris detail_mutasi_bank,
     * sumber data penyandingan rekonsiliasi dengan BKU Penerimaan. Rekening
     * dicocokkan otomatis dari nomor akun pada file (atau dipilih manual).
     */
    public function importKoran(Request $request)
    {
        $request->validate([
            'file_koran' => ['required', 'file', 'max:20480'],
            'rekening_bank_id' => ['nullable', 'integer', 'exists:rekening_bank,id'],
        ]);

        try {
            $res = $this->importer->import(
                $request->file('file_koran'),
                $request->integer('rekening_bank_id') ?: null,
                (int) auth()->id(),
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal impor rekening koran: ' . $e->getMessage());
        }

        // Semua baris ternyata SUDAH PERNAH diimpor (duplikat) → tidak ada data baru.
        if ($res['total'] === 0) {
            return redirect()
                ->route('pembukuan.klasifikasi.index')
                ->with('error', sprintf(
                    'Tidak ada data baru ditambahkan: seluruh %d baris pada berkas ini SUDAH PERNAH DIIMPOR sebelumnya '
                    . '(duplikat, dikenali dari nomor referensi bank yang sama).',
                    $res['duplikat'],
                ));
        }

        // Arahkan langsung ke berkas yang baru diimpor agar barisnya saja yang tampil.
        $pesanDup = $res['duplikat'] > 0
            ? sprintf(' %d baris dilewati karena sudah pernah diimpor (duplikat).', $res['duplikat'])
            : '';

        return redirect()
            ->route('pembukuan.klasifikasi.index', ['import_id' => $res['import_id']])
            ->with('success', sprintf(
                'Impor %s selesai: %d baris baru (%d masuk, %d keluar).%s Tabel difilter ke berkas ini. '
                . 'Lanjutkan dengan "Klasifikasi & Posting Massal".',
                $res['rekening_label'], $res['total'], $res['masuk'], $res['keluar'], $pesanDup,
            ));
    }

    public function index(Request $request)
    {
        // Selalu mengikuti rekening Penerimaan dari Setup (terpusat satu rekening),
        // tanpa pemilihan manual.
        $rekening = $this->resolvePenerimaanRekening();
        $filters = $request->only(['start_date', 'end_date', 'status', 'import_id', 'search']);

        // Query dasar: SEMUA filter KECUALI status. Dipakai untuk daftar baris dan
        // untuk menghitung jumlah per status (kartu statistik) pada cakupan yang sama.
        $base = fn () => DetailMutasiBank::query()
            ->when($rekening, fn (Builder $q) => $q->whereHas('importMutasiBank', fn (Builder $s) => $s->where('rekening_bank_id', $rekening->id)))
            ->when($filters['import_id'] ?? null, fn (Builder $q, $v) => $q->where('import_mutasi_bank_id', $v))
            ->when($filters['start_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_transaksi', '>=', $d))
            ->when($filters['end_date'] ?? null, fn (Builder $q, $d) => $q->whereDate('tanggal_transaksi', '<=', $d))
            ->when($filters['search'] ?? null, fn (Builder $q, $s) => $this->applySearch($q, (string) $s));

        $counts = [
            'total' => $base()->count(),
            'belum' => $base()->whereDoesntHave('bukuKasUmum')->count(),
            'terposting' => $base()->whereHas('bukuKasUmum', fn (Builder $s) => $s->whereNull('referensi_penerimaan_id'))->count(),
            'cocok' => $base()->whereHas('bukuKasUmum', fn (Builder $s) => $s->whereNotNull('referensi_penerimaan_id'))->count(),
        ];

        $rows = $base()
            ->with(['akunPendapatan', 'bukuKasUmum', 'importMutasiBank.rekeningBank'])
            ->when($filters['status'] ?? null, fn (Builder $q, $s) => $this->applyStatusFilter($q, (string) $s))
            ->orderBy('tanggal_transaksi')->orderBy('id')
            ->paginate(100)->withQueryString();

        $data = [
            'rows' => $rows,
            'counts' => $counts,
            'filters' => $filters,
            'rekening' => $rekening,
            'akunOptions' => $this->akunPendapatanDariLayanan(),
            // Daftar berkas impor (hanya untuk halaman penuh; tak perlu saat AJAX konten).
            'importOptions' => ($rekening && ! $request->boolean('partial'))
                ? ImportMutasiBank::where('rekening_bank_id', $rekening->id)
                    ->has('detailMutasiBanks') // sembunyikan impor kosong (mis. re-import duplikat)
                    ->withCount('detailMutasiBanks')
                    ->orderByDesc('uploaded_at')->orderByDesc('id')
                    ->get()
                : collect(),
        ];

        // Permintaan AJAX → kembalikan hanya konten (kartu statistik + tabel).
        return $request->boolean('partial')
            ? view('pembukuan.klasifikasi._content', $data)
            : view('pembukuan.klasifikasi.index', $data);
    }

    /** Filter baris koran berdasarkan status rekonsiliasi (diturunkan dari tautan BKU). */
    private function applyStatusFilter(Builder $q, string $status): Builder
    {
        return match ($status) {
            // Belum diposting ke BKU.
            'belum' => $q->whereDoesntHave('bukuKasUmum'),
            // Sudah diposting, belum tertaut tagihan/piutang.
            'terposting' => $q->whereHas('bukuKasUmum', fn (Builder $s) => $s->whereNull('referensi_penerimaan_id')),
            // Terposting & tertaut tagihan → terverifikasi.
            'cocok' => $q->whereHas('bukuKasUmum', fn (Builder $s) => $s->whereNotNull('referensi_penerimaan_id')),
            default => $q,
        };
    }

    /** Pencarian baris koran: deskripsi / nomor referensi; bila istilah berupa angka, cocokkan nominal. */
    private function applySearch(Builder $q, string $term): Builder
    {
        $term = trim($term);
        $angka = preg_replace('/[^0-9]/', '', $term);

        return $q->where(function (Builder $w) use ($term, $angka) {
            $w->where('deskripsi', 'like', "%{$term}%")
                ->orWhere('nomor_referensi_bank', 'like', "%{$term}%");
            if ($angka !== '') {
                $w->orWhere('kredit', $angka)->orWhere('debit', $angka);
            }
        });
    }

    /**
     * Opsi akun pendapatan diambil dari layanan jasa yang dipakai penagihan jasa:
     * tiap layanan (leaf, aktif) punya kode "kode_mak.kode_jenis_pembayaran" yang
     * setara CONCAT(akun_pendapatan.kode_akun,'.',kode_jenis). Digabung dengan kode
     * yang nyata muncul di tagihan_jasa_details. Hanya akun pendapatan yang punya
     * padanan layanan jasa yang ditampilkan (bukan seluruh master akun).
     *
     * Fallback: bila tak ada padanan sama sekali, tampilkan seluruh akun pendapatan
     * agar dropdown tetap dapat dipakai.
     *
     * @return \Illuminate\Support\Collection<int, AkunPendapatan>
     */
    private function akunPendapatanDariLayanan()
    {
        $tree = LayananJasa::query()
            ->get(['id', 'parent_id', 'nama_layanan', 'is_leaf', 'is_active', 'kode_mak', 'kode_jenis_pembayaran'])
            ->keyBy('id');

        // Nama "service group" = leluhur yang induknya root (level 1) — nama layanan
        // ringkas & bermakna di master (mis. "Jasa Pendaratan Pesawat Udara").
        $serviceName = function (LayananJasa $lj) use ($tree): ?string {
            $cur = $lj;
            $guard = 0;
            while ($cur && $cur->parent_id && $guard < 10) {
                $parent = $tree[$cur->parent_id] ?? null;
                if ($parent && $parent->parent_id === null) {
                    break;
                }
                $cur = $parent;
                $guard++;
            }

            return $cur?->nama_layanan;
        };

        // Peta kode "akun.jenis" → label layanan dari master layanan_jasas.
        $labelByKode = [];
        foreach ($tree as $lj) {
            if (! $lj->is_leaf || ! $lj->is_active) {
                continue;
            }
            $mak = trim((string) $lj->kode_mak);
            $jns = trim((string) $lj->kode_jenis_pembayaran);
            if ($mak === '' || $jns === '') {
                continue;
            }
            $labelByKode[$mak . '.' . $jns] ??= ($serviceName($lj) ?: $lj->nama_layanan);
        }

        // Jaring pengaman: kode yang nyata dipakai penagihan (label fallback nanti).
        foreach (TagihanJasaDetail::query()->whereNotNull('kode_akun')->where('kode_akun', '!=', '')->distinct()->pluck('kode_akun') as $k) {
            $labelByKode[$k] ??= null;
        }

        $kodeSet = array_keys($labelByKode);

        $akun = AkunPendapatan::query()
            ->when(! empty($kodeSet), fn ($q) => $q->whereIn(DB::raw("CONCAT(kode_akun, '.', kode_jenis)"), $kodeSet))
            ->orderBy('kode_akun')->orderBy('kode_jenis')
            ->get();

        // Label tiap opsi diambil dari master layanan jasa (fallback uraian_jenis akun).
        return $akun->each(function (AkunPendapatan $a) use ($labelByKode) {
            $kode = $a->kode_akun . '.' . $a->kode_jenis;
            $a->setAttribute('layanan_label', $labelByKode[$kode] ?: $a->uraian_jenis);
        });
    }

    /** Rekening Penerimaan aktif tunggal dari Setup (utamakan terkunci/default). */
    private function resolvePenerimaanRekening(): ?RekeningBank
    {
        return RekeningBank::query()
            ->where('status_aktif', true)
            ->where('jenis_rekening', JenisRekening::PENERIMAAN->value)
            ->orderByDesc('is_terkunci')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public function postBatch(Request $request)
    {
        $validated = $request->validate([
            'rekening_bank_id' => ['required', 'integer', 'exists:rekening_bank,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'import_id' => ['nullable', 'integer', 'exists:import_mutasi_bank,id'],
        ]);

        try {
            $res = $this->posting->postBatch($validated);
        } catch (\Throwable $e) {
            $msg = 'Gagal posting massal: ' . $e->getMessage();

            return $request->ajax() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        $msg = "Selesai: {$res['posted']} baris diposting, {$res['classified']} terklasifikasi otomatis, {$res['unclassified']} belum terklasifikasi (perlu set manual).";

        return $request->ajax() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('success', $msg);
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

        $msg = 'Akun pendapatan baris diperbarui.';

        return $request->ajax() ? response()->json(['success' => true, 'message' => $msg]) : back()->with('success', $msg);
    }
}
