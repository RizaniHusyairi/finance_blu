<?php

namespace App\Http\Controllers;

use App\Models\JadwalPenerbangan;
use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\PemakaianGarbarata;
use App\Models\PermohonanNonSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PemakaianGarbarataController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $dari = $request->input('tanggal_dari') ?: now()->startOfMonth()->toDateString();
        $sampai = $request->input('tanggal_sampai') ?: now()->endOfMonth()->toDateString();

        $query = PemakaianGarbarata::with(['mitra', 'layanan', 'creator', 'tagihan', 'permohonan'])
            ->whereDate('tanggal', '>=', $dari)
            ->whereDate('tanggal', '<=', $sampai)
            ->orderByDesc('tanggal')
            ->orderByDesc('docking_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($mitraId = $request->input('mitra_jasa_id')) {
            $query->where('mitra_jasa_id', $mitraId);
        }
        $this->applyAmcScope($query);

        $items = $query->get();

        $grouped = $items->groupBy('mitra_jasa_id')->map(function ($rows) {
            return [
                'mitra' => $rows->first()->mitra,
                'rows' => $rows,
                'count' => $rows->count(),
                'total_rentang' => $rows->sum('jumlah_rentang'),
                'total_durasi' => $rows->sum('durasi_menit'),
                'total_rp' => $rows->sum(function ($r) { return (float) $r->total_garbarata; }),
                'status_counts' => $rows->groupBy('status')->map->count(),
            ];
        })->sortByDesc('count')->values();

        $summary = [
            'total_flight' => $items->count(),
            'total_mitra' => $grouped->count(),
            'total_rp' => $items->sum(function ($r) { return (float) $r->total_garbarata; }),
        ];

        return view('pemakaian_garbarata.index', [
            'grouped' => $grouped,
            'summary' => $summary,
            'canSeeBilling' => $this->canSeeBilling(),
            'filters' => [
                'status' => $request->input('status'),
                'mitra_jasa_id' => $request->input('mitra_jasa_id'),
                'tanggal_dari' => $dari,
                'tanggal_sampai' => $sampai,
            ],
            'mitraOptions' => MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'statusOpt' => PemakaianGarbarata::STATUS_LABEL,
        ]);
    }

    public function create()
    {
        abort_unless($this->canCreate(), 403);

        return view('pemakaian_garbarata.form', [
            'item' => new PemakaianGarbarata(),
            'mitraOptions' => MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'garbarataLayanan' => $this->garbarataLayananOptions(),
            'permohonanOptions' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->canCreate(), 403);

        $validated = $this->validateBatchData($request);

        DB::transaction(function () use ($request, $validated) {
            foreach ($validated['rows'] as $idx => $row) {
                $data = $this->normalizeGarbarataRow($validated, $row);
                if ($request->hasFile("rows.$idx.file_pendukung")) {
                    $data['file_pendukung'] = $request->file("rows.$idx.file_pendukung")->store('pemakaian_garbarata', 'local');
                }
                PemakaianGarbarata::create($data);
            }
        });

        $count = count($validated['rows']);

        return redirect()->route('pemakaian-garbarata.index')
            ->with('success', "{$count} rincian pemakaian garbarata tercatat dan siap ditagih.");
    }

    public function edit(PemakaianGarbarata $pemakaian_garbarata)
    {
        abort_unless($this->canEdit($pemakaian_garbarata), 403);

        $permohonanOptions = collect();
        if ($pemakaian_garbarata->mitra_jasa_id) {
            $permohonanOptions = PermohonanNonSchedule::where('mitra_jasa_id', $pemakaian_garbarata->mitra_jasa_id)
                ->where('status', PermohonanNonSchedule::STATUS_DISETUJUI)
                ->orderByDesc('tanggal_surat')
                ->get();
        }

        return view('pemakaian_garbarata.form', [
            'item' => $pemakaian_garbarata,
            'mitraOptions' => MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'garbarataLayanan' => $this->garbarataLayananOptions(),
            'permohonanOptions' => $permohonanOptions,
        ]);
    }

    public function update(Request $request, PemakaianGarbarata $pemakaian_garbarata)
    {
        abort_unless($this->canEdit($pemakaian_garbarata), 403);

        $validated = $this->validateBatchData($request);
        $data = $this->normalizeGarbarataRow($validated, $validated['rows'][0]);

        $oldFile = $pemakaian_garbarata->file_pendukung;

        if ($request->hasFile('rows.0.file_pendukung')) {
            $newPath = $request->file('rows.0.file_pendukung')->store('pemakaian_garbarata', 'local');
            $data['file_pendukung'] = $newPath;

            if ($oldFile && $oldFile !== $newPath) {
                $stillUsed = PemakaianGarbarata::where('file_pendukung', $oldFile)
                    ->where('id', '!=', $pemakaian_garbarata->id)
                    ->exists();
                if (! $stillUsed) {
                    Storage::disk('public')->delete($oldFile);
                }
            }
        }

        $pemakaian_garbarata->update($data);

        return redirect()->route('pemakaian-garbarata.index')->with('success', 'Pemakaian garbarata diperbarui.');
    }

    public function destroy(PemakaianGarbarata $pemakaian_garbarata)
    {
        abort_unless($this->canEdit($pemakaian_garbarata) && $pemakaian_garbarata->status !== PemakaianGarbarata::STATUS_TERTAGIH, 403);

        $file = $pemakaian_garbarata->file_pendukung;
        $pemakaian_garbarata->delete();

        if ($file) {
            $stillUsed = PemakaianGarbarata::where('file_pendukung', $file)->exists();
            if (! $stillUsed) {
                Storage::disk('public')->delete($file);
            }
        }

        return redirect()->route('pemakaian-garbarata.index')->with('success', 'Catatan dihapus.');
    }

    public function rekapHarian(Request $request)
    {
        abort_unless($this->canView(), 403);

        $dari = $request->input('tanggal_dari') ?: now()->startOfMonth()->toDateString();
        $sampai = $request->input('tanggal_sampai') ?: now()->endOfMonth()->toDateString();
        $mitraId = $request->input('mitra_jasa_id');
        $status = $request->input('status');

        $query = PemakaianGarbarata::with(['mitra', 'layanan'])
            ->whereDate('tanggal', '>=', $dari)
            ->whereDate('tanggal', '<=', $sampai)
            ->orderBy('tanggal')
            ->orderBy('mitra_jasa_id')
            ->orderBy('docking_at');

        if ($mitraId) {
            $query->where('mitra_jasa_id', $mitraId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        $this->applyAmcScope($query);

        $items = $query->get();

        $grouped = $items->groupBy(function ($row) {
            return $row->tanggal?->toDateString();
        })->map(function ($perTanggal) {
            $byMitra = $perTanggal->groupBy('mitra_jasa_id')->map(function ($rows) {
                $totalRentang = $rows->sum('jumlah_rentang');
                $totalDurasi = $rows->sum('durasi_menit');
                $totalRp = $rows->sum(function ($r) { return (float) $r->total_garbarata; });
                return [
                    'mitra' => $rows->first()->mitra,
                    'rows' => $rows,
                    'jumlah_flight' => $rows->count(),
                    'total_rentang' => $totalRentang,
                    'total_durasi' => $totalDurasi,
                    'total_rp' => $totalRp,
                ];
            })->values();

            return [
                'jumlah_flight' => $perTanggal->count(),
                'total_rentang' => $perTanggal->sum('jumlah_rentang'),
                'total_rp' => $perTanggal->sum(function ($r) { return (float) $r->total_garbarata; }),
                'mitras' => $byMitra,
            ];
        });

        $grand = [
            'jumlah_flight' => $items->count(),
            'total_rentang' => $items->sum('jumlah_rentang'),
            'total_rp' => $items->sum(function ($r) { return (float) $r->total_garbarata; }),
            'jumlah_mitra' => $items->pluck('mitra_jasa_id')->unique()->count(),
            'jumlah_hari' => $grouped->count(),
        ];

        return view('pemakaian_garbarata.rekap_harian', [
            'grouped' => $grouped,
            'grand' => $grand,
            'canSeeBilling' => $this->canSeeBilling(),
            'filters' => [
                'tanggal_dari' => $dari,
                'tanggal_sampai' => $sampai,
                'mitra_jasa_id' => $mitraId,
                'status' => $status,
            ],
            'mitraOptions' => MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->orderBy('nama_mitra')->get(['id', 'nama_mitra']),
            'statusOpt' => PemakaianGarbarata::STATUS_LABEL,
        ]);
    }

    public function detailHari(Request $request, string $tanggal)
    {
        abort_unless($this->canView(), 403);

        try {
            $tgl = Carbon::parse($tanggal);
        } catch (\Throwable) {
            abort(404);
        }

        $rowsQuery = PemakaianGarbarata::with(['mitra', 'layanan', 'creator', 'tagihan', 'permohonan'])
            ->whereDate('tanggal', $tgl->toDateString())
            ->orderBy('mitra_jasa_id')
            ->orderBy('docking_at');
        $this->applyAmcScope($rowsQuery);
        $rows = $rowsQuery->get();

        $byMitra = $rows->groupBy('mitra_jasa_id')->map(function ($items) {
            return [
                'mitra' => $items->first()->mitra,
                'rows' => $items,
                'jumlah_flight' => $items->count(),
                'total_durasi' => $items->sum('durasi_menit'),
                'total_rentang' => $items->sum('jumlah_rentang'),
                'total_rp' => $items->sum(function ($r) { return (float) $r->total_garbarata; }),
            ];
        })->values();

        $summary = [
            'tanggal' => $tgl,
            'jumlah_mitra' => $byMitra->count(),
            'jumlah_flight' => $rows->count(),
            'total_rentang' => $rows->sum('jumlah_rentang'),
            'total_durasi' => $rows->sum('durasi_menit'),
            'total_rp' => $rows->sum(function ($r) { return (float) $r->total_garbarata; }),
            'file_count' => $rows->whereNotNull('file_pendukung')->count(),
        ];

        return view('pemakaian_garbarata.detail_hari', [
            'tanggal' => $tgl,
            'byMitra' => $byMitra,
            'summary' => $summary,
        ]);
    }

    public function jadwal(Request $request)
    {
        abort_unless($this->canView(), 403);

        $request->validate([
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
        ]);

        $rows = JadwalPenerbangan::where('mitra_jasa_id', $request->mitra_jasa_id)
            ->where('aktif', true)
            ->orderBy('sched_arrival')
            ->get(['id', 'flight_arr', 'flight_dep', 'origin', 'destination', 'route', 'aircraft_type', 'registrasi_pesawat', 'sched_arrival', 'sched_departure']);

        return response()->json(['data' => $rows]);
    }

    public function file(PemakaianGarbarata $pemakaian_garbarata)
    {
        // INF-01: file di disk privat `local` (fallback `public` untuk file lama).
        $disk = $pemakaian_garbarata->file_pendukung && Storage::disk('local')->exists($pemakaian_garbarata->file_pendukung) ? 'local' : 'public';
        abort_unless($pemakaian_garbarata->file_pendukung && Storage::disk($disk)->exists($pemakaian_garbarata->file_pendukung), 404);
        return Storage::disk($disk)->download($pemakaian_garbarata->file_pendukung);
    }

    private function validateBatchData(Request $request): array
    {
        return $request->validate([
            'mitra_jasa_id' => 'required|exists:mitra_jasa,id',
            'layanan_jasa_id' => 'nullable|exists:layanan_jasas,id',
            'permohonan_non_schedule_id' => 'nullable|exists:permohonan_non_schedule,id',
            'periode_bulan' => 'nullable|date_format:Y-m',
            'rows' => 'required|array|min:1',
            'rows.*.tanggal' => 'required|date',
            'rows.*.registrasi_pesawat' => 'nullable|string|max:50',
            'rows.*.flight_arr' => 'nullable|string|max:50',
            'rows.*.flight_dep' => 'nullable|string|max:50',
            'rows.*.route' => 'nullable|string|max:150',
            'rows.*.docking' => 'required|date_format:H:i',
            'rows.*.undocking' => 'required|date_format:H:i',
            'rows.*.type_pesawat' => 'nullable|string|max:50',
            'rows.*.nomor_avio' => 'nullable|in:1,2',
            'rows.*.bobot_ton' => 'nullable|numeric|min:0',
            'rows.*.tarif_garbarata' => 'required|numeric|min:0',
            'rows.*.file_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'keterangan' => 'nullable|string|max:1000',
        ]);
    }

    private function normalizeGarbarataRow(array $validated, array $row): array
    {
        $tanggal = Carbon::parse($row['tanggal'])->toDateString();
        $docking = Carbon::parse($tanggal . ' ' . $row['docking']);
        $undocking = Carbon::parse($tanggal . ' ' . $row['undocking']);

        if ($undocking->lessThan($docking)) {
            $undocking->addDay();
        }

        $durasi = PemakaianGarbarata::computeDuration($docking->toDateTimeString(), $undocking->toDateTimeString());
        $rentang = PemakaianGarbarata::computeRentang($durasi);
        $arr = trim((string) ($row['flight_arr'] ?? ''));
        $dep = trim((string) ($row['flight_dep'] ?? ''));
        $mitraId = $this->resolveRowMitra($arr, $dep, (int) $validated['mitra_jasa_id']);

        return [
            'mitra_jasa_id' => $mitraId,
            'layanan_jasa_id' => $validated['layanan_jasa_id'] ?? null,
            'permohonan_non_schedule_id' => $validated['permohonan_non_schedule_id'] ?? null,
            'tanggal' => $tanggal,
            'nomor_penerbangan' => trim($arr . ($arr && $dep ? ' / ' : '') . $dep) ?: null,
            'registrasi_pesawat' => $row['registrasi_pesawat'] ?? null,
            'flight_arr' => $arr ?: null,
            'flight_dep' => $dep ?: null,
            'route' => $row['route'] ?? null,
            'type_pesawat' => $row['type_pesawat'] ?? null,
            'nomor_avio' => isset($row['nomor_avio']) ? (int) $row['nomor_avio'] : null,
            'bobot_ton' => $row['bobot_ton'] ?? null,
            'docking_at' => $docking,
            'undocking_at' => $undocking,
            'durasi_menit' => $durasi,
            'jumlah_rentang' => $rentang,
            'tarif_garbarata' => $row['tarif_garbarata'] ?? 0,
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => PemakaianGarbarata::STATUS_SIAP,
            'created_by' => Auth::id(),
        ];
    }

    private function resolveRowMitra(string $arr, string $dep, int $fallback): int
    {
        if ($arr !== '') {
            $jadwal = JadwalPenerbangan::where('flight_arr', $arr)->where('aktif', true)->first();
            if ($jadwal) return (int) $jadwal->mitra_jasa_id;
        }
        if ($dep !== '') {
            $jadwal = JadwalPenerbangan::where('flight_dep', $dep)->where('aktif', true)->first();
            if ($jadwal) return (int) $jadwal->mitra_jasa_id;
        }
        return $fallback;
    }

    private function garbarataLayananOptions()
    {
        return LayananJasa::leaves()
            ->where(function ($q) {
                $q->where('nama_layanan', 'like', '%garbarata%')
                  ->orWhere('nama_layanan', 'like', '%aviobridge%');
            })
            ->orderBy('nama_layanan')
            ->get(['id', 'nama_layanan']);
    }

    private function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa', 'AMC', 'Operator BLU']);
    }

    /**
     * Informasi tagihan garbarata (nominal rupiah, status tagihan, nomor tagihan)
     * hanya untuk peran keuangan/jasa. AMC (operasional apron) tidak boleh melihatnya.
     */
    private function canSeeBilling(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa', 'Operator BLU']);
    }

    private function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(['Super Admin', 'AMC']);
    }

    /**
     * Operator AMC murni hanya melihat catatan pemakaian yang ia buat sendiri.
     * Peran pengawas/jasa (Super Admin, Super Admin Jasa, Admin Jasa, Koordinator Jasa,
     * Operator BLU non-AMC) tetap melihat seluruh data. Selaras dengan scoping di
     * PermohonanNonScheduleController::index().
     */
    private function applyAmcScope($query)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('AMC') && ! $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Koordinator Jasa'])) {
            $query->where('created_by', $user->id);
        }
        return $query;
    }

    private function canEdit(PemakaianGarbarata $item): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if ($user->hasRole('Super Admin')) return true;
        if ($item->status === PemakaianGarbarata::STATUS_TERTAGIH) return false;
        return $user->hasRole('AMC') && $item->created_by === $user->id;
    }
}
