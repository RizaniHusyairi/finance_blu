<?php

namespace App\Services\Reports;

use App\Models\BukuKasUmum;
use App\Models\DetailDipa;
use App\Models\DokumenSp2d;
use App\Models\RealisasiAnggaran;
use App\Models\Tagihan;
use App\Models\TransaksiPenerimaan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportAggregationService
{
    public function buildBkuReport(array $filters = []): array
    {
        $year = (int) ($filters['year'] ?? date('Y'));
        $month = $filters['month'] ?? null;
        $budgetId = $filters['budget_id'] ?? null;

        $budgets = $this->getBudgetOptions();
        $selectedBudget = $budgetId ? $budgets->firstWhere('id', (int) $budgetId) : null;
        $totalPagu = $selectedBudget ? (float) $selectedBudget->initial_budget : (float) $budgets->sum('initial_budget');

        $tagihans = $this->queryTagihan($year, $month, $budgetId);
        $sp2ds = $this->querySp2d($year, $month, $budgetId);
        $realisasi = $this->queryRealisasi($year, $month, $budgetId);
        $bkuEntries = $this->queryBku($year, $month);
        $transaksiPenerimaan = $this->queryTransaksiPenerimaan($year, $month, $budgetId);
        $rekonsiliasi = $this->queryRekonsiliasi($year, $month);

        $bkuRows = $this->mapBkuRows($bkuEntries, $budgetId);
        $runningDebit = (float) collect($bkuRows)->sum('netto');
        $runningSaldo = count($bkuRows) > 0
            ? (float) collect($bkuRows)->last()['saldo']
            : $totalPagu;

        return [
            'bkuRows' => $bkuRows,
            'budgets' => $budgets,
            'totalPagu' => $totalPagu,
            'runningDebit' => $runningDebit,
            'runningSaldo' => $runningSaldo,
            'year' => $year,
            'month' => $month,
            'budgetId' => $budgetId,
            'filterMonths' => $this->filterMonths(),
            'sourceSummary' => [
                'tagihan' => [
                    'count' => $tagihans->count(),
                    'total' => (float) $tagihans->sum('total_netto'),
                ],
                'dokumen_sp2d' => [
                    'count' => $sp2ds->count(),
                    'total' => (float) $sp2ds->sum(fn ($item) => optional(optional(optional($item->npi)->spm)->spp)->nominal_spp ?? 0),
                ],
                'realisasi_anggaran' => [
                    'count' => $realisasi->count(),
                    'total' => (float) $realisasi->sum('nominal_cair'),
                ],
                'buku_kas_umum' => [
                    'count' => $bkuEntries->count(),
                    'total' => (float) $bkuEntries->sum('nominal'),
                ],
                'transaksi_penerimaan' => [
                    'count' => $transaksiPenerimaan->count(),
                    'total' => (float) $transaksiPenerimaan->sum('total_dibayar'),
                ],
                'rekonsiliasi_bank' => [
                    'count' => $rekonsiliasi->count(),
                    'total_selisih' => (float) $rekonsiliasi->sum('selisih'),
                ],
            ],
        ];
    }

    private function getBudgetOptions(): Collection
    {
        return DetailDipa::query()
            ->join('dipa_revisions', 'dipa_revision_items.dipa_revision_id', '=', 'dipa_revisions.id')
            ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
            ->where('dipa_revisions.is_active', true)
            ->selectRaw('dipa_revision_items.id, master_coas.kode_mak_lengkap as coa, master_coas.nama_akun as description, dipa_revision_items.nilai_pagu as initial_budget')
            ->orderBy('master_coas.kode_mak_lengkap')
            ->get();
    }

    private function queryTagihan(int $year, $month, $budgetId): Collection
    {
        return Tagihan::query()
            ->with(['pihak', 'spps.dipaRevisionItem'])
            ->where('status', 'SELESAI')
            ->when($budgetId, function ($query) use ($budgetId) {
                $query->whereHas('spps', fn ($spp) => $spp->where('dipa_revision_item_id', $budgetId));
            })
            ->when($year, function ($query) use ($year) {
                $query->whereYear('updated_at', $year);
            })
            ->when($month, function ($query) use ($month) {
                $query->whereMonth('updated_at', $month);
            })
            ->get();
    }

    private function querySp2d(int $year, $month, $budgetId): Collection
    {
        return DokumenSp2d::query()
            ->with(['npi.spm.spp.tagihan'])
            ->where('status', DokumenSp2d::STATUS_EXECUTED)
            ->when($year, fn ($query) => $query->whereYear('tanggal_sp2d', $year))
            ->when($month, fn ($query) => $query->whereMonth('tanggal_sp2d', $month))
            ->when($budgetId, function ($query) use ($budgetId) {
                $query->whereHas('npi.spm.spp', fn ($spp) => $spp->where('dipa_revision_item_id', $budgetId));
            })
            ->get();
    }

    private function queryRealisasi(int $year, $month, $budgetId): Collection
    {
        return RealisasiAnggaran::query()
            ->with(['tagihan', 'dipaRevisionItem.coa'])
            ->when($year, fn ($query) => $query->whereYear('tanggal_pencairan', $year))
            ->when($month, fn ($query) => $query->whereMonth('tanggal_pencairan', $month))
            ->when($budgetId, fn ($query) => $query->where('dipa_revision_item_id', $budgetId))
            ->get();
    }

    private function queryBku(int $year, $month): Collection
    {
        return BukuKasUmum::query()
            ->with(['referensiPengeluaran.pihak', 'referensiPengeluaran.spps.dipaRevisionItem.coa', 'referensiPenerimaan.mitra', 'referensiPenerimaan.coa'])
            ->whereYear('tanggal_transaksi', $year)
            ->when($month, fn ($query) => $query->whereMonth('tanggal_transaksi', $month))
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get();
    }

    private function queryTransaksiPenerimaan(int $year, $month, $budgetId): Collection
    {
        return TransaksiPenerimaan::query()
            ->with(['mitra', 'coa'])
            ->whereYear('updated_at', $year)
            ->when($month, fn ($query) => $query->whereMonth('updated_at', $month))
            ->when($budgetId, function ($query) use ($budgetId) {
                $query->whereHas('coa', function ($coaQuery) use ($budgetId) {
                    $coaQuery->whereIn('id', function ($sub) use ($budgetId) {
                        $sub->select('coa_id')
                            ->from('dipa_revision_items')
                            ->where('id', $budgetId);
                    });
                });
            })
            ->get();
    }

    private function queryRekonsiliasi(int $year, $month): Collection
    {
        return DB::table('rekonsiliasi_bank')
            ->when($year, function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
            ->when($month, function ($query) use ($month) {
                $query->whereMonth('created_at', $month);
            })
            ->get();
    }

    private function mapBkuRows(Collection $entries, $budgetId = null): array
    {
        $rows = [];

        foreach ($entries as $entry) {
            $pengeluaran = $entry->referensiPengeluaran;
            $penerimaan = $entry->referensiPenerimaan;
            $spp = $pengeluaran?->spps?->first();
            $coa = $spp?->dipaRevisionItem?->coa?->kode_mak_lengkap
                ?? $penerimaan?->coa?->kode_mak_lengkap
                ?? '-';

            if ($budgetId && $coa !== '-') {
                if ($spp && (int) $spp->dipa_revision_item_id !== (int) $budgetId) {
                    continue;
                }
            }

            $rows[] = [
                'date' => $entry->tanggal_transaksi,
                'transaction_number' => $entry->nomor_bukti,
                'description' => $entry->uraian,
                'supplier' => $pengeluaran?->pihak?->nama_pihak ?? $penerimaan?->mitra?->nama_pihak ?? '-',
                'bruto' => (float) $entry->nominal,
                'tax' => (float) ($pengeluaran->total_potongan ?? 0),
                'netto' => (float) $entry->nominal,
                'budget_coa' => $coa,
                'type' => $entry->arus_kas,
                'saldo' => (float) $entry->saldo_akhir,
            ];
        }

        return $rows;
    }

    private function filterMonths(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }
}
