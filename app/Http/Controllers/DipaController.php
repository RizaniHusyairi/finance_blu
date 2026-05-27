<?php

namespace App\Http\Controllers;

use App\Models\MasterDipa;
use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\RiwayatRevisiDipa;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DipaController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = MasterDipa::query()->with(['activeRevision.items.coa']);

        $query = (clone $baseQuery)
            ->when($request->filled('search'), function ($builder) use ($request) {
                $builder->where('nomor_dipa', 'like', '%' . trim($request->string('search')) . '%');
            })
            ->when($request->filled('tahun_anggaran'), function ($builder) use ($request) {
                $builder->where('tahun_anggaran', $request->integer('tahun_anggaran'));
            })
            ->when($request->filled('status_aktif'), function ($builder) use ($request) {
                $builder->where('status_aktif', $request->string('status_aktif') === 'aktif');
            })
            ->when($request->filled('revisi_aktif_ke'), function ($builder) use ($request) {
                $builder->where('revisi_aktif_ke', $request->integer('revisi_aktif_ke'));
            })
            ->orderByDesc('tahun_anggaran')
            ->orderByDesc('tanggal_disahkan')
            ->orderBy('nomor_dipa');

        $dipas = $query->get();

        if ($request->ajax() && $request->boolean('partial')) {
            return response()->view('dipas._table', compact('dipas'));
        }

        $allDipas = $baseQuery->get();
        $tahunBerjalan = (int) now()->year;
        $summary = [
            'total_dipa' => $allDipas->count(),
            'dipa_aktif' => $allDipas->where('status_aktif', true)->count(),
            'tahun_berjalan' => $allDipas->where('tahun_anggaran', $tahunBerjalan)->count(),
            'total_pagu_revisi_aktif' => (float) $allDipas->sum(fn ($dipa) => (float) optional($dipa->activeRevision)->total_pagu),
        ];

        $tahunOptions = MasterDipa::query()
            ->select('tahun_anggaran')
            ->distinct()
            ->orderByDesc('tahun_anggaran')
            ->pluck('tahun_anggaran');

        $revisiOptions = MasterDipa::query()
            ->select('revisi_aktif_ke')
            ->distinct()
            ->orderByDesc('revisi_aktif_ke')
            ->pluck('revisi_aktif_ke')
            ->filter(fn ($value) => $value !== null)
            ->values();

        return view('dipas.index', compact('dipas', 'summary', 'tahunOptions', 'revisiOptions'));
    }

    public function create()
    {
        return view('dipas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_dipa' => 'required|string|max:255|unique:master_dipas,nomor_dipa',
            'tahun_anggaran' => 'required|integer|min:2000|max:2100',
            'tanggal_disahkan' => 'required|date',
            'status_aktif' => 'required|boolean',
            'tanggal_revisi' => 'nullable|date',
            'total_pagu' => 'required|numeric|min:0',
            'file_dokumen_dipa' => 'nullable|file|mimes:pdf|max:5120',
            'keterangan' => 'nullable|string',
            'redirect_action' => 'nullable|string|in:save,save_and_detail',
        ]);

        $dipa = DB::transaction(function () use ($request, $validated) {
            $dipa = MasterDipa::create([
                'nomor_dipa' => $validated['nomor_dipa'],
                'tahun_anggaran' => $validated['tahun_anggaran'],
                'tanggal_disahkan' => $validated['tanggal_disahkan'],
                'revisi_aktif_ke' => 0,
                'status_aktif' => (bool) $validated['status_aktif'],
            ]);

            $filePath = $request->hasFile('file_dokumen_dipa')
                ? $request->file('file_dokumen_dipa')->store('dipa/documents', 'public')
                : null;

            RiwayatRevisiDipa::create([
                'master_dipa_id' => $dipa->id,
                'nomor_revisi' => 0,
                'tanggal_revisi' => $validated['tanggal_revisi'] ?? $validated['tanggal_disahkan'],
                'total_pagu' => $validated['total_pagu'],
                'file_dokumen_dipa' => $filePath,
                'keterangan' => $validated['keterangan'] ?? null,
                'is_active' => true,
            ]);

            return $dipa;
        });

        $message = 'DIPA ' . $dipa->nomor_dipa . ' berhasil dibuat beserta revisi awal aktif.';

        if (($validated['redirect_action'] ?? 'save') === 'save_and_detail') {
            return redirect()
                ->route('dipas.show', $dipa)
                ->with('success', $message);
        }

        return redirect()
            ->route('dipas.index')
            ->with('success', $message);
    }

    public function show(MasterDipa $dipa)
    {
        $dipa->load([
            'activeRevision.items.coa',
            'activeRevision.items.realisasiAnggarans',
            'revisions.items.coa',
        ]);

        $activeRevision = $dipa->activeRevision;
        $items = collect(optional($activeRevision)->items ?? [])
            ->filter(function ($item) {
                if (request()->filled('search_coa')) {
                    $search = strtolower(trim((string) request('search_coa')));
                    $kode = strtolower((string) optional($item->coa)->kode_mak_lengkap);
                    if (!str_contains($kode, $search)) {
                        return false;
                    }
                }

                if (request()->filled('search_nama_akun')) {
                    $search = strtolower(trim((string) request('search_nama_akun')));
                    $nama = strtolower((string) optional($item->coa)->nama_akun);
                    if (!str_contains($nama, $search)) {
                        return false;
                    }
                }

                if (request()->filled('kd_akun')) {
                    if ((string) optional($item->coa)->kd_akun !== (string) request('kd_akun')) {
                        return false;
                    }
                }

                if (request()->filled('status_item')) {
                    $expected = request('status_item') === 'aktif';
                    if ((bool) $item->status_aktif !== $expected) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        $summary = [
            'total_pagu_revisi_aktif' => (float) optional($activeRevision)->total_pagu,
            'total_item_anggaran' => (float) collect(optional($activeRevision)->items ?? [])->sum('nilai_pagu'),
            'jumlah_item_aktif' => collect(optional($activeRevision)->items ?? [])->where('status_aktif', true)->count(),
        ];
        $summary['selisih_pagu_vs_item'] = $summary['total_pagu_revisi_aktif'] - $summary['total_item_anggaran'];

        $coaOptions = MasterCoa::query()
            ->where('status_aktif', true)
            ->orderBy('kode_mak_lengkap')
            ->get();

        $kdAkunOptions = MasterCoa::query()
            ->where('status_aktif', true)
            ->whereNotNull('kd_akun')
            ->distinct()
            ->orderBy('kd_akun')
            ->pluck('kd_akun');

        return view('dipas.show', compact('dipa', 'activeRevision', 'items', 'summary', 'coaOptions', 'kdAkunOptions'));
    }

    public function createRevision(MasterDipa $dipa)
    {
        $dipa->load(['activeRevision.items.coa', 'revisions']);

        $activeRevision = $dipa->activeRevision;
        $nextRevisionNumber = ((int) $dipa->revisions->max('nomor_revisi')) + 1;
        $activeItems = collect(optional($activeRevision)->items ?? []);

        $summary = [
            'revisi_aktif_saat_ini' => $activeRevision?->nomor_revisi ?? $dipa->revisi_aktif_ke ?? 0,
            'total_pagu_revisi_aktif' => (float) optional($activeRevision)->total_pagu,
            'jumlah_item_anggaran_revisi_aktif' => $activeItems->count(),
            'jumlah_item_anggaran_aktif' => $activeItems->where('status_aktif', true)->count(),
        ];

        return view('dipas.revisions.create', compact('dipa', 'activeRevision', 'nextRevisionNumber', 'summary'));
    }

    public function storeRevision(Request $request, MasterDipa $dipa)
    {
        $dipa->load(['activeRevision.items', 'revisions']);

        $nextRevisionNumber = ((int) $dipa->revisions->max('nomor_revisi')) + 1;

        $validated = $request->validate([
            'tanggal_revisi' => 'required|date',
            'total_pagu' => 'required|numeric|min:0',
            'file_dokumen_dipa' => 'nullable|file|mimes:pdf|max:5120',
            'keterangan' => 'nullable|string',
            'salin_item_anggaran' => 'nullable|boolean',
            'redirect_action' => 'nullable|string|in:save,save_and_manage',
            'nomor_revisi' => [
                'required',
                'integer',
                Rule::unique('dipa_revisions', 'nomor_revisi')->where(
                    fn ($query) => $query->where('master_dipa_id', $dipa->id)
                ),
            ],
        ]);

        if ((int) $validated['nomor_revisi'] !== $nextRevisionNumber) {
            return back()
                ->withInput()
                ->withErrors(['nomor_revisi' => 'Nomor revisi baru sudah berubah. Silakan muat ulang halaman dan coba lagi.']);
        }

        $newRevision = DB::transaction(function () use ($request, $validated, $dipa) {
            $filePath = $request->hasFile('file_dokumen_dipa')
                ? $request->file('file_dokumen_dipa')->store('dipa/documents', 'public')
                : null;

            $revision = RiwayatRevisiDipa::create([
                'master_dipa_id' => $dipa->id,
                'nomor_revisi' => (int) $validated['nomor_revisi'],
                'tanggal_revisi' => $validated['tanggal_revisi'],
                'total_pagu' => $validated['total_pagu'],
                'file_dokumen_dipa' => $filePath,
                'keterangan' => $validated['keterangan'] ?? null,
                'is_active' => false,
            ]);

            if (! empty($validated['salin_item_anggaran']) && $dipa->activeRevision) {
                $clonePayload = $dipa->activeRevision->items->map(function ($item) use ($revision) {
                    return [
                        'dipa_revision_id' => $revision->id,
                        'coa_id' => $item->coa_id,
                        'nilai_pagu' => $item->nilai_pagu,
                        'status_aktif' => $item->status_aktif,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->all();

                if (! empty($clonePayload)) {
                    DetailDipa::insert($clonePayload);
                }
            }

            return $revision;
        });

        $copiedItemMessage = ! empty($validated['salin_item_anggaran'])
            ? ' Item anggaran dari revisi aktif sebelumnya berhasil disalin.'
            : '';

        $message = 'Revisi DIPA ' . $dipa->nomor_dipa . ' nomor ' . $newRevision->nomor_revisi . ' berhasil dibuat sebagai draft nonaktif.' . $copiedItemMessage;

        if (($validated['redirect_action'] ?? 'save') === 'save_and_manage') {
            return redirect()
                ->route('dipas.show', $dipa)
                ->with('success', $message . ' Anda dapat meninjau histori revisi atau mengaktifkannya secara manual setelah item diverifikasi.');
        }

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', $message);
    }

    public function edit(MasterDipa $dipa)
    {
        return redirect()
            ->route('dipas.index')
            ->with('info', 'Form edit header DIPA untuk ' . $dipa->nomor_dipa . ' akan disiapkan pada tahap berikutnya.');
    }

    public function revisions(MasterDipa $dipa)
    {
        return redirect()
            ->route('dipas.revisions.create', $dipa);
    }

    public function toggle(MasterDipa $dipa)
    {
        $dipa->update([
            'status_aktif' => ! $dipa->status_aktif,
        ]);

        return redirect()
            ->route('dipas.index')
            ->with('success', 'Status DIPA ' . $dipa->nomor_dipa . ' berhasil diperbarui.');
    }

    public function storeItem(Request $request, MasterDipa $dipa)
    {
        $activeRevision = $dipa->activeRevision;

        if (! $activeRevision) {
            return back()->with('error', 'DIPA ini belum memiliki revisi aktif.');
        }

        $validated = $request->validate([
            'coa_id' => 'required|exists:master_coas,id',
            'nilai_pagu' => 'required|numeric|min:0',
            'status_aktif' => 'required|boolean',
        ]);

        DetailDipa::create([
            'dipa_revision_id' => $activeRevision->id,
            'coa_id' => $validated['coa_id'],
            'nilai_pagu' => $validated['nilai_pagu'],
            'status_aktif' => (bool) $validated['status_aktif'],
        ]);

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'Item anggaran berhasil ditambahkan ke revisi aktif.');
    }

    public function toggleItem(MasterDipa $dipa, DetailDipa $item)
    {
        abort_unless($item->dipaRevision?->master_dipa_id === $dipa->id, 404);

        $item->update([
            'status_aktif' => ! $item->status_aktif,
        ]);

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'Status item anggaran berhasil diperbarui.');
    }

    public function destroyItem(MasterDipa $dipa, DetailDipa $item)
    {
        abort_unless($item->dipaRevision?->master_dipa_id === $dipa->id, 404);

        $item->delete();

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'Item anggaran berhasil dihapus dari revisi aktif.');
    }

    public function activateRevision(MasterDipa $dipa, RiwayatRevisiDipa $revision)
    {
        abort_unless($revision->master_dipa_id === $dipa->id, 404);

        DB::transaction(function () use ($dipa, $revision) {
            $dipa->revisions()->update(['is_active' => false]);
            $revision->update(['is_active' => true]);
            $dipa->update(['revisi_aktif_ke' => $revision->nomor_revisi]);
        });

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'Revisi aktif DIPA berhasil diperbarui.');
    }
}
