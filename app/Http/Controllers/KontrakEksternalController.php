<?php

namespace App\Http\Controllers;

use App\Enums\MekanismePembayaran;
use App\Http\Controllers\Concerns\BuildsVerifikatorSnapshots;
use App\Models\DetailKontrakEksternal;
use App\Models\KontrakEksternal;
use App\Models\KontrakEksternalTermin;
use App\Models\LogStatusDokumen;
use App\Models\MasterPihak;
use App\Models\PotonganTagihan;
use App\Models\Tagihan;
use App\Services\GeminiNamaPekerjaanService;
use App\Services\SuratPesananPdfExtractor;
use App\Support\PdfCompressor;
use App\Support\TerminScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Master Kontrak Eksternal (pola Manajemen SPK): kontrak/Surat Pesanan yang
 * ditandatangani di luar sistem didaftarkan dulu sebagai master + skema termin,
 * lalu tiap termin ditagih satu per satu dari halaman master. Progresi termin:
 * LOCKED → READY_TO_BILL → DRAFT (tagihan dibuat) → SUDAH_DITAGIH (diajukan);
 * SP2D selesai membuka termin berikutnya (DokumenSp2d::unlockNextTerminKontrakEksternal).
 */
class KontrakEksternalController extends Controller
{
    use BuildsVerifikatorSnapshots;

    public function index()
    {
        $kontraks = KontrakEksternal::with(['vendor', 'termin', 'arsipDokumen'])
            ->latest()
            ->get();

        return view('kontrak_eksternal.index', compact('kontraks'));
    }

