<?php

namespace App\Http\Controllers;

use App\Models\KontrakMitraJasa;
use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaPenjualan;
use App\Models\TagihanJasa;
use App\Services\MitraJasaKonsesiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class MitraPortalController extends Controller
{
    public function showTagihanJasa($id)
    {
        $mitra = $this->currentMitraJasa();

        $tagihan = TagihanJasa::with([
            'mitra',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])
            ->where('mitra_jasa_id', $mitra->id)
            ->whereIn('status', ['PUBLISHED', 'LUNAS'])
            ->findOrFail($id);

        return view('dashboard.mitra_tagihan_jasa_show', compact('mitra', 'tagihan'));
    }

    public function invoiceTagihanJasaPdf(Request $request, $id)
    {
        $mitra = $this->currentMitraJasa();

        $tagihan = TagihanJasa::with([
            'mitra',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])
            ->where('mitra_jasa_id', $mitra->id)
            ->whereIn('status', ['PUBLISHED', 'LUNAS'])
            ->findOrFail($id);

        $terbilang = function_exists('terbilang_rupiah')
            ? terbilang_rupiah((float) $tagihan->total_tagihan)
            : trim(terbilang((float) $tagihan->total_tagihan)) . ' Rupiah';

        $pdf = Pdf::loadView('tagihan_jasa.pdf', compact('tagihan', 'terbilang'))
            ->setPaper('a4', 'portrait');

        $fileName = 'nota-tagihan-' . str_replace(['/', '\\'], '-', $tagihan->nomor_tagihan) . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($fileName);
        }

        return $pdf->stream($fileName);
    }

    public function downloadSuratPengantarFinal($id)
    {
        $mitra = $this->currentMitraJasa();

        $tagihan = TagihanJasa::query()
            ->where('mitra_jasa_id', $mitra->id)
            ->whereIn('status', ['PUBLISHED', 'LUNAS'])
            ->findOrFail($id);

        abort_unless(
            $tagihan->file_surat_pengantar_final
                && Storage::disk('public')->exists($tagihan->file_surat_pengantar_final),
            404
        );

        return Storage::disk('public')->download($tagihan->file_surat_pengantar_final);
    }

    public function downloadKontrak(KontrakMitraJasa $kontrak)
    {
        $mitra = $this->currentMitraJasa();

        abort_unless((int) $kontrak->mitra_jasa_id === (int) $mitra->id, 404);
        abort_unless($kontrak->file_kontrak && Storage::disk('public')->exists($kontrak->file_kontrak), 404);

        return Storage::disk('public')->download($kontrak->file_kontrak);
    }

    public function layananAktif()
    {
        $mitra = $this->currentMitraJasa();
        $selectedLayananIds = $mitra->layananJasaAktif()->pluck('layanan_jasas.id')->all();
        $visibleLayananIds = $this->buildVisibleLayananIds($selectedLayananIds);
        $layananTreeItems = LayananJasa::query()
            ->whereIn('id', $visibleLayananIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        return view('dashboard.mitra_layanan_aktif', compact(
            'mitra',
            'layananTreeItems',
            'selectedLayananIds',
            'visibleLayananIds'
        ));
    }

    public function konsesiPenjualan()
    {
        $mitra = $this->currentMitraJasa();
        $selectedLayananIds = $mitra->layananJasaAktif()->pluck('layanan_jasas.id')->all();
        $visibleLayananIds = $this->buildVisibleLayananIds($selectedLayananIds);
        $layananTreeItems = LayananJasa::query()
            ->whereIn('id', $visibleLayananIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();
        $pjp2uLayananIds = LayananJasa::all()->filter(fn($l) => $l->isPjp2u())->pluck('id');
        $penjualans = $mitra->penjualan()
            ->with(['konsesi', 'layananJasa', 'tagihanJasa'])
            ->whereNotIn('layanan_jasa_id', $pjp2uLayananIds)
            ->latest('periode_mulai')
            ->latest('id')
            ->paginate(10);
        $tagihanKonsesi = $mitra->penjualan()
            ->with(['layananJasa', 'tagihanJasa'])
            ->whereNotIn('layanan_jasa_id', $pjp2uLayananIds)
            ->whereNotNull('tagihan_jasa_id')
            ->latest('periode_mulai')
            ->latest('id')
            ->get();

        return view('dashboard.mitra_konsesi_penjualan', compact(
            'mitra',
            'penjualans',
            'tagihanKonsesi',
            'layananTreeItems',
            'selectedLayananIds',
            'visibleLayananIds'
        ));
    }

    public function pjp2uPenjualan()
    {
        $mitra = $this->currentMitraJasa();
        $selectedLayananIds = $mitra->layananJasaAktif()->pluck('layanan_jasas.id')->all();
        $visibleLayananIds = $this->buildVisibleLayananIds($selectedLayananIds);
        $layananTreeItems = LayananJasa::query()
            ->whereIn('id', $visibleLayananIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();
            
        $penjualans = $mitra->penjualan()
            ->with(['konsesi', 'layananJasa', 'tagihanJasa'])
            ->whereNotNull('penerbangan_details')
            ->latest('periode_mulai')
            ->latest('id')
            ->paginate(10);
            
        $tagihanPjp2u = $mitra->penjualan()
            ->with(['layananJasa', 'tagihanJasa'])
            ->whereNotNull('penerbangan_details')
            ->whereNotNull('tagihan_jasa_id')
            ->latest('periode_mulai')
            ->latest('id')
            ->get();

        return view('dashboard.mitra_pjp2u_penjualan', compact(
            'mitra',
            'penjualans',
            'tagihanPjp2u',
            'layananTreeItems',
            'selectedLayananIds',
            'visibleLayananIds'
        ));
    }

    public function createPenjualan()
    {
        $mitra = $this->currentMitraJasa();
        $selectedLayananIds = $mitra->layananJasaAktif()->pluck('layanan_jasas.id')->all();
        $visibleLayananIds = $this->buildVisibleLayananIds($selectedLayananIds);
        $layananTreeItems = LayananJasa::query()
            ->whereIn('id', $visibleLayananIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();
        $konsesiContext = $this->resolveKonsesiContext($mitra);

        return view('dashboard.mitra_penjualan_form', [
            'mitra' => $mitra,
            'konsesiContext' => $konsesiContext,
            'layananTreeItems' => $layananTreeItems,
            'selectedLayananIds' => $selectedLayananIds,
            'penjualan' => new MitraJasaPenjualan([
                'periode_tipe' => 'harian',
                'periode_mulai' => now()->startOfMonth()->toDateString(),
                'periode_selesai' => now()->endOfMonth()->toDateString(),
            ]),
        ]);
    }

    public function storePenjualan(Request $request, MitraJasaKonsesiService $service)
    {
        $mitra = $this->currentMitraJasa();
        $validated = $request->validate([
            'layanan_jasa_id' => ['required', Rule::in($this->tagihanKonsesiLayanans($mitra)->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'periode_tipe' => ['required', Rule::in(['harian', 'mingguan'])],
            'periode_mulai' => ['required', 'date'],
            'periode_selesai' => ['required', 'date', 'after_or_equal:periode_mulai'],
            'total_omzet' => ['required', 'numeric', 'min:0'],
            'total_transaksi' => ['nullable', 'integer', 'min:0'],
            'file_laporan' => ['required', 'file', 'mimes:pdf,xlsx,xls,csv,jpg,jpeg,png', 'max:5120'],
            'catatan_mitra' => ['nullable', 'string', 'max:1000'],
        ]);

        $konsesiContext = $this->resolveKonsesiContext($mitra, (int) $validated['layanan_jasa_id']);
        if (! $konsesiContext['layanan']) {
            return back()
                ->withInput()
                ->with('error', 'Mitra belum memiliki layanan Konsesi aktif. Hubungi pengelola untuk mengaktifkan layanan Konsesi.');
        }

        $bulan = (int) date('m', strtotime($validated['periode_mulai']));
        $tahun = (int) date('Y', strtotime($validated['periode_mulai']));

        // Find an OPEN parent (draft/diajukan) for this mitra + layanan + bulan
        // If only finalized parents exist (diverifikasi/ditagihkan), we create a NEW parent
        $parent = MitraJasaPenjualan::where('mitra_jasa_id', $mitra->id)
            ->where('layanan_jasa_id', $konsesiContext['layanan']->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNull('penerbangan_details')
            ->whereIn('status', ['draft', 'diajukan'])
            ->first();

        $hasil = $service->hitungKonsesiLayanan($konsesiContext['layanan'], (float) $validated['total_omzet']);

        // Check for overlapping period in existing details
        if ($parent) {
            $overlap = $parent->details()
                ->where(function ($q) use ($validated) {
                    $q->where('periode_mulai', '<=', $validated['periode_selesai'])
                      ->where('periode_selesai', '>=', $validated['periode_mulai']);
                })
                ->first();

            if ($overlap) {
                $tgl = fn ($d) => \Carbon\Carbon::parse($d)->format('d/m/Y');
                return back()
                    ->withInput()
                    ->with('error', 'Periode yang Anda input (' . $tgl($validated['periode_mulai']) . ' s.d. ' . $tgl($validated['periode_selesai']) . ') bertabrakan dengan laporan yang sudah ada (' . $tgl($overlap->periode_mulai) . ' s.d. ' . $tgl($overlap->periode_selesai) . ').');
            }
        }

        if ($request->hasFile('file_laporan')) {
            $validated['file_laporan'] = $request->file('file_laporan')->store('mitra-jasa/penjualan', 'public');
        }

        DB::transaction(function () use ($parent, $mitra, $konsesiContext, $validated, $hasil, $bulan, $tahun) {
            if (! $parent) {
                $parent = MitraJasaPenjualan::create([
                    'mitra_jasa_id' => $mitra->id,
                    'mitra_jasa_konsesi_id' => $konsesiContext['konsesi']?->id,
                    'kontrak_mitra_jasa_id' => $konsesiContext['kontrak']?->id,
                    'layanan_jasa_id' => $konsesiContext['layanan']->id,
                    ...($this->hasSourceTagihanColumn() ? [
                        'source_tagihan_jasa_id' => $this->sourceTagihanForLayanan($mitra, (int) $konsesiContext['layanan']->id)?->id,
                    ] : []),
                    'periode_tipe' => 'bulanan',
                    'periode_mulai' => \Carbon\Carbon::create($tahun, $bulan, 1)->startOfMonth(),
                    'periode_selesai' => \Carbon\Carbon::create($tahun, $bulan, 1)->endOfMonth(),
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'total_omzet' => 0,
                    'persentase_konsesi' => $hasil['persentase_konsesi'],
                    'nilai_konsesi' => 0,
                    'nilai_minimum_guarantee' => $hasil['nilai_minimum_guarantee'],
                    'nilai_tagihan' => 0,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }

            // Create detail record
            $parent->details()->create([
                'periode_mulai' => $validated['periode_mulai'],
                'periode_selesai' => $validated['periode_selesai'],
                'total_omzet' => $validated['total_omzet'],
                'total_transaksi' => $validated['total_transaksi'] ?? null,
                'file_laporan' => $validated['file_laporan'] ?? null,
                'catatan_mitra' => $validated['catatan_mitra'] ?? null,
                'submitted_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // Recalculate parent totals
            $parent->recalculateTotals();
        });

        return redirect()
            ->route('mitra.konsesi-penjualan')
            ->with('success', 'Laporan penjualan berhasil ditambahkan ke ringkasan bulan ' . $bulan . '/' . $tahun . '.');
    }

    private function pjp2uLayanans($mitra)
    {
        $hakLayananIds = $mitra->pjp2uAktif()
            ->pluck('layanan_jasa_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($hakLayananIds->isEmpty()) {
            return collect();
        }

        return $mitra->layananJasaAktif()
            ->whereIn('layanan_jasas.id', $hakLayananIds)
            ->with('parent.parent.parent.parent.parent')
            ->get()
            ->filter(fn ($layanan) => $layanan->isPjp2u())
            ->values();
    }

    public function createPax()
    {
        $mitra = $this->currentMitraJasa();
        $pjp2uLayanans = $this->pjp2uLayanans($mitra);

        abort_if($pjp2uLayanans->isEmpty(), 403, 'Anda tidak memiliki layanan PJP2U yang aktif.');

        $visibleLayananIds = $this->buildVisibleLayananIds($pjp2uLayanans->pluck('id')->all());
        $selectedLayananIds = request('layanan_jasa_id') ? [(int) request('layanan_jasa_id')] : [];

        $layananTreeItems = LayananJasa::query()
            ->whereIn('id', $visibleLayananIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();
            
        $selectedLayananId = request('layanan_jasa_id') ?? $pjp2uLayanans->first()->id;
        $layanan = $pjp2uLayanans->firstWhere('id', $selectedLayananId);
        $hakPjp2u = $this->activePjp2uHak($mitra, (int) $layanan?->id);
        $kontrak = $hakPjp2u?->kontrakMitraJasa;

        $konsesiContext = [
            'konsesi' => null, 
            'layanan' => $layanan,
            'layanans' => $pjp2uLayanans,
            'kontrak' => $kontrak,
            'persentase' => $layanan?->persentase_konsesi ?? \App\Services\MitraJasaKonsesiService::DEFAULT_PERSENTASE_KONSESI,
        ];

        return view('dashboard.mitra_pax_form', [
            'mitra' => $mitra,
            'konsesiContext' => $konsesiContext,
            'layananTreeItems' => $layananTreeItems,
            'selectedLayananIds' => $selectedLayananIds,
            'penjualan' => new MitraJasaPenjualan([
                'periode_tipe' => 'harian',
                'periode_mulai' => now()->startOfMonth()->toDateString(),
                'periode_selesai' => now()->endOfMonth()->toDateString(),
            ]),
        ]);
    }

    public function storePax(Request $request, MitraJasaKonsesiService $service)
    {
        $mitra = $this->currentMitraJasa();
        $pjp2uLayanans = $this->pjp2uLayanans($mitra);
        
        $validated = $request->validate([
            'layanan_jasa_id' => ['required', Rule::in($pjp2uLayanans->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'periode_tipe' => ['required', Rule::in(['harian', 'mingguan'])],
            'periode_mulai' => ['required', 'date'],
            'periode_selesai' => ['required', 'date', 'after_or_equal:periode_mulai'],
            'penerbangan' => ['required', 'array', 'min:1'],
            'penerbangan.*.nomor_penerbangan' => ['required', 'string', 'max:255'],
            'penerbangan.*.pax_dewasa' => ['required', 'integer', 'min:0'],
            'penerbangan.*.pax_anak' => ['required', 'integer', 'min:0'],
            'penerbangan.*.pax_bayi' => ['required', 'integer', 'min:0'],
            'file_laporan' => ['required', 'file', 'mimes:pdf,xlsx,xls,csv,jpg,jpeg,png', 'max:5120'],
            'catatan_mitra' => ['nullable', 'string', 'max:1000'],
        ]);

        $layanan = $pjp2uLayanans->firstWhere('id', (int) $validated['layanan_jasa_id']);
        if (! $layanan) {
            return back()->withInput()->with('error', 'Layanan tidak valid.');
        }

        $hakPjp2u = $this->activePjp2uHak($mitra, (int) $layanan->id);
        if (! $hakPjp2u) {
            return back()->withInput()->with('error', 'Hak PJP2U untuk layanan ini belum aktif. Hubungi pengelola jasa.');
        }

        $totalPaxDewasa = 0;
        $totalPaxAnak = 0;
        $totalPaxBayi = 0;
        $nomorPenerbangans = [];

        foreach ($validated['penerbangan'] as $flight) {
            $totalPaxDewasa += (int) $flight['pax_dewasa'];
            $totalPaxAnak += (int) $flight['pax_anak'];
            $totalPaxBayi += (int) $flight['pax_bayi'];
            $nomorPenerbangans[] = trim($flight['nomor_penerbangan']);
        }

        $totalPax = $totalPaxDewasa + $totalPaxAnak + $totalPaxBayi;
        
        $tarifDasar = (float) ($layanan->tarif_dasar ?? 0);
        $nilaiTagihan = $totalPax * $tarifDasar;

        if ($request->hasFile('file_laporan')) {
            $validated['file_laporan'] = $request->file('file_laporan')->store('mitra-jasa/pax', 'public');
        }
        
        $kontrak = $hakPjp2u->kontrakMitraJasa;

        MitraJasaPenjualan::create([
            'layanan_jasa_id' => $layanan->id,
            'periode_tipe' => $validated['periode_tipe'],
            'periode_mulai' => $validated['periode_mulai'],
            'periode_selesai' => $validated['periode_selesai'],
            'catatan_mitra' => $validated['catatan_mitra'] ?? null,
            'file_laporan' => $validated['file_laporan'] ?? null,
            'mitra_jasa_id' => $mitra->id,
            'mitra_jasa_konsesi_id' => null,
            'kontrak_mitra_jasa_id' => $kontrak?->id,
            ...($this->hasSourceTagihanColumn() ? [
                'source_tagihan_jasa_id' => $this->sourceTagihanForLayanan($mitra, (int) $layanan->id)?->id,
            ] : []),
            'bulan' => (int) date('m', strtotime($validated['periode_mulai'])),
            'tahun' => (int) date('Y', strtotime($validated['periode_mulai'])),
            'nomor_penerbangan' => implode(', ', array_unique($nomorPenerbangans)),
            'penerbangan_details' => $validated['penerbangan'],
            'total_omzet' => $totalPax, 
            'persentase_konsesi' => 0,
            'nilai_konsesi' => 0,
            'nilai_minimum_guarantee' => null,
            'nilai_tagihan' => $nilaiTagihan,
            'status' => 'diajukan',
            'submitted_at' => now(),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('mitra.pjp2u-penjualan')
            ->with('success', 'Laporan Pax PJP2U berhasil dikirim dan menunggu verifikasi.');
    }

    private function activePjp2uHak($mitra, int $layananId)
    {
        if ($layananId <= 0) {
            return null;
        }

        return $mitra->pjp2uAktif()
            ->with('kontrakMitraJasa')
            ->where('layanan_jasa_id', $layananId)
            ->latest('tanggal_mulai')
            ->latest('id')
            ->first();
    }

    public function showPenjualan(MitraJasaPenjualan $penjualan)
    {
        $mitra = $this->currentMitraJasa();

        abort_unless((int) $penjualan->mitra_jasa_id === (int) $mitra->id, 404);

        $penjualan->load([
            'mitraJasa',
            'konsesi',
            'layananJasa.parent.parent.parent.parent.parent',
            'kontrakMitraJasa',
            'tagihanJasa',
            'verifiedByUser',
            'createdByUser',
            'details' => fn ($q) => $q->orderBy('periode_mulai'),
        ]);

        return view('dashboard.mitra_penjualan_show', compact('mitra', 'penjualan'));
    }

    public function submitPenjualan(MitraJasaPenjualan $penjualan)
    {
        $mitra = $this->currentMitraJasa();
        abort_unless((int) $penjualan->mitra_jasa_id === (int) $mitra->id, 403);

        if ($penjualan->status !== 'draft') {
            return back()->with('error', 'Hanya laporan berstatus draft yang dapat diajukan.');
        }

        if ($penjualan->details()->count() === 0) {
            return back()->with('error', 'Tidak dapat mengajukan laporan tanpa detail laporan.');
        }

        $penjualan->update([
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Laporan berhasil diajukan untuk verifikasi.');
    }

    public function destroyPenjualan(MitraJasaPenjualan $penjualan)
    {
        $mitra = $this->currentMitraJasa();

        abort_unless((int) $penjualan->mitra_jasa_id === (int) $mitra->id, 403, 'Akses ditolak.');

        if (in_array($penjualan->status, ['diverifikasi', 'ditagihkan'])) {
            return back()->with('error', 'Laporan yang sudah diverifikasi atau ditagihkan tidak dapat dihapus.');
        }

        // Delete all detail files
        foreach ($penjualan->details as $detail) {
            if ($detail->file_laporan) {
                Storage::disk('public')->delete($detail->file_laporan);
            }
        }
        $penjualan->details()->delete();

        if ($penjualan->file_laporan) {
            Storage::disk('public')->delete($penjualan->file_laporan);
        }

        $penjualan->delete();

        return back()->with('success', 'Laporan berhasil dihapus.');
    }

    public function profile()
    {
        $user = auth()->user();
        $mitra = $this->currentMitraJasa();

        return view('dashboard.mitra_profile', compact('user', 'mitra'));
    }

    private function resolveKonsesiContext(MitraJasa $mitra, ?int $layananId = null): array
    {
        $layanans = $this->tagihanKonsesiLayanans($mitra);
        $konsesi = $mitra->konsesiAktif()
            ->with(['kontrakMitraJasa', 'layananJasa'])
            ->when($layananId, fn ($query) => $query->where('layanan_jasa_id', $layananId))
            ->orderByDesc('tanggal_mulai')
            ->first();

        $layanan = $layananId
            ? $layanans->firstWhere('id', $layananId)
            : ($konsesi?->layananJasa ?: $layanans->first());

        $kontrak = $konsesi?->kontrakMitraJasa
            ?: $mitra->kontrak()->orderByDesc('tanggal_kontrak')->orderByDesc('id')->first();

        return [
            'konsesi' => $konsesi,
            'layanan' => $layanan,
            'layanans' => $layanans,
            'kontrak' => $kontrak,
            'persentase' => $layanan?->persentase_konsesi ?? MitraJasaKonsesiService::DEFAULT_PERSENTASE_KONSESI,
        ];
    }

    private function tagihanKonsesiLayanans(MitraJasa $mitra)
    {
        return $mitra->konsesiAktif()
            ->with('layananJasa')
            ->get()
            ->pluck('layananJasa')
            ->filter()
            ->unique('id')
            ->values();
    }

    private function sourceTagihanForLayanan(MitraJasa $mitra, int $layananId): ?TagihanJasa
    {
        return $mitra->tagihanJasas()
            ->whereHas('details', fn ($query) => $query->where('layanan_jasa_id', $layananId))
            ->latest('tanggal_tagihan')
            ->latest('id')
            ->first();
    }

    private function hasSourceTagihanColumn(): bool
    {
        return Schema::hasColumn('mitra_jasa_penjualan', 'source_tagihan_jasa_id');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();
        $this->currentMitraJasa();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Password lama tidak sesuai.'])
                ->withInput();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password akun berhasil diperbarui.');
    }

    private function currentMitraJasa(): MitraJasa
    {
        $profile = auth()->user()?->profilable;

        abort_unless($profile instanceof MitraJasa, 403);

        return $profile;
    }

    private function buildVisibleLayananIds(array $selectedIds): array
    {
        $visibleIds = collect($selectedIds);
        $itemsById = LayananJasa::query()
            ->whereIn('id', $selectedIds)
            ->with('parent.parent.parent.parent.parent')
            ->get()
            ->keyBy('id');

        foreach ($selectedIds as $id) {
            $parent = $itemsById->get($id)?->parent;
            $guard = 0;

            while ($parent && $guard < 10) {
                $visibleIds->push($parent->id);
                $parent = $parent->parent;
                $guard++;
            }
        }

        return $visibleIds->unique()->values()->all();
    }
}
