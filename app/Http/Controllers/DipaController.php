<?php

namespace App\Http\Controllers;

use App\Models\KontrakPengadaan;
use App\Models\MasterDipa;
use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\RiwayatRevisiDipa;
use App\Models\Tagihan;
use Illuminate\Support\Facades\Storage;
use App\Support\PdfCompressor;
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
            'pok_token' => 'nullable|uuid',
        ]);

        $rekapPok = null;

        $dipa = DB::transaction(function () use ($request, $validated, &$rekapPok) {
            $dipa = MasterDipa::create([
                'nomor_dipa' => $validated['nomor_dipa'],
                'tahun_anggaran' => $validated['tahun_anggaran'],
                'tanggal_disahkan' => $validated['tanggal_disahkan'],
                'revisi_aktif_ke' => 0,
                'status_aktif' => (bool) $validated['status_aktif'],
            ]);

            $filePath = $request->hasFile('file_dokumen_dipa')
                ? PdfCompressor::storeCompressed($request->file('file_dokumen_dipa'), 'dipa/documents', 'local')
                : null;

            $revision = RiwayatRevisiDipa::create([
                'master_dipa_id' => $dipa->id,
                'nomor_revisi' => 0,
                'tanggal_revisi' => $validated['tanggal_revisi'] ?? $validated['tanggal_disahkan'],
                'total_pagu' => $validated['total_pagu'],
                'file_dokumen_dipa' => $filePath,
                'keterangan' => $validated['keterangan'] ?? null,
                'is_active' => true,
            ]);

            // Form Tambah DIPA dengan POK terunggah: seluruh baris detil POK
            // langsung menjadi COA + item pada revisi awal.
            if (! empty($validated['pok_token'])) {
                $pokPath = 'pok-import/' . $validated['pok_token'] . '.pdf';
                if (Storage::disk('local')->exists($pokPath)) {
                    $hasil = (new \App\Support\Pok\PokPdfParser())->parse(Storage::disk('local')->path($pokPath));
                    $rekapPok = (new \App\Support\Pok\PokImporter())->importRows($hasil['rows'], $revision);
                    Storage::disk('local')->delete($pokPath);
                }
            }

            return $dipa;
        });

        $message = 'DIPA ' . $dipa->nomor_dipa . ' berhasil dibuat beserta revisi awal aktif.';
        if ($rekapPok !== null) {
            $message .= ' ' . $rekapPok['dibuat'] . ' COA hasil impor POK ikut dibuat otomatis.';
        }

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

        // ID item yang dirujuk tagihan — dipakai UI untuk menonaktifkan tombol
        // hapus (selaras dengan guard destroyItem, termasuk tagihan yang masih
        // berproses dan belum punya realisasi).
        $allItemIds = collect(optional($activeRevision)->items ?? [])->pluck('id');
        $itemDipakaiTagihan = $allItemIds->isEmpty()
            ? collect()
            : Tagihan::whereIn('dipa_revision_item_id', $allItemIds)
                ->distinct()
                ->pluck('dipa_revision_item_id');

        return view('dipas.show', compact('dipa', 'activeRevision', 'items', 'summary', 'coaOptions', 'kdAkunOptions', 'itemDipakaiTagihan'));
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
            'pok_token' => 'nullable|uuid',
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

        $rekapPok = null;

        $newRevision = DB::transaction(function () use ($request, $validated, $dipa, &$rekapPok) {
            $filePath = $request->hasFile('file_dokumen_dipa')
                ? PdfCompressor::storeCompressed($request->file('file_dokumen_dipa'), 'dipa/documents', 'local')
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

            // POK revisi terunggah: seluruh baris detil POK menjadi item revisi
            // baru. Nilai POK otoritatif, jadi salin-item dilewati agar pagu
            // lama tidak menimpa angka revisi.
            if (! empty($validated['pok_token'])) {
                $pokPath = 'pok-import/' . $validated['pok_token'] . '.pdf';
                if (Storage::disk('local')->exists($pokPath)) {
                    $hasil = (new \App\Support\Pok\PokPdfParser())->parse(Storage::disk('local')->path($pokPath));
                    $rekapPok = (new \App\Support\Pok\PokImporter())->importRows($hasil['rows'], $revision);
                    Storage::disk('local')->delete($pokPath);

                    return $revision;
                }
            }

            if (! empty($validated['salin_item_anggaran']) && $dipa->activeRevision) {
                $clonePayload = $dipa->activeRevision->items->map(function ($item) use ($revision) {
                    return [
                        'dipa_revision_id' => $revision->id,
                        'coa_id' => $item->coa_id,
                        'nilai_pagu' => $item->nilai_pagu,
                        'volume' => $item->volume,
                        'satuan' => $item->satuan,
                        'harga_satuan' => $item->harga_satuan,
                        'status_aktif' => $item->status_aktif,
                        'blokir' => $item->blokir,
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

        $copiedItemMessage = '';
        if ($rekapPok !== null) {
            $copiedItemMessage = ' ' . $rekapPok['dibuat'] . ' COA hasil impor POK ikut dibuat otomatis.';
        } elseif (! empty($validated['salin_item_anggaran'])) {
            $copiedItemMessage = ' COA dari revisi aktif sebelumnya berhasil disalin.';
        }

        $message = 'Revisi DIPA ' . $dipa->nomor_dipa . ' nomor ' . $newRevision->nomor_revisi . ' berhasil dibuat sebagai draft nonaktif.' . $copiedItemMessage;

        if (($validated['redirect_action'] ?? 'save') === 'save_and_manage') {
            return redirect()
                ->route('dipas.show', $dipa)
                ->with('success', $message . ' Anda dapat meninjau histori revisi atau mengaktifkannya secara manual setelah COA diverifikasi.');
        }

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', $message);
    }

    public function edit(MasterDipa $dipa)
    {
        $dipa->load('activeRevision');

        return view('dipas.edit', compact('dipa'));
    }

    public function update(Request $request, MasterDipa $dipa)
    {
        $validated = $request->validate([
            'nomor_dipa' => ['required', 'string', 'max:255', Rule::unique('master_dipas', 'nomor_dipa')->ignore($dipa->id)],
            'tahun_anggaran' => 'required|integer|min:2000|max:2100',
            'tanggal_disahkan' => 'required|date',
            'status_aktif' => 'required|boolean',
        ]);

        $dipa->update([
            'nomor_dipa' => $validated['nomor_dipa'],
            'tahun_anggaran' => $validated['tahun_anggaran'],
            'tanggal_disahkan' => $validated['tanggal_disahkan'],
            'status_aktif' => (bool) $validated['status_aktif'],
        ]);

        return redirect()
            ->route('dipas.index')
            ->with('success', 'Header DIPA ' . $dipa->nomor_dipa . ' berhasil diperbarui.');
    }

    /**
     * Hapus terjaga: DIPA hanya boleh dihapus bila benar-benar kosong —
     * tanpa item anggaran dan tanpa rujukan tagihan/kontrak. DIPA yang
     * pernah dipakai cukup dinonaktifkan agar jejak audit anggaran utuh.
     * (Pola sama dengan CoaController::destroy.)
     */
    public function destroy(MasterDipa $dipa)
    {
        $dipa->load('revisions.items');

        $itemIds = $dipa->revisions->flatMap->items->pluck('id');

        $adaItem = $itemIds->isNotEmpty();
        $adaTagihan = Tagihan::where('master_dipa_id', $dipa->id)->exists()
            || ($itemIds->isNotEmpty() && Tagihan::whereIn('dipa_revision_item_id', $itemIds)->exists());
        $adaKontrak = KontrakPengadaan::where('master_dipa_id', $dipa->id)->exists();

        if ($adaItem || $adaTagihan || $adaKontrak) {
            return redirect()
                ->route('dipas.show', $dipa)
                ->with('error', 'DIPA tidak dapat dihapus karena sudah memiliki COA atau dipakai transaksi. Gunakan status Nonaktif.');
        }

        $label = $dipa->nomor_dipa;

        // forceDelete (bukan soft delete): DIPA dijamin kosong oleh guard di
        // atas, dan baris soft-deleted akan menyandera unique nomor_dipa —
        // padahal kasus utama fitur ini justru salah ketik nomor.
        DB::transaction(function () use ($dipa) {
            foreach ($dipa->revisions as $revision) {
                if ($revision->file_dokumen_dipa) {
                    Storage::disk('local')->delete($revision->file_dokumen_dipa);
                }
                $revision->forceDelete();
            }
            $dipa->forceDelete();
        });

        return redirect()
            ->route('dipas.index')
            ->with('success', 'DIPA ' . $label . ' berhasil dihapus.');
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
            'nilai_pagu' => 'required_without_all:volume,harga_satuan|nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'satuan' => 'nullable|string|max:30',
            'harga_satuan' => 'nullable|numeric|min:0',
            'status_aktif' => 'required|boolean',
            'blokir' => 'nullable|boolean',
        ]);

        // Aturan POK: Jumlah Biaya = Volume × Harga Satuan. Bila keduanya
        // diisi, nilai pagu dihitung dari sana agar rinciannya selalu konsisten.
        $volume = $validated['volume'] ?? null;
        $hargaSatuan = $validated['harga_satuan'] ?? null;
        $nilaiPagu = ($volume !== null && $hargaSatuan !== null)
            ? round((float) $volume * (float) $hargaSatuan, 2)
            : (float) ($validated['nilai_pagu'] ?? 0);

        DetailDipa::create([
            'dipa_revision_id' => $activeRevision->id,
            'coa_id' => $validated['coa_id'],
            'nilai_pagu' => $nilaiPagu,
            'volume' => $volume,
            'satuan' => $validated['satuan'] ?? null,
            'harga_satuan' => $hargaSatuan,
            'status_aktif' => (bool) $validated['status_aktif'],
            'blokir' => (bool) ($validated['blokir'] ?? false),
        ]);

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'COA berhasil ditambahkan ke revisi aktif.');
    }

    public function toggleItem(MasterDipa $dipa, DetailDipa $item)
    {
        abort_unless($item->dipaRevision?->master_dipa_id === $dipa->id, 404);

        $item->update([
            'status_aktif' => ! $item->status_aktif,
        ]);

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'Status COA berhasil diperbarui.');
    }

    /**
     * Hapus terjaga: COA (item revisi) yang sudah dirujuk tagihan atau punya
     * realisasi tidak boleh dihapus — FK database memang menolaknya
     * (ON DELETE RESTRICT), tapi tanpa guard user melihat error 500.
     */
    public function destroyItem(MasterDipa $dipa, DetailDipa $item)
    {
        abort_unless($item->dipaRevision?->master_dipa_id === $dipa->id, 404);

        $dipakaiTagihan = Tagihan::where('dipa_revision_item_id', $item->id)->exists();
        $adaRealisasi = \App\Models\RealisasiAnggaran::where('dipa_revision_item_id', $item->id)->exists();

        if ($dipakaiTagihan || $adaRealisasi) {
            return redirect()
                ->route('dipas.show', $dipa)
                ->with('error', 'COA ini tidak dapat dihapus karena sudah dipakai tagihan atau memiliki realisasi. Gunakan Nonaktifkan agar tidak dipilih pada tagihan baru.');
        }

        try {
            $item->delete();
        } catch (\Illuminate\Database\QueryException) {
            // Jaring pengaman untuk rujukan lain (mis. dokumen pencairan/komponen).
            return redirect()
                ->route('dipas.show', $dipa)
                ->with('error', 'COA ini tidak dapat dihapus karena masih dirujuk data lain. Gunakan Nonaktifkan.');
        }

        return redirect()
            ->route('dipas.show', $dipa)
            ->with('success', 'COA berhasil dihapus dari revisi aktif.');
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
