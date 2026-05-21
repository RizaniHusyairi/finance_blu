<?php

namespace App\Services\Pembukuan;

use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\ImportMutasiBank;
use App\Models\LaporanPengesahanBlu;
use App\Models\PotonganTagihan;
use App\Models\RealisasiAnggaran;
use App\Models\RekonsiliasiBank;
use App\Models\RekeningBank;
use App\Models\Tagihan;
use App\Models\TransaksiPenerimaan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PembukuanService
{
    public function monthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    public function rekeningOptions(): Collection
    {
        return RekeningBank::query()
            ->where('status_aktif', true)
            ->orderBy('nama_bank')
            ->orderBy('nomor_rekening')
            ->get();
    }

    public function buildBkuIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = BukuKasUmum::query()
            ->with([
                'sumberRekening',
                'referensiPengeluaran.pihak',
                'referensiPengeluaran.spps.spm.npi.sp2d',
                'referensiPenerimaan.mitra',
            ]);

        $this->applyBkuFilters($query, $filters);

        $entries = $query
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();

        return [
            'filters' => $filters,
            'rekeningOptions' => $this->rekeningOptions(),
            'entries' => $entries,
            'summary' => [
                'total_debit' => (clone $query)->where('arus_kas', 'DEBIT_MASUK')->sum('nominal'),
                'total_kredit' => (clone $query)->where('arus_kas', 'KREDIT_KELUAR')->sum('nominal'),
                'saldo_akhir' => optional($entries->last())->saldo_akhir ?? 0,
                'jumlah_transaksi' => $entries->count(),
            ],
        ];
    }

    public function buildBkuDetail(BukuKasUmum $bku): array
    {
        $bku->loadMissing([
            'sumberRekening.pemilik',
            'referensiPengeluaran.pihak',
            'referensiPengeluaran.creator',
            'referensiPengeluaran.logs.user',
            'referensiPengeluaran.potonganTagihan.pajak',
            'referensiPengeluaran.spps.spm.npi.sp2d',
            'referensiPenerimaan.mitra',
            'referensiPenerimaan.coa',
        ]);

        $tagihan = $bku->referensiPengeluaran;
        $penerimaan = $bku->referensiPenerimaan;
        $docChain = $this->documentChain($tagihan);

        $relatedLogs = collect();
        if ($tagihan) {
            $relatedLogs = $relatedLogs
                ->merge($tagihan->logs ?? collect())
                ->merge($docChain['sp2d']?->logs ?? collect());
        }

        return [
            'entry' => $bku,
            'tagihan' => $tagihan,
            'penerimaan' => $penerimaan,
            'docChain' => $docChain,
            'relatedLogs' => $relatedLogs
                ->filter()
                ->sortByDesc('created_at')
                ->values(),
        ];
    }

    public function buildBankIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $rekeningList = RekeningBank::query()
            ->where('status_aktif', true)
            ->when($filters['rekening_bank_id'], fn (Builder $query) => $query->whereKey($filters['rekening_bank_id']))
            ->orderBy('nama_bank')
            ->orderBy('nomor_rekening')
            ->get();

        $mutasiBase = DetailMutasiBank::query()
            ->join('import_mutasi_bank', 'import_mutasi_bank.id', '=', 'detail_mutasi_bank.import_mutasi_bank_id');

        $this->applyMutasiFilters($mutasiBase, $filters, 'detail_mutasi_bank.tanggal_transaksi');

        $mutasiCounts = (clone $mutasiBase)
            ->selectRaw('import_mutasi_bank.rekening_bank_id, COUNT(*) as jumlah_mutasi')
            ->groupBy('import_mutasi_bank.rekening_bank_id')
            ->pluck('jumlah_mutasi', 'import_mutasi_bank.rekening_bank_id');

        $latestStatuses = (clone $mutasiBase)
            ->select([
                'import_mutasi_bank.rekening_bank_id',
                'detail_mutasi_bank.status_rekonsiliasi',
                'detail_mutasi_bank.tanggal_transaksi',
                'detail_mutasi_bank.id',
            ])
            ->orderByDesc('detail_mutasi_bank.tanggal_transaksi')
            ->orderByDesc('detail_mutasi_bank.id')
            ->get()
            ->groupBy('rekening_bank_id')
            ->map(fn (Collection $items) => optional($items->first())->status_rekonsiliasi);

        $rekeningList->each(function (RekeningBank $rekening) use ($mutasiCounts, $latestStatuses) {
            $rekening->jumlah_mutasi = (int) ($mutasiCounts[$rekening->id] ?? 0);
            $rekening->status_rekonsiliasi_terakhir = $latestStatuses[$rekening->id] ?? 'BELUM';
        });

        return [
            'filters' => $filters,
            'rekeningList' => $rekeningList,
            'rekeningOptions' => $this->rekeningOptions(),
            'summary' => [
                'rekening_aktif' => RekeningBank::query()->where('status_aktif', true)->count(),
                'belum' => (clone $mutasiBase)->where('detail_mutasi_bank.status_rekonsiliasi', 'BELUM')->count(),
                'matched' => (clone $mutasiBase)->where('detail_mutasi_bank.status_rekonsiliasi', 'MATCHED')->count(),
                'selisih' => (clone $mutasiBase)->where('detail_mutasi_bank.status_rekonsiliasi', 'SELISIH')->count(),
            ],
        ];
    }

    public function buildBankMutasiData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = DetailMutasiBank::query()
            ->with([
                'importMutasiBank.rekeningBank',
                'rekonsiliasiBanks.direkonsiliasiOleh',
            ]);

        $this->applyMutasiFilters($query, $filters);

        $mutasi = $query
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->get();

        return [
            'filters' => $filters,
            'rekeningOptions' => $this->rekeningOptions(),
            'mutasi' => $mutasi,
            'summary' => [
                'jumlah_mutasi' => $mutasi->count(),
                'debit' => $mutasi->sum(fn (DetailMutasiBank $item) => (float) $item->debit),
                'kredit' => $mutasi->sum(fn (DetailMutasiBank $item) => (float) $item->kredit),
                'matched' => $mutasi->where('status_rekonsiliasi', 'MATCHED')->count(),
            ],
        ];
    }

    public function buildBankRekeningDetail(RekeningBank $rekening, array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $rekening->loadMissing('pemilik');

        $query = DetailMutasiBank::query()
            ->with([
                'importMutasiBank.rekeningBank',
                'rekonsiliasiBanks.bku',
                'rekonsiliasiBanks.tagihan',
                'rekonsiliasiBanks.transaksiPenerimaan',
                'rekonsiliasiBanks.direkonsiliasiOleh',
            ])
            ->whereHas('importMutasiBank', fn (Builder $q) => $q->where('rekening_bank_id', $rekening->id));

        $this->applyMutasiFilters($query, $filters);

        $mutasi = $query
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->get();

        return [
            'rekening' => $rekening,
            'filters' => $filters,
            'rekeningOptions' => $this->rekeningOptions(),
            'mutasi' => $mutasi,
            'summary' => [
                'jumlah_mutasi' => $mutasi->count(),
                'total_masuk' => $mutasi->sum(fn (DetailMutasiBank $item) => (float) $item->debit),
                'total_keluar' => $mutasi->sum(fn (DetailMutasiBank $item) => (float) $item->kredit),
                'matched' => $mutasi->where('status_rekonsiliasi', 'MATCHED')->count(),
            ],
        ];
    }

    public function buildBankReconciliationData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = RekonsiliasiBank::query()
            ->with([
                'detailMutasiBank.importMutasiBank.rekeningBank',
                'bku.sumberRekening',
                'tagihan.pihak',
                'transaksiPenerimaan.mitra',
                'direkonsiliasiOleh',
                'logs.user',
            ]);

        $this->applyReconciliationFilters($query, $filters);

        $reconciliations = $query
            ->orderByDesc('direkonsiliasi_pada')
            ->orderByDesc('id')
            ->get();

        return [
            'filters' => $filters,
            'rekeningOptions' => $this->rekeningOptions(),
            'reconciliations' => $reconciliations,
            'summary' => [
                'matched' => $reconciliations->where('status', 'MATCHED')->count(),
                'partial' => $reconciliations->where('status', 'PARTIAL')->count(),
                'selisih' => $reconciliations->where('status', 'SELISIH')->count(),
                'manual' => $reconciliations->where('status', 'MANUAL_OVERRIDE')->count(),
            ],
        ];
    }

    public function buildBendaharaIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = BukuKasUmum::query()
            ->with([
                'sumberRekening',
                'referensiPengeluaran.pihak',
                'referensiPengeluaran.spps.spm.npi.sp2d',
                'referensiPenerimaan.mitra',
            ]);

        $this->applyBendaharaFilters($query, $filters);

        $entries = $query
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();

        $openingQuery = BukuKasUmum::query();
        if ($filters['rekening_bank_id']) {
            $openingQuery->where('sumber_rekening_id', $filters['rekening_bank_id']);
        }
        if ($filters['start_date']) {
            $openingQuery->whereDate('tanggal_transaksi', '<', $filters['start_date']);
        }

        $saldoAwal = $filters['start_date']
            ? (optional($openingQuery->orderByDesc('tanggal_transaksi')->orderByDesc('id')->first())->saldo_akhir ?? 0)
            : 0;

        return [
            'filters' => $filters,
            'rekeningOptions' => $this->rekeningOptions(),
            'tagihanOptions' => Tagihan::query()->orderByDesc('created_at')->limit(200)->get(['id', 'nomor_tagihan']),
            'entries' => $entries,
            'summary' => [
                'saldo_awal' => $saldoAwal,
                'total_penerimaan' => $entries->where('arus_kas', 'DEBIT_MASUK')->sum('nominal'),
                'total_pengeluaran' => $entries->where('arus_kas', 'KREDIT_KELUAR')->sum('nominal'),
                'saldo_akhir' => optional($entries->last())->saldo_akhir ?? $saldoAwal,
            ],
        ];
    }

    public function buildBungaIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = DetailMutasiBank::query()
            ->with([
                'importMutasiBank.rekeningBank',
                'rekonsiliasiBanks',
            ]);

        $this->applyMutasiFilters($query, $filters);
        $this->applyBungaFilter($query);

        // Urut naik supaya saldo berjalan bisa dihitung secara kumulatif.
        $entries = $query
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();

        // Saldo awal = akumulasi (penerimaan - pengeluaran) seluruh transaksi bunga
        // sebelum start_date (tanpa filter start_date).
        $openingQuery = DetailMutasiBank::query();
        $openingFilters = $filters;
        $openingFilters['start_date'] = null;
        $openingFilters['end_date'] = null;
        $this->applyMutasiFilters($openingQuery, $openingFilters);
        $this->applyBungaFilter($openingQuery);

        if ($filters['start_date']) {
            $openingQuery->whereDate('tanggal_transaksi', '<', $filters['start_date']);
        }

        $saldoAwal = (float) ($openingQuery->sum(DB::raw('COALESCE(debit,0) - COALESCE(kredit,0)')));

        // Anotasi saldo berjalan per baris (cumulative). Simpan pada atribut transient.
        $saldoBerjalan = $saldoAwal;
        $totalPenerimaan = 0.0;
        $totalPengeluaran = 0.0;

        foreach ($entries as $entry) {
            $penerimaan = (float) $entry->debit;
            $pengeluaran = (float) $entry->kredit;

            $saldoBerjalan += $penerimaan - $pengeluaran;
            $totalPenerimaan += $penerimaan;
            $totalPengeluaran += $pengeluaran;

            // Atribut transient non-persisten — dipakai view saja.
            $entry->nominal_penerimaan = $penerimaan;
            $entry->nominal_pengeluaran = $pengeluaran;
            $entry->saldo_berjalan = $saldoBerjalan;
        }

        $saldoAkhir = $saldoBerjalan;

        $matchedBkuMap = $this->mapBungaToBku($entries);

        // Summary bulan berjalan & tahun berjalan tetap berbasis net penerimaan bunga.
        $baseSummaryQuery = DetailMutasiBank::query()
            ->join('import_mutasi_bank', 'import_mutasi_bank.id', '=', 'detail_mutasi_bank.import_mutasi_bank_id');

        if ($filters['rekening_bank_id']) {
            $baseSummaryQuery->where('import_mutasi_bank.rekening_bank_id', $filters['rekening_bank_id']);
        }

        $this->applyBungaFilter($baseSummaryQuery);

        $monthStart = now()->startOfMonth()->toDateString();
        $yearStart = now()->startOfYear()->toDateString();

        return [
            'filters' => $filters,
            'rekeningOptions' => $this->rekeningOptions(),
            'entries' => $entries,
            'matchedBkuMap' => $matchedBkuMap,
            'summary' => [
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $saldoAkhir,
                'total_penerimaan' => $totalPenerimaan,
                'total_pengeluaran' => $totalPengeluaran,
                'bulan_ini' => (clone $baseSummaryQuery)
                    ->whereDate('detail_mutasi_bank.tanggal_transaksi', '>=', $monthStart)
                    ->sum(DB::raw('detail_mutasi_bank.debit')),
                'tahun_berjalan' => (clone $baseSummaryQuery)
                    ->whereDate('detail_mutasi_bank.tanggal_transaksi', '>=', $yearStart)
                    ->sum(DB::raw('detail_mutasi_bank.debit')),
                'jumlah_transaksi' => $entries->count(),
            ],
        ];
    }

    public function buildPajakIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = PotonganTagihan::query()
            ->with([
                'tagihan.pihak',
                'tagihan.spps.spm.npi.sp2d',
                'pajak',
                'arsipDokumen',
            ]);

        $this->applyPajakFilters($query, $filters);

        $entries = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return [
            'filters' => $filters,
            'entries' => $entries,
            'summary' => [
                'total_potongan' => $entries->sum('nominal_potongan'),
                'sudah_billing' => $entries->whereNotNull('kode_billing')->sum('nominal_potongan'),
                'sudah_setor' => $entries->whereNotNull('ntpn')->sum('nominal_potongan'),
                'belum_setor' => $entries->whereNull('ntpn')->sum('nominal_potongan'),
            ],
            'jenisTagihanOptions' => ['PERJALDIN', 'KONTRAK', 'HONORARIUM'],
        ];
    }

    public function buildPajakDetail(PotonganTagihan $potongan): array
    {
        $potongan->loadMissing([
            'tagihan.pihak',
            'tagihan.creator',
            'tagihan.logs.user',
            'tagihan.spps.spm.npi.sp2d.logs.user',
            'pajak',
            'akunPotongan',
            'arsipDokumen.uploader',
        ]);

        $tagihan = $potongan->tagihan;
        $docChain = $this->documentChain($tagihan);

        $bkuEntries = BukuKasUmum::query()
            ->with('sumberRekening')
            ->where('referensi_pengeluaran_id', $tagihan?->id)
            ->orderBy('tanggal_transaksi')
            ->get();

        $rekonsiliasi = RekonsiliasiBank::query()
            ->with([
                'detailMutasiBank.importMutasiBank.rekeningBank',
                'direkonsiliasiOleh',
            ])
            ->where('tagihan_id', $tagihan?->id)
            ->orderByDesc('direkonsiliasi_pada')
            ->get();

        $statusBku = $bkuEntries->isNotEmpty() ? 'SUDAH_MASUK_BKU' : 'BELUM_MASUK_BKU';
        $statusRekonsiliasi = $rekonsiliasi->isNotEmpty()
            ? ($rekonsiliasi->first()->status ?? 'BELUM')
            : 'BELUM';

        return [
            'potongan' => $potongan,
            'tagihan' => $tagihan,
            'docChain' => $docChain,
            'bkuEntries' => $bkuEntries,
            'rekonsiliasi' => $rekonsiliasi,
            'statusBku' => $statusBku,
            'statusRekonsiliasi' => $statusRekonsiliasi,
        ];
    }

    public function buildPengesahanIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = LaporanPengesahanBlu::query()
            ->with('approver')
            ->when($filters['bulan'], fn (Builder $q) => $q->where('periode_bulan', $filters['bulan']))
            ->when($filters['tahun'], fn (Builder $q) => $q->where('tahun', $filters['tahun']))
            ->when($filters['status_pengesahan'], fn (Builder $q) => $q->where('status_pengesahan', $filters['status_pengesahan']));

        $reports = $query
            ->orderByDesc('tahun')
            ->orderByDesc('periode_bulan')
            ->get();

        return [
            'filters' => $filters,
            'reports' => $reports,
            'months' => $this->monthOptions(),
            'summary' => [
                'total_laporan' => $reports->count(),
                'draft' => $reports->where('status_pengesahan', 'DRAFT')->count(),
                'verifikasi_kppn' => $reports->where('status_pengesahan', 'VERIFIKASI_KPPN')->count(),
                'disahkan' => $reports->where('status_pengesahan', 'DISAHKAN')->count(),
            ],
        ];
    }

    public function buildPengesahanDetail(LaporanPengesahanBlu $laporan): array
    {
        $laporan->loadMissing([
            'approver.profilable',
            'arsipDokumen.uploader',
        ]);

        $start = Carbon::create($laporan->tahun, $laporan->periode_bulan, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $sourceSummary = [
            'bku' => [
                'count' => BukuKasUmum::query()
                    ->whereBetween('tanggal_transaksi', [$start->toDateString(), $end->toDateString()])
                    ->count(),
                'total' => BukuKasUmum::query()
                    ->whereBetween('tanggal_transaksi', [$start->toDateString(), $end->toDateString()])
                    ->sum('nominal'),
            ],
            'realisasi' => [
                'count' => RealisasiAnggaran::query()
                    ->whereBetween('tanggal_pencairan', [$start->toDateString(), $end->toDateString()])
                    ->count(),
                'total' => RealisasiAnggaran::query()
                    ->whereBetween('tanggal_pencairan', [$start->toDateString(), $end->toDateString()])
                    ->sum('nominal_cair'),
            ],
            'pajak' => [
                'count' => PotonganTagihan::query()
                    ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->count(),
                'total' => PotonganTagihan::query()
                    ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->sum('nominal_potongan'),
            ],
            'mutasi_bank' => [
                'count' => DetailMutasiBank::query()
                    ->whereBetween('tanggal_transaksi', [$start->toDateString(), $end->toDateString()])
                    ->count(),
                'total' => DetailMutasiBank::query()
                    ->whereBetween('tanggal_transaksi', [$start->toDateString(), $end->toDateString()])
                    ->sum(DB::raw('debit + kredit')),
            ],
        ];

        $timeline = collect([
            [
                'label' => 'Laporan dibuat',
                'description' => 'Dokumen pengesahan BLU dibuat di sistem.',
                'time' => $laporan->created_at,
                'actor' => 'Sistem',
            ],
            [
                'label' => 'Pembaruan terakhir',
                'description' => 'Metadata laporan diperbarui terakhir kali.',
                'time' => $laporan->updated_at,
                'actor' => 'Sistem',
            ],
            $laporan->approver ? [
                'label' => 'Approver tercatat',
                'description' => 'Laporan memiliki approver / KPA terkait.',
                'time' => $laporan->updated_at,
                'actor' => $laporan->approver->name,
            ] : null,
        ])
            ->filter()
            ->sortBy('time')
            ->values();

        return [
            'report' => $laporan,
            'months' => $this->monthOptions(),
            'periodStart' => $start,
            'periodEnd' => $end,
            'sourceSummary' => $sourceSummary,
            'timeline' => $timeline,
        ];
    }

    public function streamPdf(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'landscape')
    {
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper($paper, $orientation);

        return $pdf->stream($filename);
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'start_date' => $this->normalizeDate($filters['start_date'] ?? null),
            'end_date' => $this->normalizeDate($filters['end_date'] ?? null),
            'rekening_bank_id' => $filters['rekening_bank_id'] ?? null,
            'arus_kas' => $filters['arus_kas'] ?? null,
            'sumber_transaksi' => $filters['sumber_transaksi'] ?? null,
            'arah_mutasi' => $filters['arah_mutasi'] ?? null,
            'status_rekonsiliasi' => $filters['status_rekonsiliasi'] ?? null,
            'status' => $filters['status'] ?? null,
            'jenis_transaksi' => $filters['jenis_transaksi'] ?? null,
            'tagihan_id' => $filters['tagihan_id'] ?? null,
            'mekanisme_pembayaran' => $filters['mekanisme_pembayaran'] ?? null,
            'jenis_tagihan' => $filters['jenis_tagihan'] ?? null,
            'jenis_pajak' => $filters['jenis_pajak'] ?? null,
            'status_billing_setor' => $filters['status_billing_setor'] ?? null,
            'bulan' => $filters['bulan'] ?? null,
            'tahun' => $filters['tahun'] ?? null,
            'status_pengesahan' => $filters['status_pengesahan'] ?? null,
            'status_pembayaran' => $filters['status_pembayaran'] ?? null,
            'search' => $filters['search'] ?? null,
        ];
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function applyDateRange($query, string $column, array $filters): void
    {
        if ($filters['start_date']) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }
    }

    private function applyBkuFilters(Builder $query, array $filters): void
    {
        $this->applyDateRange($query, 'tanggal_transaksi', $filters);

        $query
            ->when($filters['rekening_bank_id'], fn (Builder $q) => $q->where('sumber_rekening_id', $filters['rekening_bank_id']))
            ->when($filters['arus_kas'], fn (Builder $q) => $q->where('arus_kas', $filters['arus_kas']))
            ->when($filters['sumber_transaksi'] === 'pengeluaran', fn (Builder $q) => $q->whereNotNull('referensi_pengeluaran_id'))
            ->when($filters['sumber_transaksi'] === 'penerimaan', fn (Builder $q) => $q->whereNotNull('referensi_penerimaan_id'));
    }

    private function applyBendaharaFilters(Builder $query, array $filters): void
    {
        $this->applyBkuFilters($query, $filters);

        $query
            ->when($filters['jenis_transaksi'] === 'pengeluaran', fn (Builder $q) => $q->whereNotNull('referensi_pengeluaran_id'))
            ->when($filters['jenis_transaksi'] === 'penerimaan', fn (Builder $q) => $q->whereNotNull('referensi_penerimaan_id'))
            ->when($filters['tagihan_id'], fn (Builder $q) => $q->where('referensi_pengeluaran_id', $filters['tagihan_id']));

        // Filter mekanisme pembayaran: default hanya LS_BENDAHARA agar menu ini
        // konsisten dengan penamaannya ("Buku Pembantu LS Bendahara"). Pengguna
        // dapat mengubah ke 'all' atau memilih mekanisme lain lewat filter.
        $mekanisme = $filters['mekanisme_pembayaran']
            ?? \App\Enums\MekanismePembayaran::LS_BENDAHARA->value;

        if ($mekanisme !== 'all') {
            $query->where(function (Builder $q) use ($mekanisme) {
                // Baris yang punya referensi tagihan pengeluaran → match mekanisme tagihan
                $q->whereHas('referensiPengeluaran', function (Builder $t) use ($mekanisme) {
                    $t->where('mekanisme_pembayaran', $mekanisme);
                });
                // Baris penerimaan tetap ditampilkan (bukan outflow LS)
                $q->orWhereNotNull('referensi_penerimaan_id');
            });
        }
    }

    private function applyMutasiFilters($query, array $filters, string $dateColumn = 'tanggal_transaksi'): void
    {
        $this->applyDateRange($query, $dateColumn, $filters);

        if ($filters['rekening_bank_id']) {
            $query->whereHas('importMutasiBank', fn (Builder $q) => $q->where('rekening_bank_id', $filters['rekening_bank_id']));
        }

        if ($filters['arah_mutasi']) {
            $query->where('arah_mutasi', $filters['arah_mutasi']);
        }

        if ($filters['status_rekonsiliasi']) {
            $query->where('status_rekonsiliasi', $filters['status_rekonsiliasi']);
        }
    }

    private function applyReconciliationFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'], fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['rekening_bank_id'], function (Builder $q) use ($filters) {
                $q->whereHas('detailMutasiBank.importMutasiBank', fn (Builder $sub) => $sub->where('rekening_bank_id', $filters['rekening_bank_id']));
            });

        if ($filters['start_date']) {
            $query->whereHas('detailMutasiBank', fn (Builder $q) => $q->whereDate('tanggal_transaksi', '>=', $filters['start_date']));
        }

        if ($filters['end_date']) {
            $query->whereHas('detailMutasiBank', fn (Builder $q) => $q->whereDate('tanggal_transaksi', '<=', $filters['end_date']));
        }
    }

    private function applyPajakFilters(Builder $query, array $filters): void
    {
        if ($filters['start_date']) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $query
            ->when($filters['jenis_tagihan'], function (Builder $q) use ($filters) {
                $q->whereHas('tagihan', fn (Builder $sub) => $sub->where('tipe_tagihan', $filters['jenis_tagihan']));
            })
            ->when($filters['jenis_pajak'], function (Builder $q) use ($filters) {
                $q->where(function (Builder $sub) use ($filters) {
                    $sub->where('jenis_potongan', $filters['jenis_pajak'])
                        ->orWhere('nama_pajak_snapshot', $filters['jenis_pajak']);
                });
            });

        if ($filters['status_billing_setor'] === 'SUDAH_BILLING') {
            $query->whereNotNull('kode_billing');
        } elseif ($filters['status_billing_setor'] === 'SUDAH_SETOR') {
            $query->whereNotNull('ntpn');
        } elseif ($filters['status_billing_setor'] === 'BELUM_SETOR') {
            $query->whereNull('ntpn');
        }
    }

    private function applyBungaFilter($query): void
    {
        $keywords = ['bunga', 'jasa giro', 'interest'];
        $kategoriBungaValues = [
            \App\Enums\KategoriMutasiBank::BUNGA_MASUK->value,
            \App\Enums\KategoriMutasiBank::TRANSFER_BUNGA_KELUAR->value,
            \App\Enums\KategoriMutasiBank::PAJAK_BUNGA->value,
        ];

        $query->where(function ($subQuery) use ($keywords, $kategoriBungaValues) {
            // Prioritas 1: baris yang sudah terklasifikasi sebagai kategori bunga
            $subQuery->whereIn('kategori_mutasi', $kategoriBungaValues);

            // Prioritas 2: fallback keyword pada deskripsi (untuk data yang belum ter-classify)
            $subQuery->orWhere(function ($inner) use ($keywords) {
                $inner->whereNull('kategori_mutasi');
                $inner->where(function ($kw) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $kw->orWhereRaw('LOWER(deskripsi) LIKE ?', ['%' . $keyword . '%']);
                    }
                });
            });
        });
    }

    private function documentChain(?Tagihan $tagihan): array
    {
        $spp = $tagihan?->spps?->sortByDesc('tanggal_spp')->first();
        $spm = $spp?->spm;
        $npi = $spm?->npi;
        $sp2d = $npi?->sp2d;

        return compact('spp', 'spm', 'npi', 'sp2d');
    }

    private function mapBungaToBku(Collection $entries): array
    {
        if ($entries->isEmpty()) {
            return [];
        }

        $rekeningIds = $entries
            ->pluck('importMutasiBank.rekening_bank_id')
            ->filter()
            ->unique()
            ->values();

        $minDate = $entries->min('tanggal_transaksi');
        $maxDate = $entries->max('tanggal_transaksi');

        $candidateBku = BukuKasUmum::query()
            ->with('sumberRekening')
            ->where('arus_kas', 'DEBIT_MASUK')
            ->whereIn('sumber_rekening_id', $rekeningIds)
            ->whereBetween('tanggal_transaksi', [
                Carbon::parse($minDate)->subDays(3)->toDateString(),
                Carbon::parse($maxDate)->addDays(3)->toDateString(),
            ])
            ->get();

        $matches = [];
        foreach ($entries as $entry) {
            $nominal = (float) $entry->debit;
            $rekeningId = $entry->importMutasiBank?->rekening_bank_id;

            $match = $candidateBku->first(function (BukuKasUmum $bku) use ($entry, $nominal, $rekeningId) {
                return (int) $bku->sumber_rekening_id === (int) $rekeningId
                    && abs((float) $bku->nominal - $nominal) < 0.01
                    && abs(Carbon::parse($bku->tanggal_transaksi)->diffInDays($entry->tanggal_transaksi, false)) <= 3;
            });

            $matches[$entry->id] = $match;
        }

        return $matches;
    }

    public function buildPiutangIndexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $query = TransaksiPenerimaan::query()
            ->with(['mitra', 'coa', 'bukuKasUmums'])
            ->when($filters['start_date'], fn (Builder $q) => $q->whereDate('tanggal_invoice', '>=', $filters['start_date']))
            ->when($filters['end_date'], fn (Builder $q) => $q->whereDate('tanggal_invoice', '<=', $filters['end_date']))
            ->when($filters['status_pembayaran'], fn (Builder $q) => $q->where('status_pembayaran', $filters['status_pembayaran']))
            ->when($filters['search'], function (Builder $q) use ($filters) {
                $search = strtolower((string) $filters['search']);

                $q->where(function (Builder $sub) use ($search) {
                    $sub->whereRaw('LOWER(nomor_invoice) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(keterangan) LIKE ?', ["%{$search}%"])
                        ->orWhereHas('mitra', fn (Builder $mitra) => $mitra->whereRaw('LOWER(nama_pihak) LIKE ?', ["%{$search}%"]));
                });
            });

        $entries = $query
            ->orderByDesc('tanggal_invoice')
            ->orderByDesc('id')
            ->get();

        $today = now()->toDateString();

        return [
            'filters' => $filters,
            'entries' => $entries,
            'summary' => [
                'jumlah_piutang' => $entries->count(),
                'total_tagihan' => $entries->sum(fn (TransaksiPenerimaan $item) => (float) $item->nominal_tagihan),
                'total_dibayar' => $entries->sum(fn (TransaksiPenerimaan $item) => (float) $item->total_dibayar),
                'total_sisa' => $entries->sum(fn (TransaksiPenerimaan $item) => max((float) $item->nominal_tagihan - (float) $item->total_dibayar, 0)),
                'jatuh_tempo' => $entries->filter(function (TransaksiPenerimaan $item) use ($today) {
                    return $item->tanggal_jatuh_tempo
                        && $item->tanggal_jatuh_tempo->toDateString() < $today
                        && $item->status_pembayaran !== 'PAID';
                })->count(),
            ],
        ];
    }
}
