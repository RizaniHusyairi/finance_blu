<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\LogPerubahanTarifPjp2u;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogPerubahanTarifPjp2uController extends Controller
{
    public function riwayat(LayananJasa $master_layanan_jasa)
    {
        abort_unless($master_layanan_jasa->is_leaf && $master_layanan_jasa->isPjp2u(), 404, 'Layanan ini bukan PJP2U.');

        $logs = LogPerubahanTarifPjp2u::with('creator')
            ->where('layanan_jasa_id', $master_layanan_jasa->id)
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id')
            ->paginate(20);

        return view('master_layanan_jasa.riwayat_tarif', [
            'layanan' => $master_layanan_jasa,
            'logs' => $logs,
        ]);
    }

    public function laporan(Request $request)
    {
        abort_unless($this->canViewLaporan(), 403);

        $filters = $this->resolveFilters($request);
        $data = $this->buildLaporanData($filters);

        return view('super_admin_jasa.laporan.log-tarif-pjp2u', $data + [
            'filters' => $filters,
            'layananOptions' => $this->layananOptions(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        abort_unless($this->canViewLaporan(), 403);
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $filters = $this->resolveFilters($request);
        $data = $this->buildLaporanData($filters, withPagination: false);

        $filename = 'log-tarif-pjp2u-' . now()->format('YmdHis');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('super_admin_jasa.laporan.log-tarif-pjp2u-pdf', $data + ['filters' => $filters])
                ->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return $this->streamExcel($data['logs'], $filename . '.xls');
    }

    public function fileDownload(LogPerubahanTarifPjp2u $log)
    {
        abort_unless($this->canViewLaporan(), 403);
        // INF-01: file di disk privat `local` (fallback `public` untuk file lama).
        $disk = $log->file_pendukung && Storage::disk('local')->exists($log->file_pendukung) ? 'local' : 'public';
        abort_unless($log->file_pendukung && Storage::disk($disk)->exists($log->file_pendukung), 404);

        return Storage::disk($disk)->download($log->file_pendukung);
    }

    private function buildLaporanData(array $filters, bool $withPagination = true): array
    {
        $query = LogPerubahanTarifPjp2u::query()
            ->with(['layananJasa', 'creator'])
            ->when($filters['tanggal_dari'], fn ($q) => $q->whereDate('berlaku_mulai', '>=', $filters['tanggal_dari']))
            ->when($filters['tanggal_sampai'], fn ($q) => $q->whereDate('berlaku_mulai', '<=', $filters['tanggal_sampai']))
            ->when($filters['layanan_id'], fn ($q) => $q->where('layanan_jasa_id', $filters['layanan_id']))
            ->when($filters['tipe_perubahan'], fn ($q) => $q->where('tipe_perubahan', $filters['tipe_perubahan']))
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id');

        $logs = $withPagination ? $query->paginate(25)->withQueryString() : $query->get();

        $summary = [
            'total_perubahan' => $withPagination ? $logs->total() : $logs->count(),
            'revisi_resmi' => $this->countByTipe($filters, LogPerubahanTarifPjp2u::TIPE_REVISI_RESMI),
            'diskon' => $this->countByTipe($filters, LogPerubahanTarifPjp2u::TIPE_DISKON),
            'koreksi' => $this->countByTipe($filters, LogPerubahanTarifPjp2u::TIPE_KOREKSI),
        ];

        return compact('logs', 'summary');
    }

    private function countByTipe(array $filters, string $tipe): int
    {
        return LogPerubahanTarifPjp2u::query()
            ->when($filters['tanggal_dari'], fn ($q) => $q->whereDate('berlaku_mulai', '>=', $filters['tanggal_dari']))
            ->when($filters['tanggal_sampai'], fn ($q) => $q->whereDate('berlaku_mulai', '<=', $filters['tanggal_sampai']))
            ->when($filters['layanan_id'], fn ($q) => $q->where('layanan_jasa_id', $filters['layanan_id']))
            ->where('tipe_perubahan', $tipe)
            ->count();
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'tanggal_dari' => $request->filled('tanggal_dari') ? Carbon::parse($request->input('tanggal_dari'))->toDateString() : null,
            'tanggal_sampai' => $request->filled('tanggal_sampai') ? Carbon::parse($request->input('tanggal_sampai'))->toDateString() : null,
            'layanan_id' => $request->filled('layanan_id') ? (int) $request->input('layanan_id') : null,
            'tipe_perubahan' => in_array($request->input('tipe_perubahan'), array_keys(LogPerubahanTarifPjp2u::TIPE_LABEL), true)
                ? $request->input('tipe_perubahan')
                : null,
        ];
    }

    private function layananOptions()
    {
        return LayananJasa::query()
            ->where('is_leaf', true)
            ->where('is_active', true)
            ->orderBy('nama_layanan')
            ->get()
            ->filter(fn (LayananJasa $l) => $l->isPjp2u())
            ->values();
    }

    private function canViewLaporan(): bool
    {
        return Auth::user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Operator BLU', 'Koordinator Jasa']) ?? false;
    }

    private function streamExcel($logs, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($logs) {
            echo "<table border='1'>";
            echo "<thead><tr>";
            foreach (['Tanggal Berlaku', 'Berlaku Sampai', 'Layanan', 'Tarif Lama', 'Tarif Baru', 'Selisih', 'Tipe', 'Nomor Referensi', 'Alasan', 'Diubah Oleh', 'Dicatat'] as $h) {
                echo '<th>' . htmlspecialchars($h) . '</th>';
            }
            echo "</tr></thead><tbody>";
            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td>' . optional($log->berlaku_mulai)->format('d/m/Y') . '</td>';
                echo '<td>' . (optional($log->berlaku_sampai)->format('d/m/Y') ?: '-') . '</td>';
                echo '<td>' . htmlspecialchars($log->layananJasa?->nama_lengkap ?? '-') . '</td>';
                echo '<td>' . number_format((float) $log->tarif_lama, 2, ',', '.') . '</td>';
                echo '<td>' . number_format((float) $log->tarif_baru, 2, ',', '.') . '</td>';
                echo '<td>' . number_format($log->selisih, 2, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($log->tipe_label) . '</td>';
                echo '<td>' . htmlspecialchars($log->nomor_referensi ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($log->alasan) . '</td>';
                echo '<td>' . htmlspecialchars($log->creator?->name ?? '-') . '</td>';
                echo '<td>' . $log->created_at?->format('d/m/Y H:i') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }
}
