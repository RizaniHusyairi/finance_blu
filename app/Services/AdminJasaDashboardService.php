<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\TagihanJasa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminJasaDashboardService
{
    public function getAllowedItemIds(User $admin): array
    {
        if ($admin->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa'])) {
            return LayananJasa::query()
                ->where('is_active', true)
                ->where('is_leaf', true)
                ->pluck('id')
                ->unique()
                ->values()
                ->all();
        }

        return $admin->layananJasaDikelolaAktif()
            ->where('layanan_jasas.is_active', true)
            ->where('layanan_jasas.is_leaf', true)
            ->pluck('layanan_jasas.id')
            ->unique()
            ->values()
            ->all();
    }

    public function getSummaryCards(User $admin, array $filters): array
    {
        $base = $this->baseQuery($admin, $filters);
        $published = (clone $base)->whereIn('status', ['PUBLISHED', 'LUNAS']);
        $unpaid = (clone $base)->where('status', 'PUBLISHED')->where('status_pembayaran', '!=', 'lunas');
        $paid = (clone $base)->where(function ($query) {
            $query->where('status', 'LUNAS')->orWhere('status_pembayaran', 'lunas');
        });
        $overdue = $this->overdueQuery($admin, $filters);
        $dueSoon = $this->dueSoonQuery($admin, $filters);

        return [
            'published_count' => (clone $published)->count(),
            'published_nominal' => (float) (clone $published)->sum('total_tagihan'),
            'unpaid_count' => (clone $unpaid)->count(),
            'unpaid_nominal' => (float) (clone $unpaid)->sum('sisa_tagihan'),
            'paid_count' => (clone $paid)->count(),
            'paid_nominal' => (float) (clone $paid)->sum('total_tagihan'),
            'overdue_count' => (clone $overdue)->count(),
            'overdue_nominal' => (float) (clone $overdue)->sum('sisa_tagihan'),
            'due_soon_count' => (clone $dueSoon)->count(),
        ];
    }

    public function getVerificationSummary(User $admin, array $filters): array
    {
        $statusCounts = $this->baseQuery($admin, $filters)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'draft' => (int) ($statusCounts['DRAFT'] ?? 0),
            'diajukan' => (int) ($statusCounts['VERIFIKASI_KOORDINATOR'] ?? 0),
            'menunggu_verifikasi' => (int) (($statusCounts['VERIFIKASI_KOORDINATOR'] ?? 0) + ($statusCounts['VERIFIKASI_KABANDARA'] ?? 0)),
            'diverifikasi' => (int) ($statusCounts['VERIFIKASI_KABANDARA'] ?? 0),
            'ditolak' => (int) ($statusCounts['DITOLAK'] ?? 0),
            'published' => (int) ($statusCounts['PUBLISHED'] ?? 0),
            'dibayar' => (int) ($statusCounts['LUNAS'] ?? 0),
            'batal' => (int) ($statusCounts['BATAL'] ?? 0),
        ];
    }

    public function getPaymentSummary(User $admin, array $filters): array
    {
        return $this->baseQuery($admin, $filters)
            ->select('status_pembayaran', DB::raw('count(*) as total'), DB::raw('sum(total_tagihan) as nominal'))
            ->groupBy('status_pembayaran')
            ->get()
            ->keyBy('status_pembayaran')
            ->map(fn ($row) => ['count' => (int) $row->total, 'nominal' => (float) $row->nominal])
            ->all();
    }

    public function getMitraSummary(User $admin, array $filters): array
    {
        $base = $this->baseQuery($admin, $filters);
        $mitraIds = (clone $base)->whereNotNull('mitra_jasa_id')->distinct()->pluck('mitra_jasa_id');
        $unpaidMitraIds = (clone $base)->where('status', 'PUBLISHED')->where('status_pembayaran', '!=', 'lunas')->distinct()->pluck('mitra_jasa_id');
        $overdueMitraIds = $this->overdueQuery($admin, $filters)->distinct()->pluck('mitra_jasa_id');

        return [
            'total' => $mitraIds->count(),
            'aktif' => DB::table('mitra_jasa')->whereIn('id', $mitraIds)->where('status_aktif', true)->count(),
            'belum_lunas' => $unpaidMitraIds->count(),
            'jatuh_tempo' => $overdueMitraIds->count(),
            'top_nominal' => $this->topMitraQuery($admin, $filters)->limit(5)->get(),
            'top_unpaid' => $this->topMitraQuery($admin, $filters, true)->limit(5)->get(),
        ];
    }

    public function getLayananSummary(User $admin, array $filters): array
    {
        $allowedIds = $this->getAllowedItemIds($admin);
        $items = LayananJasa::with('parent.parent')->whereIn('id', $allowedIds)->get();
        $parentIds = $items->pluck('parent_id')->filter()->unique();
        $rootIds = $items->map(function ($item) {
            $current = $item;
            $guard = 0;
            while ($current->parent && $guard < 10) {
                $current = $current->parent;
                $guard++;
            }
            return $current->id;
        })->unique();

        return [
            'total_item' => count($allowedIds),
            'total_jenis' => $rootIds->count(),
            'total_kategori' => $parentIds->count(),
            'sering_ditagihkan' => $this->topLayananQuery($admin, $filters, 'count')->limit(5)->get(),
            'nominal_terbesar' => $this->topLayananQuery($admin, $filters, 'nominal')->limit(5)->get(),
            'tunggakan_terbesar' => $this->topLayananQuery($admin, $filters, 'unpaid')->limit(5)->get(),
        ];
    }

    public function getLatestTagihan(User $admin, array $filters, int $limit = 10)
    {
        return $this->baseQuery($admin, $filters)
            ->with(['mitra', 'details.layananJasa'])
            ->latest('tanggal_tagihan')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function getUnpaidTagihan(User $admin, array $filters, int $limit = 10)
    {
        return $this->baseQuery($admin, $filters)
            ->with(['mitra'])
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->orderByRaw('CASE WHEN tanggal_jatuh_tempo IS NULL THEN 1 ELSE 0 END')
            ->orderBy('tanggal_jatuh_tempo')
            ->limit($limit)
            ->get();
    }

    public function getOverdueTagihan(User $admin, array $filters, int $limit = 10)
    {
        return $this->overdueQuery($admin, $filters)
            ->with(['mitra'])
            ->orderBy('tanggal_jatuh_tempo')
            ->limit($limit)
            ->get();
    }

    public function getLatestNotifications(User $admin, array $filters, int $limit = 10): Collection
    {
        return collect();
    }

    public function getChartTagihanBulanan(User $admin, array $filters): array
    {
        $rows = $this->baseQuery($admin, $filters)
            ->selectRaw('MONTH(tanggal_tagihan) as month_num, COUNT(*) as total, SUM(total_tagihan) as nominal')
            ->groupByRaw('MONTH(tanggal_tagihan)')
            ->orderByRaw('MONTH(tanggal_tagihan)')
            ->get()
            ->keyBy('month_num');

        return $this->monthLabels()->map(function ($label, $month) use ($rows) {
            $row = $rows->get($month);
            return ['label' => $label, 'count' => (int) ($row->total ?? 0), 'nominal' => (float) ($row->nominal ?? 0)];
        })->values()->all();
    }

    public function getChartPaymentStatus(User $admin, array $filters): array
    {
        $summary = $this->getPaymentSummary($admin, $filters);

        return [
            'labels' => ['Belum Dibayar', 'Sebagian', 'Lunas'],
            'data' => [
                (float) ($summary['belum_dibayar']['nominal'] ?? 0),
                (float) ($summary['sebagian']['nominal'] ?? 0),
                (float) ($summary['lunas']['nominal'] ?? 0),
            ],
        ];
    }

    public function getChartTagihanByStatus(User $admin, array $filters): array
    {
        $rows = $this->baseQuery($admin, $filters)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return ['labels' => $rows->keys()->values()->all(), 'data' => $rows->values()->map(fn ($v) => (int) $v)->all()];
    }

    public function getChartTopMitra(User $admin, array $filters): array
    {
        $rows = $this->topMitraQuery($admin, $filters)->limit(5)->get();

        return ['labels' => $rows->pluck('nama_mitra')->all(), 'data' => $rows->pluck('nominal')->map(fn ($v) => (float) $v)->all()];
    }

    public function getChartTopLayanan(User $admin, array $filters): array
    {
        $rows = $this->topLayananQuery($admin, $filters, 'nominal')->limit(5)->get();
        return ['labels' => $rows->pluck('nama_layanan')->all(), 'data' => $rows->pluck('nominal')->map(fn ($v) => (float) $v)->all()];
    }

    public function getPersentaseLunas(User $admin, array $filters): float
    {
        $base = $this->baseQuery($admin, $filters);
        $total = (clone $base)->count();
        if ($total <= 0) {
            return 0.0;
        }
        $lunas = (clone $base)->where('status', 'LUNAS')->count();
        return round(($lunas / $total) * 100, 1);
    }

    public function getCalendar(User $admin, array $filters): array
    {
        $month = (int) ($filters['month'] ?? now()->month);
        $year = (int) ($filters['year'] ?? now()->year);
        $first = \Carbon\Carbon::create($year, $month, 1);
        $rangeStart = $first->copy()->startOfMonth()->toDateString();
        $rangeEnd = $first->copy()->endOfMonth()->toDateString();

        $rows = $this->baseQuery($admin, $filters)
            ->select('id', 'nomor_tagihan', 'tanggal_tagihan', 'tanggal_jatuh_tempo', 'status', 'mitra_jasa_id')
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('tanggal_tagihan', [$rangeStart, $rangeEnd])
                  ->orWhereBetween('tanggal_jatuh_tempo', [$rangeStart, $rangeEnd]);
            })
            ->limit(200)
            ->get();

        $events = [];
        foreach ($rows as $t) {
            if ($t->tanggal_tagihan && $t->tanggal_tagihan->month === $month && $t->tanggal_tagihan->year === $year) {
                $d = (int) $t->tanggal_tagihan->format('j');
                $events[$d][] = [
                    'type' => 'terbit',
                    'label' => 'Tagihan terbit',
                    'nomor' => $t->nomor_tagihan,
                    'status' => $t->status,
                ];
            }
            if ($t->tanggal_jatuh_tempo && $t->tanggal_jatuh_tempo->month === $month && $t->tanggal_jatuh_tempo->year === $year) {
                $d = (int) $t->tanggal_jatuh_tempo->format('j');
                $events[$d][] = [
                    'type' => $t->status === 'LUNAS' ? 'lunas' : 'jatuh_tempo',
                    'label' => $t->status === 'LUNAS' ? 'Lunas' : 'Jatuh tempo',
                    'nomor' => $t->nomor_tagihan,
                    'status' => $t->status,
                ];
            }
        }

        $today = (now()->month === $month && now()->year === $year) ? (int) now()->day : null;

        return [
            'month' => $month,
            'year' => $year,
            'monthLabel' => $first->translatedFormat('F Y'),
            'firstWeekday' => (int) $first->dayOfWeek,
            'daysInMonth' => $first->daysInMonth,
            'today' => $today,
            'events' => $events,
        ];
    }

    public function baseQuery(User $admin, array $filters): Builder
    {
        $allowedIds = $this->getAllowedItemIds($admin);

        $query = TagihanJasa::query()
            ->whereHas('details', fn ($detail) => $detail->whereIn('layanan_jasa_id', $allowedIds));

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('tanggal_tagihan', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('tanggal_tagihan', '<=', $filters['date_to']);
        }

        if (! empty($filters['month'])) {
            $query->whereMonth('tanggal_tagihan', (int) $filters['month']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('tanggal_tagihan', (int) $filters['year']);
        }

        if (! empty($filters['mitra_jasa_id'])) {
            $query->where('mitra_jasa_id', $filters['mitra_jasa_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['status_pembayaran'])) {
            $query->where('status_pembayaran', $filters['status_pembayaran']);
        }

        if (! empty($filters['layanan_jasa_id'])) {
            $query->whereHas('details', fn ($detail) => $detail->where('layanan_jasa_id', $filters['layanan_jasa_id']));
        }
    }

    private function overdueQuery(User $admin, array $filters): Builder
    {
        return $this->baseQuery($admin, $filters)
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', now()->toDateString());
    }

    private function dueSoonQuery(User $admin, array $filters): Builder
    {
        return $this->baseQuery($admin, $filters)
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', '!=', 'lunas')
            ->whereBetween('tanggal_jatuh_tempo', [now()->toDateString(), now()->addDays(7)->toDateString()]);
    }

    private function topMitraQuery(User $admin, array $filters, bool $unpaidOnly = false)
    {
        $query = $this->baseQuery($admin, $filters)
            ->join('mitra_jasa', 'tagihan_jasas.mitra_jasa_id', '=', 'mitra_jasa.id')
            ->select('mitra_jasa.id', 'mitra_jasa.nama_mitra', DB::raw('SUM(tagihan_jasas.total_tagihan) as nominal'), DB::raw('COUNT(*) as total_tagihan'))
            ->groupBy('mitra_jasa.id', 'mitra_jasa.nama_mitra')
            ->orderByDesc('nominal');

        if ($unpaidOnly) {
            $query->where('tagihan_jasas.status', 'PUBLISHED')->where('tagihan_jasas.status_pembayaran', '!=', 'lunas');
        }

        return $query;
    }

    private function topLayananQuery(User $admin, array $filters, string $mode)
    {
        $allowedIds = $this->getAllowedItemIds($admin);

        $query = $this->baseQuery($admin, $filters)
            ->join('tagihan_jasa_details', 'tagihan_jasas.id', '=', 'tagihan_jasa_details.tagihan_jasa_id')
            ->join('layanan_jasas', 'tagihan_jasa_details.layanan_jasa_id', '=', 'layanan_jasas.id')
            ->whereIn('tagihan_jasa_details.layanan_jasa_id', $allowedIds)
            ->select('layanan_jasas.id', 'layanan_jasas.nama_layanan', DB::raw('COUNT(tagihan_jasa_details.id) as total_item'), DB::raw('SUM(tagihan_jasa_details.subtotal) as nominal'))
            ->groupBy('layanan_jasas.id', 'layanan_jasas.nama_layanan');

        if ($mode === 'unpaid') {
            $query->where('tagihan_jasas.status', 'PUBLISHED')->where('tagihan_jasas.status_pembayaran', '!=', 'lunas');
        }

        return $query->orderByDesc($mode === 'count' ? 'total_item' : 'nominal');
    }

    private function monthLabels(): Collection
    {
        return collect([
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ]);
    }
}
