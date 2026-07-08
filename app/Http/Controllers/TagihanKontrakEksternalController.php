<?php

namespace App\Http\Controllers;

use App\Models\DetailKontrakEksternal;
use App\Models\LogStatusDokumen;
use App\Models\MasterPihak;
use App\Models\PotonganTagihan;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\PdfCompressor;
use App\Support\TerminScheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Tagihan Kontrak Eksternal: penagihan atas kontrak yang dibuat & ditandatangani
 * di luar sistem (mis. Surat Pesanan e-Purchasing/INAPROC yang sudah ber-TTE
 * BSrE/Privy). Tidak ada SPK internal maupun TTE vendor via magic-link — staf
 * cukup mengunggah PDF Surat Pesanan bertanda tangan. Setelah submit tagihan
 * langsung READY_FOR_SPP; verifikasi terjadi pada dokumen SPP/SPM/NPI.
 */
class TagihanKontrakEksternalController extends Controller
{
    private const VERIFIKATOR_ROLES = [
        'ppk'                   => 'PPK',
        'ppspm'                 => 'PPSPM',
        'koordinator_keuangan'  => 'Koordinator Keuangan',
        'bendahara_pengeluaran' => 'Bendahara Pengeluaran',
        'bendahara_penerimaan'  => 'Bendahara Penerimaan',
        'kasubbag'              => 'Kepala Subbagian Keuangan dan Tata Usaha',
    ];