    public function create()
    {
        return view('kontrak_eksternal.create', [
            'verifikatorOptions' => $this->buildVerifikatorOptions(),
            'vendorOptions' => $this->buildVendorOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMasterPayload($request, isUpdate: false);

        try {
            $terminRows = $this->buildTerminRows($validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        try {
            DB::beginTransaction();

            $vendor = $this->resolveVendor($request);

            $kontrak = KontrakEksternal::create([
                'vendor_id' => $vendor->id,
                'nomor_surat_pesanan' => $validated['nomor_surat_pesanan'],
                'tanggal_surat_pesanan' => $validated['tanggal_surat_pesanan'],
                'sumber' => $validated['sumber'] ?? 'INAPROC',
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'nilai_total_kontrak' => (float) str_replace(',', '', (string) $validated['nilai_total_kontrak']),
                'ada_uang_muka' => (bool) ($validated['ada_uang_muka'] ?? false),
                'nilai_uang_muka' => ($validated['ada_uang_muka'] ?? false)
                    ? (float) str_replace(',', '', (string) ($validated['nilai_uang_muka'] ?? 0))
                    : 0,
                'status_kontrak' => 'DRAFT',
                'ppk_user_id' => $validated['ppk_user_id'],
                'ppspm_user_id' => $validated['ppspm_user_id'],
                'koordinator_keuangan_user_id' => $validated['koordinator_keuangan_user_id'],
                'bendahara_pengeluaran_user_id' => $validated['bendahara_pengeluaran_user_id'],
                'bendahara_penerimaan_user_id' => $validated['bendahara_penerimaan_user_id'],
                'kasubbag_user_id' => $validated['kasubbag_user_id'],
                'created_by' => Auth::id(),
            ]);

            $this->storeSuratPesanan($request, $kontrak);
            $this->createTerminRows($kontrak, $terminRows);

            DB::commit();

            return redirect()->route('kontrak-eksternal.show', $kontrak->id)
                ->with('success', 'Master kontrak berhasil dibuat dengan '.count($terminRows)
                    .' termin. Periksa skema termin lalu Aktifkan untuk mulai menagih.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan kontrak: '.$e->getMessage()]);
        }
    }

    public function show($id)
    {
        $kontrak = KontrakEksternal::with([
            'vendor.rekening',
            'termin.detailKontrakEksternal.tagihan',
            'arsipDokumen',
            'ppkUser.profilable', 'ppspmUser.profilable', 'koordinatorKeuanganUser.profilable',
            'bendaharaPengeluaranUser.profilable', 'bendaharaPenerimaanUser.profilable', 'kasubbagUser.profilable',
        ])->findOrFail($id);

        return view('kontrak_eksternal.show', compact('kontrak'));
    }

    public function edit($id)
    {
        $kontrak = KontrakEksternal::with(['vendor.rekening', 'termin', 'arsipDokumen'])->findOrFail($id);

        if (! $kontrak->isEditable()) {
            return redirect()->route('kontrak-eksternal.show', $kontrak->id)
                ->withErrors(['error' => 'Kontrak sudah diaktifkan dan tidak dapat diubah lagi.']);
        }

        return view('kontrak_eksternal.edit', [
            'kontrak' => $kontrak,
            'verifikatorOptions' => $this->buildVerifikatorOptions(),
            'vendorOptions' => $this->buildVendorOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $kontrak = KontrakEksternal::with('termin')->findOrFail($id);

        if (! $kontrak->isEditable()) {
            return redirect()->route('kontrak-eksternal.show', $kontrak->id)
                ->withErrors(['error' => 'Kontrak sudah diaktifkan dan tidak dapat diubah lagi.']);
        }

        $validated = $this->validateMasterPayload($request, isUpdate: true);

        try {
            $terminRows = $this->buildTerminRows($validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        try {
            DB::beginTransaction();

            $vendor = $this->resolveVendor($request);

            $kontrak->update([
                'vendor_id' => $vendor->id,
                'nomor_surat_pesanan' => $validated['nomor_surat_pesanan'],
                'tanggal_surat_pesanan' => $validated['tanggal_surat_pesanan'],
                'sumber' => $validated['sumber'] ?? 'INAPROC',
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'nilai_total_kontrak' => (float) str_replace(',', '', (string) $validated['nilai_total_kontrak']),
                'ada_uang_muka' => (bool) ($validated['ada_uang_muka'] ?? false),
                'nilai_uang_muka' => ($validated['ada_uang_muka'] ?? false)
                    ? (float) str_replace(',', '', (string) ($validated['nilai_uang_muka'] ?? 0))
                    : 0,
                'ppk_user_id' => $validated['ppk_user_id'],
                'ppspm_user_id' => $validated['ppspm_user_id'],
                'koordinator_keuangan_user_id' => $validated['koordinator_keuangan_user_id'],
                'bendahara_pengeluaran_user_id' => $validated['bendahara_pengeluaran_user_id'],
                'bendahara_penerimaan_user_id' => $validated['bendahara_penerimaan_user_id'],
                'kasubbag_user_id' => $validated['kasubbag_user_id'],
            ]);

            if ($request->hasFile('file_surat_pesanan')) {
                $kontrak->arsipDokumen()->where('jenis_dokumen', 'SURAT_PESANAN')->update(['is_active' => false]);
                $this->storeSuratPesanan($request, $kontrak);
            }

            // Skema termin dibangun ulang selama masih DRAFT (pola ContractController).
            // forceDelete: baris soft-deleted masih menempati unique(kontrak, termin_ke).
            $kontrak->termin()->forceDelete();
            $this->createTerminRows($kontrak, $terminRows);

            DB::commit();

            return redirect()->route('kontrak-eksternal.show', $kontrak->id)
                ->with('success', 'Master kontrak berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui kontrak: '.$e->getMessage()]);
        }
    }

    /** Aktifkan kontrak: DRAFT → AKTIF (gerbang billing termin). */
    public function activate(Request $request, $id)
    {
        $kontrak = KontrakEksternal::findOrFail($id);

        if ($kontrak->status_kontrak !== 'DRAFT') {
            return back()->withErrors(['error' => 'Hanya kontrak berstatus DRAFT yang dapat diaktifkan.']);
        }

        if (! $kontrak->file_surat_pesanan) {
            return back()->withErrors(['error' => 'PDF Surat Pesanan bertanda tangan wajib diunggah sebelum kontrak diaktifkan.']);
        }

        $kontrak->update([
            'status_kontrak' => 'AKTIF',
            'diaktifkan_at' => now(),
            'diaktifkan_by' => Auth::id(),
        ]);

        return redirect()->route('kontrak-eksternal.show', $kontrak->id)
            ->with('success', 'Kontrak diaktifkan — termin pertama siap ditagih.');
    }

    /** Halaman tagih termin (form ramping — nilai & verifikator dari master). */
    public function billTermin($kontrakId, $terminId)
    {
        [$kontrak, $termin, $error] = $this->resolveBillableTermin($kontrakId, $terminId);
        if ($error) {
            return redirect()->route('kontrak-eksternal.show', $kontrakId)->withErrors(['error' => $error]);
        }

        return view('kontrak_eksternal.bill_termin', compact('kontrak', 'termin'));
    }

    /** Buat tagihan atas satu termin READY_TO_BILL. */
    public function storeTagihanTermin(Request $request, $kontrakId, $terminId)
    {
        [$kontrak, $termin, $error] = $this->resolveBillableTermin($kontrakId, $terminId);
        if ($error) {
            return redirect()->route('kontrak-eksternal.show', $kontrakId)->withErrors(['error' => $error]);
        }

        $validated = $request->validate([
            'deskripsi' => 'nullable|string|max:500',
            'file_invoice' => 'nullable|file|mimes:pdf|max:5120',
            'file_kwitansi' => 'nullable|file|mimes:pdf|max:5120',
            'file_bast' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $bruto = (float) $termin->nilai_bruto_termin;
            $potonganUm = (float) $termin->potongan_angsuran_uang_muka;
            $netto = round($bruto - $potonganUm, 2);

            $verifikatorSnapshots = $this->buildVerifikatorSnapshots([
                'ppk' => $kontrak->ppk_user_id,
                'ppspm' => $kontrak->ppspm_user_id,
                'koordinator_keuangan' => $kontrak->koordinator_keuangan_user_id,
                'bendahara_pengeluaran' => $kontrak->bendahara_pengeluaran_user_id,
                'bendahara_penerimaan' => $kontrak->bendahara_penerimaan_user_id,
                'kasubbag' => $kontrak->kasubbag_user_id,
            ]);

            $tagihan = Tagihan::create(array_merge([
                'nomor_tagihan' => 'TAG-KE/'.date('Ym').'/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'tipe_tagihan' => 'KONTRAK_EKSTERNAL',
                // COA dibebankan Operator/PPK di halaman Proses Tagihan.
                'master_dipa_id' => null,
                'dipa_revision_item_id' => null,
                'pihak_id' => $kontrak->vendor_id,
                'deskripsi' => $validated['deskripsi']
                    ?? ('Pembayaran '.$kontrak->nama_pekerjaan.' — '.$termin->keterangan_termin
                        .' (Surat Pesanan '.$kontrak->nomor_surat_pesanan.')'),
                'total_bruto' => $bruto,
                'total_potongan' => $potonganUm,
                'total_netto' => $netto,
                'mekanisme_pembayaran' => MekanismePembayaran::LS_PIHAK_3->value,
                'status' => 'DRAFT',
                'created_by' => Auth::id(),
            ], $verifikatorSnapshots));

            $detail = DetailKontrakEksternal::create([
                'tagihan_id' => $tagihan->id,
                'kontrak_eksternal_termin_id' => $termin->id,
                'nomor_surat_pesanan' => $kontrak->nomor_surat_pesanan,
                'tanggal_surat_pesanan' => $kontrak->tanggal_surat_pesanan,
                'sumber' => $kontrak->sumber,
                'nama_pekerjaan' => $kontrak->nama_pekerjaan,
                'termin_ke' => $termin->termin_ke,
                'total_termin' => $kontrak->termin()->count(),
            ]);

            // Dokumen pendukung per termin — Surat Pesanan TIDAK di-copy
            // (accessor detail me-resolve dari arsip master).
            $this->storeSupportFiles($request, $detail);

            if ($potonganUm > 0) {
                PotonganTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'jenis_potongan' => 'ANGSURAN_UANG_MUKA',
                    'deskripsi' => 'Angsuran Uang Muka ('.$termin->keterangan_termin.')',
                    'dpp' => $bruto,
                    'nama_pajak_snapshot' => 'Angsuran Uang Muka',
                    'nominal_potongan' => $potonganUm,
                ]);
            }

            $termin->update(['status_termin' => 'DRAFT']);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => null,
                'status_baru' => 'DRAFT',
                'aksi' => 'DIBUAT',
                'catatan' => "Draft tagihan termin {$termin->termin_ke} ({$termin->keterangan_termin}) "
                    ."dari kontrak eksternal Surat Pesanan {$kontrak->nomor_surat_pesanan}.",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->with('success', "Draft tagihan termin {$termin->termin_ke} berhasil dibuat. Periksa kembali lalu ajukan.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Gagal membuat tagihan termin: '.$e->getMessage()]);
        }
    }

    /**
     * Baca PDF Surat Pesanan (INAPROC) dan kembalikan field untuk auto-isi
     * form master. Murni kenyamanan input — file TIDAK disimpan di sini.
     */
    public function parseSuratPesanan(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $extracted = app(SuratPesananPdfExtractor::class)
            ->extract((string) file_get_contents($request->file('file')->getRealPath()));

        if (! SuratPesananPdfExtractor::hasUsefulData($extracted)) {
            return response()->json([
                'ok' => false,
                'message' => 'PDF tidak dapat dibaca otomatis (kemungkinan hasil scan). Silakan isi form secara manual.',
            ]);
        }

        // Nama pekerjaan: rangkum daftar produk via Gemini (bila API key diset).
        // Best-effort dengan timeout ketat — gagal/limit → saran heuristik tetap dipakai.
        $gemini = app(GeminiNamaPekerjaanService::class);
        if ($gemini->isEnabled() && filled($extracted['ringkasan_produk'] ?? null)) {
            $judulAi = $gemini->suggest((string) $extracted['ringkasan_produk']);
            if ($judulAi !== null) {
                $extracted['nama_pekerjaan_saran'] = $judulAi;
            }
        }
        unset($extracted['ringkasan_produk']);

        // Skema termin terdeteksi (deret nilai dari SSKK) → kirim sebagai
        // persentase agar form langsung menyusun baris termin.
        if (is_array($extracted['skema_termin'] ?? null) && ($extracted['total_bruto'] ?? 0) > 0) {
            $total = (float) $extracted['total_bruto'];
            $extracted['skema_termin'] = array_map(fn ($nilai, $i) => [
                'termin_ke' => $i + 1,
                'nilai' => $nilai,
                'persentase' => round($nilai / $total * 100, 4),
            ], $extracted['skema_termin'], array_keys($extracted['skema_termin']));
        } else {
            $extracted['skema_termin'] = null;
        }

        // Vendor dengan NPWP sama sudah terdaftar? Pakai yang ada agar tidak dobel.
        $pihakId = null;
        if (filled($extracted['vendor_npwp'])) {
            $npwpDigits = preg_replace('/\D+/', '', $extracted['vendor_npwp']);
            if (strlen((string) $npwpDigits) >= 10) {
                $pihakId = MasterPihak::where('status_aktif', true)
                    ->get(['id', 'npwp'])
                    ->first(fn ($p) => preg_replace('/\D+/', '', (string) $p->npwp) === $npwpDigits)
                    ?->id;
            }
        }

        return response()->json([
            'ok' => true,
            'data' => array_merge($extracted, ['pihak_id' => $pihakId]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Ambil kontrak + termin dan pastikan layak ditagih.
     *
     * @return array{0: ?KontrakEksternal, 1: ?KontrakEksternalTermin, 2: ?string}
     */
    private function resolveBillableTermin($kontrakId, $terminId): array
    {
        $kontrak = KontrakEksternal::with('vendor')->findOrFail($kontrakId);
        $termin = KontrakEksternalTermin::findOrFail($terminId);

        if ((int) $termin->kontrak_eksternal_id !== (int) $kontrak->id) {
            return [null, null, 'Termin tidak ditemukan pada kontrak ini.'];
        }
        if ($kontrak->status_kontrak !== 'AKTIF') {
            return [null, null, 'Kontrak belum AKTIF — aktifkan kontrak sebelum menagih termin.'];
        }
        if ($termin->status_termin !== 'READY_TO_BILL') {
            return [null, null, "Termin {$termin->termin_ke} belum siap ditagih (status {$termin->status_termin})."];
        }
        if (DetailKontrakEksternal::where('kontrak_eksternal_termin_id', $termin->id)->exists()) {
            return [null, null, "Termin {$termin->termin_ke} sudah memiliki tagihan."];
        }

        return [$kontrak, $termin, null];
    }

    /** Validasi form master (create & update memakai aturan yang sama). */
    private function validateMasterPayload(Request $request, bool $isUpdate): array
    {
        $request->merge([
            'nilai_total_kontrak' => str_replace(',', '', (string) $request->input('nilai_total_kontrak')),
            'nilai_uang_muka' => str_replace(',', '', (string) $request->input('nilai_uang_muka')),
        ]);

        return $request->validate([
            'nomor_surat_pesanan' => 'required|string|max:150',
            'tanggal_surat_pesanan' => 'required|date',
            'sumber' => 'nullable|string|max:100',
            'nama_pekerjaan' => 'required|string|max:255',
            'nilai_total_kontrak' => 'required|numeric|min:1',
            'metode_pembayaran' => 'required|in:LUMPSUM,TERMIN',
            'ada_uang_muka' => 'nullable|boolean',
            'nilai_uang_muka' => 'nullable|numeric|min:0|lte:nilai_total_kontrak',
            'progress_persentase' => 'array',
            'progress_persentase.*' => 'nullable|numeric|min:0|max:100',
            'progress_keterangan' => 'array',
            'progress_keterangan.*' => 'nullable|string|max:150',
            'gunakan_retensi' => 'nullable|boolean',
            'retensi_persentase' => 'nullable|numeric|min:0|max:100',
            'retensi_keterangan' => 'nullable|string|max:150',
            // Vendor: pilih existing ATAU isi data vendor baru
            'pihak_id' => 'nullable|exists:master_pihak,id|required_without:vendor_nama',
            'vendor_nama' => 'nullable|string|max:150|required_without:pihak_id',
            'vendor_npwp' => 'nullable|string|max:50',
            'vendor_penanggung_jawab' => 'nullable|string|max:150',
            'vendor_alamat' => 'nullable|string|max:500',
            'vendor_nama_bank' => 'nullable|string|max:100|required_with:vendor_nama',
            'vendor_nomor_rekening' => 'nullable|string|max:50|required_with:vendor_nama',
            'vendor_nama_rekening' => 'nullable|string|max:150|required_with:vendor_nama',
            'file_surat_pesanan' => ($isUpdate ? 'nullable' : 'required').'|file|mimes:pdf|max:10240',
            // 6 penanda tangan/verifikator dokumen pencairan
            'ppk_user_id' => 'required|exists:users,id',
            'ppspm_user_id' => 'required|exists:users,id',
            'koordinator_keuangan_user_id' => 'required|exists:users,id',
            'bendahara_pengeluaran_user_id' => 'required|exists:users,id',
            'bendahara_penerimaan_user_id' => 'required|exists:users,id',
            'kasubbag_user_id' => 'required|exists:users,id',
        ]);
    }

    /**
     * Bentuk baris termin (LUMPSUM/TERMIN) + alokasi uang muka proporsional.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \InvalidArgumentException
     */
    private function buildTerminRows(array $validated): array
    {
        $nilaiTotal = (float) str_replace(',', '', (string) $validated['nilai_total_kontrak']);
        $nilaiUangMuka = ($validated['ada_uang_muka'] ?? false)
            ? (float) str_replace(',', '', (string) ($validated['nilai_uang_muka'] ?? 0))
            : 0.0;

        if ($validated['metode_pembayaran'] === 'TERMIN') {
            $progress = [];
            foreach (($validated['progress_persentase'] ?? []) as $idx => $pct) {
                $progress[] = [
                    'persentase' => $pct,
                    'keterangan' => $validated['progress_keterangan'][$idx] ?? null,
                ];
            }
            $retensiPct = ($validated['gunakan_retensi'] ?? false) ? (float) ($validated['retensi_persentase'] ?? 0) : null;
            $rows = TerminScheme::build($progress, $retensiPct, $validated['retensi_keterangan'] ?? null, $nilaiTotal);
        } else {
            $rows = TerminScheme::lumpsum($nilaiTotal);
        }

        return TerminScheme::allocateUangMuka($rows, $nilaiUangMuka);
    }

    /** Simpan baris termin master; termin 1 READY_TO_BILL, sisanya LOCKED (pola SPK). */
    private function createTerminRows(KontrakEksternal $kontrak, array $terminRows): void
    {
        foreach ($terminRows as $i => $row) {
            KontrakEksternalTermin::create([
                'kontrak_eksternal_id' => $kontrak->id,
                'jenis_termin' => $row['jenis_termin'],
                'termin_ke' => $i + 1,
                'keterangan_termin' => $row['keterangan_termin'],
                'persentase' => $row['persentase'],
                'nilai_bruto_termin' => $row['nilai_bruto_termin'],
                'potongan_angsuran_uang_muka' => (float) ($row['potongan_angsuran_uang_muka'] ?? 0),
                'nilai_retensi' => (float) ($row['nilai_retensi'] ?? 0),
                'status_termin' => $i === 0 ? 'READY_TO_BILL' : 'LOCKED',
            ]);
        }
    }

    /** Pakai vendor existing (pihak_id) atau daftarkan MasterPihak baru + rekening default. */
    private function resolveVendor(Request $request): MasterPihak
    {
        if ($request->filled('pihak_id')) {
            return MasterPihak::findOrFail($request->integer('pihak_id'));
        }

        $vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'kode_pihak' => 'VDR-EXT-'.strtoupper(uniqid()),
            'npwp' => $request->input('vendor_npwp'),
            'nama_pihak' => $request->input('vendor_nama'),
            'nama_penanggung_jawab' => $request->input('vendor_penanggung_jawab'),
            'alamat' => $request->input('vendor_alamat'),
            'status_aktif' => true,
        ]);

        $vendor->rekening()->create([
            'nama_bank' => $request->input('vendor_nama_bank'),
            'nomor_rekening' => $request->input('vendor_nomor_rekening'),
            'nama_rekening' => $request->input('vendor_nama_rekening'),
            'is_default' => true,
            'status_aktif' => true,
        ]);

        return $vendor;
    }

    /** Simpan PDF Surat Pesanan sebagai arsip master. */
    private function storeSuratPesanan(Request $request, KontrakEksternal $kontrak): void
    {
        if (! $request->hasFile('file_surat_pesanan')) {
            return;
        }

        $file = $request->file('file_surat_pesanan');
        $path = PdfCompressor::storeCompressed($file, 'kontrak_eksternal/surat_pesanan', 'local');

        $kontrak->arsipDokumen()->create([
            'jenis_dokumen' => 'SURAT_PESANAN',
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'disk' => 'local',
            'mime_type' => $file->getMimeType(),
            'ukuran_file' => Storage::disk('local')->size($path),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'is_active' => true,
        ]);
    }

    /** Simpan dokumen pendukung per termin (invoice/kwitansi/BAST) ke arsip detail. */
    private function storeSupportFiles(Request $request, DetailKontrakEksternal $detail): void
    {
        $map = [
            'file_invoice' => ['jenis' => 'INVOICE', 'dir' => 'tagihan/kontrak_eksternal/invoice'],
            'file_kwitansi' => ['jenis' => 'KWITANSI', 'dir' => 'tagihan/kontrak_eksternal/kwitansi'],
            'file_bast' => ['jenis' => 'BAST', 'dir' => 'tagihan/kontrak_eksternal/bast'],
        ];

        foreach ($map as $field => $cfg) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = PdfCompressor::storeCompressed($file, $cfg['dir'], 'local');

            $detail->arsipDokumen()->create([
                'jenis_dokumen' => $cfg['jenis'],
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => Storage::disk('local')->size($path),
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    private function buildVendorOptions()
    {
        return MasterPihak::where('status_aktif', true)
            ->orderBy('nama_pihak')
            ->get(['id', 'nama_pihak', 'npwp'])
            ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama_pihak, 'npwp' => $p->npwp]);
    }
}