    public function index()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'KONTRAK_EKSTERNAL')
            ->with(['detailKontrakEksternal', 'pihak', 'logs' => fn ($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        return view('tagihan_kontrak_eksternal.index', compact('tagihans'));
    }

    public function create()
    {
        return view('tagihan_kontrak_eksternal.create', [
            'verifikatorOptions' => $this->buildVerifikatorOptions(),
            'vendorOptions' => $this->buildVendorOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, isUpdate: false);

        // Susun baris termin sesuai metode pembayaran, lalu bagikan uang muka.
        try {
            $terminRows = $this->buildTerminRows($validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        try {
            DB::beginTransaction();

            $pihak = $this->resolveVendor($request);

            $nilaiTotal = (float) str_replace(',', '', (string) $validated['nilai_total_kontrak']);
            $adaUangMuka = (bool) ($validated['ada_uang_muka'] ?? false);
            $nilaiUangMuka = $adaUangMuka ? (float) str_replace(',', '', (string) ($validated['nilai_uang_muka'] ?? 0)) : 0.0;
            $metode = $validated['metode_pembayaran'];
            $totalTermin = count($terminRows);

            $verifikatorSnapshots = $this->buildVerifikatorSnapshots([
                'ppk'                   => (int) $validated['ppk_user_id'],
                'ppspm'                 => (int) $validated['ppspm_user_id'],
                'koordinator_keuangan'  => (int) $validated['koordinator_keuangan_user_id'],
                'bendahara_pengeluaran' => (int) $validated['bendahara_pengeluaran_user_id'],
                'bendahara_penerimaan'  => (int) $validated['bendahara_penerimaan_user_id'],
                'kasubbag'              => (int) $validated['kasubbag_user_id'],
            ]);

            // Simpan file unggahan SEKALI ke disk; path dipakai ulang oleh
            // setiap tagihan termin (Surat Pesanan yang sama berlaku untuk semua).
            $storedFiles = $this->storeUploadedFilesOnce($request);

            $nomorBase = 'TAG-KE/' . date('Ym') . '/' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $tagihanPertama = null;

            foreach ($terminRows as $i => $row) {
                $bruto = (float) $row['nilai_bruto_termin'];
                $potonganUm = (float) ($row['potongan_angsuran_uang_muka'] ?? 0);
                $netto = round($bruto - $potonganUm, 2);
                $nomorTagihan = $totalTermin > 1 ? $nomorBase . '-T' . ($i + 1) : $nomorBase;

                $tagihan = Tagihan::create(array_merge([
                    'nomor_tagihan' => $nomorTagihan,
                    'tipe_tagihan' => 'KONTRAK_EKSTERNAL',
                    // COA dibebankan Operator/PPK di halaman Proses Tagihan.
                    'master_dipa_id' => null,
                    'dipa_revision_item_id' => null,
                    'pihak_id' => $pihak->id,
                    'deskripsi' => $validated['deskripsi']
                        ?? ('Pembayaran ' . $validated['nama_pekerjaan'] . ' — ' . $row['keterangan_termin'] . ' (Surat Pesanan ' . $validated['nomor_surat_pesanan'] . ')'),
                    'total_bruto' => $bruto,
                    'total_potongan' => $potonganUm,
                    'total_netto' => $netto,
                    'mekanisme_pembayaran' => \App\Enums\MekanismePembayaran::LS_PIHAK_3->value,
                    'status' => 'DRAFT',
                    'created_by' => Auth::id(),
                ], $verifikatorSnapshots));

                $detail = DetailKontrakEksternal::create([
                    'tagihan_id' => $tagihan->id,
                    'nomor_surat_pesanan' => $validated['nomor_surat_pesanan'],
                    'tanggal_surat_pesanan' => $validated['tanggal_surat_pesanan'],
                    'sumber' => $validated['sumber'] ?? 'INAPROC',
                    'nama_pekerjaan' => $validated['nama_pekerjaan'],
                    'metode_pembayaran' => $metode,
                    'nilai_total_kontrak' => $nilaiTotal,
                    'ada_uang_muka' => $adaUangMuka,
                    'nilai_uang_muka' => $nilaiUangMuka,
                    'termin_ke' => $i + 1,
                    'total_termin' => $totalTermin,
                    'jenis_termin' => $row['jenis_termin'],
                    'persentase' => $row['persentase'],
                    'keterangan_termin' => $row['keterangan_termin'],
                    'potongan_angsuran_uang_muka' => $potonganUm,
                    'nilai_retensi' => (float) ($row['nilai_retensi'] ?? 0),
                ]);

                // Salin arsip file yang sudah tersimpan ke tiap termin.
                foreach ($storedFiles as $arsip) {
                    $detail->arsipDokumen()->create($arsip);
                }

                // Potongan angsuran uang muka (jika ada) — pola tagihan kontrak.
                if ($potonganUm > 0) {
                    PotonganTagihan::create([
                        'tagihan_id' => $tagihan->id,
                        'jenis_potongan' => 'ANGSURAN_UANG_MUKA',
                        'deskripsi' => 'Angsuran Uang Muka (' . $row['keterangan_termin'] . ')',
                        'dpp' => $bruto,
                        'nama_pajak_snapshot' => 'Angsuran Uang Muka',
                        'nominal_potongan' => $potonganUm,
                    ]);
                }

                LogStatusDokumen::create([
                    'dokumen_type' => Tagihan::class,
                    'dokumen_id' => $tagihan->id,
                    'user_id' => Auth::id(),
                    'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                    'status_sebelumnya' => null,
                    'status_baru' => 'DRAFT',
                    'aksi' => 'DIBUAT',
                    'catatan' => 'Draft tagihan kontrak eksternal ' . ($totalTermin > 1 ? "termin {$row['keterangan_termin']} " : '')
                        . '(Surat Pesanan ' . $detail->nomor_surat_pesanan . ').',
                    'ip_address' => $request->ip(),
                ]);

                $tagihanPertama ??= $tagihan;
            }

            DB::commit();

            if ($totalTermin > 1) {
                return redirect()->route('tagihan-kontrak-eksternal.index')
                    ->with('success', "{$totalTermin} tagihan termin berhasil dibuat dari Surat Pesanan {$validated['nomor_surat_pesanan']}. Periksa & ajukan tiap termin.");
            }

            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihanPertama->id)
                ->with('success', 'Draft tagihan kontrak eksternal berhasil dibuat. Periksa kembali lalu ajukan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan tagihan: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $tagihan = Tagihan::with([
            'detailKontrakEksternal.arsipDokumen',
            'pihak.rekening',
            'potonganTagihan.pajak',
            'logs.user',
        ])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        return view('tagihan_kontrak_eksternal.show', [
            'tagihan' => $tagihan,
            'detail' => $tagihan->detailKontrakEksternal,
            'isEditable' => $this->isEditable($tagihan),
        ]);
    }

    public function edit($id)
    {
        $tagihan = Tagihan::with(['detailKontrakEksternal.arsipDokumen', 'pihak.rekening'])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        if (! $this->isEditable($tagihan)) {
            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->withErrors(['error' => 'Tagihan sudah diajukan dan tidak dapat diubah lagi.']);
        }

        return view('tagihan_kontrak_eksternal.edit', [
            'tagihan' => $tagihan,
            'detail' => $tagihan->detailKontrakEksternal,
            'verifikatorOptions' => $this->buildVerifikatorOptions(),
            'vendorOptions' => $this->buildVendorOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $tagihan = Tagihan::with('detailKontrakEksternal')->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        if (! $this->isEditable($tagihan)) {
            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->withErrors(['error' => 'Tagihan sudah diajukan dan tidak dapat diubah lagi.']);
        }

        // Edit bersifat per-tagihan & deskriptif — skema termin (nilai bruto,
        // jenis, potongan uang muka) FIXED saat create dan tidak diregenerasi.
        $validated = $this->validateEditPayload($request);

        try {
            DB::beginTransaction();

            $pihak = $this->resolveVendor($request);

            $verifikatorSnapshots = $this->buildVerifikatorSnapshots([
                'ppk'                   => (int) $validated['ppk_user_id'],
                'ppspm'                 => (int) $validated['ppspm_user_id'],
                'koordinator_keuangan'  => (int) $validated['koordinator_keuangan_user_id'],
                'bendahara_pengeluaran' => (int) $validated['bendahara_pengeluaran_user_id'],
                'bendahara_penerimaan'  => (int) $validated['bendahara_penerimaan_user_id'],
                'kasubbag'              => (int) $validated['kasubbag_user_id'],
            ]);

            $tagihan->update(array_merge([
                'pihak_id' => $pihak->id,
                'deskripsi' => $validated['deskripsi'] ?? $tagihan->deskripsi,
            ], $verifikatorSnapshots));

            $tagihan->detailKontrakEksternal->update([
                'nomor_surat_pesanan' => $validated['nomor_surat_pesanan'],
                'tanggal_surat_pesanan' => $validated['tanggal_surat_pesanan'],
                'sumber' => $validated['sumber'] ?? 'INAPROC',
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
            ]);

            foreach ($this->storeUploadedFilesOnce($request) as $arsip) {
                $tagihan->detailKontrakEksternal->arsipDokumen()
                    ->where('jenis_dokumen', $arsip['jenis_dokumen'])->update(['is_active' => false]);
                $tagihan->detailKontrakEksternal->arsipDokumen()->create($arsip);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => $tagihan->status,
                'aksi' => 'DIPERBARUI',
                'catatan' => 'Data tagihan kontrak eksternal diperbarui sebelum diajukan.',
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->with('success', 'Tagihan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui tagihan: ' . $e->getMessage()]);
        }
    }

    /**
     * Ajukan tagihan: langsung READY_FOR_SPP (tanpa tahap verifikasi tagihan) —
     * verifikator terpilih menjadi penanda tangan dokumen SPP/SPM/NPI/SP2D.
     */
    public function submit(Request $request, $id)
    {
        $tagihan = Tagihan::with('detailKontrakEksternal.arsipDokumen')->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        if ($tagihan->status !== 'DRAFT' && ! str_starts_with((string) $tagihan->status, 'REVISI_')) {
            return back()->withErrors(['error' => 'Tagihan tidak dalam status DRAFT/REVISI.']);
        }

        $missingVerif = collect([
            'PPK' => $tagihan->ppk_user_id,
            'PPSPM' => $tagihan->ppspm_user_id,
            'Koordinator Keuangan' => $tagihan->koordinator_keuangan_user_id,
            'Bendahara Pengeluaran' => $tagihan->bendahara_pengeluaran_user_id,
            'Bendahara Penerimaan' => $tagihan->bendahara_penerimaan_user_id,
            'Kasubbag' => $tagihan->kasubbag_user_id,
        ])->filter(fn ($v) => empty($v))->keys();

        if ($missingVerif->isNotEmpty()) {
            return back()->withErrors(['error' => 'Verifikator belum lengkap: ' . $missingVerif->implode(', ')]);
        }

        if (! $tagihan->detailKontrakEksternal?->file_surat_pesanan) {
            return back()->withErrors(['error' => 'PDF Surat Pesanan bertanda tangan wajib diunggah sebelum tagihan diajukan.']);
        }

        $statusSebelumSubmit = $tagihan->status;

        try {
            DB::beginTransaction();

            $tagihan->update(['status' => 'READY_FOR_SPP']);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $statusSebelumSubmit,
                'status_baru' => 'READY_FOR_SPP',
                'aksi' => 'DIAJUKAN',
                'catatan' => 'Tagihan kontrak eksternal diajukan — kontrak sudah ber-TTE di luar sistem, langsung siap diproses.',
                'ip_address' => $request->ip(),
            ]);

            if ($tagihan->ppk_user_id && ($ppkUser = User::find($tagihan->ppk_user_id))) {
                Notification::send($ppkUser, new WorkflowNotification([
                    'title' => 'Tagihan Kontrak Eksternal Siap Diproses',
                    'message' => "Tagihan {$tagihan->nomor_tagihan} siap diproses — silakan pilih COA pada halaman Proses Tagihan.",
                    'url' => route('proses-tagihan.show', $tagihan->id),
                    'icon' => 'receipt_long',
                    'color' => 'primary',
                ]));
            }
            app(\App\Services\TagihanReadyForSppNotificationService::class)
                ->notifyIfNewlyReady($tagihan->fresh(), $statusSebelumSubmit);

            DB::commit();

            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->with('success', 'Tagihan berhasil diajukan dan langsung siap diproses. Lanjutkan pembebanan COA & pajak pada halaman Proses Tagihan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal mengajukan tagihan: ' . $e->getMessage()]);
        }
    }

    /**
     * Baca PDF Surat Pesanan (INAPROC) dan kembalikan field untuk auto-isi
     * form. Murni kenyamanan input — file TIDAK disimpan di sini; penyimpanan
     * tetap terjadi saat form disubmit dan validasi backend tetap berlaku.
     */
    public function parseSuratPesanan(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $extracted = app(\App\Services\SuratPesananPdfExtractor::class)
            ->extract((string) file_get_contents($request->file('file')->getRealPath()));

        if (! \App\Services\SuratPesananPdfExtractor::hasUsefulData($extracted)) {
            return response()->json([
                'ok' => false,
                'message' => 'PDF tidak dapat dibaca otomatis (kemungkinan hasil scan). Silakan isi form secara manual.',
            ]);
        }

        // Nama pekerjaan: rangkum daftar produk via Gemini (bila API key diset).
        // Best-effort dengan timeout ketat — gagal/limit → saran heuristik tetap dipakai.
        $gemini = app(\App\Services\GeminiNamaPekerjaanService::class);
        if ($gemini->isEnabled() && filled($extracted['ringkasan_produk'] ?? null)) {
            $judulAi = $gemini->suggest((string) $extracted['ringkasan_produk']);
            if ($judulAi !== null) {
                $extracted['nama_pekerjaan_saran'] = $judulAi;
            }
        }
        unset($extracted['ringkasan_produk']);

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

    private function isEditable(Tagihan $tagihan): bool
    {
        return $tagihan->status === 'DRAFT' || str_starts_with((string) $tagihan->status, 'REVISI_');
    }

    /** Validasi form pembuatan (nilai total kontrak + metode termin + uang muka). */
    private function validatePayload(Request $request, bool $isUpdate): array
    {
        $request->merge([
            'nilai_total_kontrak' => str_replace(',', '', (string) $request->input('nilai_total_kontrak')),
            'nilai_uang_muka' => str_replace(',', '', (string) $request->input('nilai_uang_muka')),
        ]);

        return $request->validate(array_merge($this->descriptiveRules(isUpdate: false), [
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
        ]));
    }

    /** Validasi form edit (deskriptif saja — skema termin sudah terkunci). */
    private function validateEditPayload(Request $request): array
    {
        return $request->validate($this->descriptiveRules(isUpdate: true));
    }

    /** Aturan bersama: data Surat Pesanan, vendor, file, penanda tangan. */
    private function descriptiveRules(bool $isUpdate): array
    {
        return [
            'nomor_surat_pesanan' => 'required|string|max:150',
            'tanggal_surat_pesanan' => 'required|date',
            'sumber' => 'nullable|string|max:100',
            'nama_pekerjaan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            // Vendor: pilih existing ATAU isi data vendor baru
            'pihak_id' => 'nullable|exists:master_pihak,id|required_without:vendor_nama',
            'vendor_nama' => 'nullable|string|max:150|required_without:pihak_id',
            'vendor_npwp' => 'nullable|string|max:50',
            'vendor_penanggung_jawab' => 'nullable|string|max:150',
            'vendor_alamat' => 'nullable|string|max:500',
            'vendor_nama_bank' => 'nullable|string|max:100|required_with:vendor_nama',
            'vendor_nomor_rekening' => 'nullable|string|max:50|required_with:vendor_nama',
            'vendor_nama_rekening' => 'nullable|string|max:150|required_with:vendor_nama',
            // File Surat Pesanan wajib saat create; saat update opsional (pengganti)
            'file_surat_pesanan' => ($isUpdate ? 'nullable' : 'required') . '|file|mimes:pdf|max:10240',
            'file_invoice' => 'nullable|file|mimes:pdf|max:5120',
            'file_kwitansi' => 'nullable|file|mimes:pdf|max:5120',
            'file_bast' => 'nullable|file|mimes:pdf|max:10240',
            // 6 penanda tangan/verifikator dokumen pencairan
            'ppk_user_id' => 'required|exists:users,id',
            'ppspm_user_id' => 'required|exists:users,id',
            'koordinator_keuangan_user_id' => 'required|exists:users,id',
            'bendahara_pengeluaran_user_id' => 'required|exists:users,id',
            'bendahara_penerimaan_user_id' => 'required|exists:users,id',
            'kasubbag_user_id' => 'required|exists:users,id',
        ];
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

    /** Pakai vendor existing (pihak_id) atau daftarkan MasterPihak baru + rekening default. */
    private function resolveVendor(Request $request): MasterPihak
    {
        if ($request->filled('pihak_id')) {
            return MasterPihak::findOrFail($request->integer('pihak_id'));
        }

        $vendor = MasterPihak::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'kode_pihak' => 'VDR-EXT-' . strtoupper(uniqid()),
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

    /**
     * Simpan file unggahan SEKALI ke disk dan kembalikan atribut arsip siap-pakai
     * (tanpa membuat baris arsip). File yang sama bisa ditautkan ke beberapa
     * tagihan termin — pemindahan file temp hanya terjadi satu kali per field.
     *
     * @return array<int, array<string, mixed>>
     */
    private function storeUploadedFilesOnce(Request $request): array
    {
        $map = [
            'file_surat_pesanan' => ['jenis' => 'SURAT_PESANAN', 'dir' => 'tagihan/kontrak_eksternal/surat_pesanan'],
            'file_invoice' => ['jenis' => 'INVOICE', 'dir' => 'tagihan/kontrak_eksternal/invoice'],
            'file_kwitansi' => ['jenis' => 'KWITANSI', 'dir' => 'tagihan/kontrak_eksternal/kwitansi'],
            'file_bast' => ['jenis' => 'BAST', 'dir' => 'tagihan/kontrak_eksternal/bast'],
        ];

        $arsip = [];
        foreach ($map as $field => $cfg) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = PdfCompressor::storeCompressed($file, $cfg['dir'], 'local');

            $arsip[] = [
                'jenis_dokumen' => $cfg['jenis'],
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => Storage::disk('local')->size($path),
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ];
        }

        return $arsip;
    }

    private function buildVerifikatorOptions(): array
    {
        $options = [];
        foreach (self::VERIFIKATOR_ROLES as $key => $roleName) {
            try {
                $users = User::role($roleName)->with('profilable')->orderByDisplayName()->get();
            } catch (\Exception) {
                $users = collect();
            }

            $options[$key] = $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'nip' => optional($u->profilable)->nip ?? '-',
                'jabatan' => optional($u->profilable)->jabatan ?? $roleName,
            ])->values();
        }

        return $options;
    }

    private function buildVendorOptions()
    {
        return MasterPihak::where('status_aktif', true)
            ->orderBy('nama_pihak')
            ->get(['id', 'nama_pihak', 'npwp'])
            ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama_pihak, 'npwp' => $p->npwp]);
    }

    private function buildVerifikatorSnapshots(array $userIdsByRole): array
    {
        $out = [];
        foreach ($userIdsByRole as $key => $userId) {
            if (empty($userId)) {
                continue;
            }
            $user = User::with('profilable')->find($userId);
            if (! $user) {
                continue;
            }

            $out["{$key}_user_id"] = $user->id;
            $out["{$key}_nama_snapshot"] = $user->name;
            $out["{$key}_nip_snapshot"] = optional($user->profilable)->nip ?? null;
        }

        return $out;
    }
}
