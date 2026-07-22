<?php

namespace App\Http\Controllers;

use App\Enums\MekanismePembayaran;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\MasterPihak;
use App\Models\PotonganTagihan;
use App\Models\Tagihan;
use App\Services\BkuPostingService;
use App\Services\BudgetRealizationService;
use App\Services\DocumentNumberService;
use App\Support\DipaBudgetOptionService;
use App\Support\Historis\TagihanArsipReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Input Tagihan Historis: merekam tagihan arsip yang sudah selesai diproses
 * di luar sistem (SILABI) — lengkap dengan nomor & tanggal SPP/SPM/NPI/SP2D
 * sesuai berkas — langsung berstatus SELESAI: realisasi anggaran tercatat dan
 * diposting ke BKU bertanggal SP2D historis, tanpa alur verifikasi ulang.
 */
class TagihanHistorisController extends Controller
{
    public function create()
    {
        return view('proses_tagihan.historis_create', [
            'budgetGroups' => DipaBudgetOptionService::groupedOptions(),
            'pihakOptions' => MasterPihak::where('kategori', 'PENGELUARAN')
                ->orderBy('nama_pihak')
                ->get(['id', 'nama_pihak', 'npwp']),
        ]);
    }

    /**
     * Endpoint AJAX: unggah bundel arsip → OCR (Tesseract) + parse nama file
     * → JSON untuk mengisi form otomatis. File disimpan bertoken agar saat
     * submit tidak perlu diunggah ulang (arsip_token).
     */
    public function bacaArsip(Request $request, TagihanArsipReader $reader)
    {
        $request->validate([
            'file_arsip' => 'required|file|mimes:pdf|max:25600',
        ]);

        $token = \Illuminate\Support\Str::uuid()->toString();
        $namaAsli = $request->file('file_arsip')->getClientOriginalName();
        $path = $request->file('file_arsip')->storeAs('historis-arsip', $token . '.pdf', 'local');

        try {
            $hasil = $reader->read(\Illuminate\Support\Facades\Storage::disk('local')->path($path), $namaAsli);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);

            return response()->json(['ok' => false, 'pesan' => 'Berkas tidak dapat dibaca: ' . $e->getMessage()], 422);
        }

        // Pratinjau halaman 1 diperkecil (lebar maks 900px) sebagai base64.
        $previewBase64 = null;
        if ($hasil['preview_jpeg'] && function_exists('imagecreatefromjpeg')) {
            $src = @imagecreatefromjpeg($hasil['preview_jpeg']);
            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);
                $tw = min(900, $w);
                $th = (int) round($h * $tw / $w);
                $dst = imagecreatetruecolor($tw, $th);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
                ob_start();
                imagejpeg($dst, null, 72);
                $previewBase64 = 'data:image/jpeg;base64,' . base64_encode((string) ob_get_clean());
                imagedestroy($dst);
                imagedestroy($src);
            }
        }

        // Bersihkan JPEG temp hasil ekstraksi.
        foreach (glob(storage_path('app/historis-arsip/tmp/*.jpg')) ?: [] as $tmp) {
            @unlink($tmp);
        }

        // Cocokkan pihak dengan kunci longgar (huruf/angka saja) agar artefak
        // OCR pada nama tersimpan ("©: CV. ..." dsb.) tidak menggagalkan match.
        $cocokPihak = null;
        if (! empty($hasil['fields']['pihak_nama'])) {
            $kunci = TagihanArsipReader::kunciNama($hasil['fields']['pihak_nama']);
            $cocokPihak = MasterPihak::where('kategori', 'PENGELUARAN')
                ->get(['id', 'nama_pihak'])
                ->first(fn ($p) => TagihanArsipReader::kunciNama($p->nama_pihak) === $kunci)
                ?->id;
        }

        return response()->json([
            'ok' => true,
            'token' => $token,
            'fields' => $hasil['fields'],
            'potongan' => $hasil['potongan'],
            'peserta' => $hasil['peserta'],
            'komponen' => $hasil['komponen'],
            'pihak_id_cocok' => $cocokPihak,
            'preview' => $previewBase64,
            'warnings' => $hasil['warnings'],
            'ocr_aktif' => $hasil['ocr_aktif'],
        ]);
    }

    public function store(
        Request $request,
        BudgetRealizationService $realizationService,
        BkuPostingService $bkuPostingService,
        DocumentNumberService $numberService
    ) {
        $validated = $request->validate([
            'tipe_tagihan' => 'required|in:KONTRAK_EKSTERNAL,HONORARIUM,PERJALDIN',
            'nomor_tagihan' => 'required|string|max:100|unique:tagihan,nomor_tagihan',
            'deskripsi' => 'required|string|max:500',
            'pihak_id' => 'nullable|exists:master_pihak,id',
            'pihak_nama_baru' => 'nullable|string|max:255|required_without_all:pihak_id',
            'pihak_npwp' => 'nullable|string|max:30',
            'pihak_alamat' => 'nullable|string|max:500',
            'pihak_direktur' => 'nullable|string|max:150',
            'pihak_jabatan' => 'nullable|string|max:100',
            'pihak_email' => 'nullable|email|max:150',
            'pihak_telepon' => 'nullable|string|max:30',
            'pihak_bank' => 'nullable|string|max:100',
            'pihak_norek' => 'nullable|string|max:30',
            'pihak_nama_rekening' => 'nullable|string|max:150',
            'dipa_revision_item_id' => 'required|integer',
            'total_bruto' => 'required|numeric|min:1',
            'potongan_nama' => 'nullable|array',
            'potongan_nama.*' => 'nullable|string|max:100',
            'potongan_nominal' => 'nullable|array',
            'potongan_nominal.*' => 'nullable|numeric|min:0',
            'potongan_ntpn' => 'nullable|array',
            'potongan_ntpn.*' => 'nullable|string|max:50',
            'nomor_spp' => 'required|string|max:100|unique:dokumen_spp,nomor_spp',
            'tanggal_spp' => 'required|date',
            'nomor_spm' => 'required|string|max:100',
            'tanggal_spm' => 'required|date|after_or_equal:tanggal_spp',
            'nomor_npi' => 'required|string|max:100',
            'tanggal_npi' => 'required|date|after_or_equal:tanggal_spp',
            'nomor_sp2d' => 'required|string|max:100',
            'tanggal_sp2d' => 'required|date|after_or_equal:tanggal_spp',
            'register_nomor_urut' => 'nullable|integer|min:1|max:9999',
            'komponen_nama' => 'nullable|array',
            'komponen_nama.*' => 'nullable|string|max:100',
            'komponen_nomor_spp' => 'nullable|array',
            'komponen_nomor_spp.*' => 'nullable|string|max:100',
            'komponen_urut' => 'nullable|array',
            'komponen_urut.*' => 'nullable|integer|min:1|max:9999',
            'komponen_dipa_item_id' => 'nullable|array',
            'komponen_dipa_item_id.*' => 'nullable|integer',
            'komponen_nominal' => 'nullable|array',
            'komponen_nominal.*' => 'nullable|numeric|min:0',
            'file_arsip' => 'nullable|file|mimes:pdf|max:25600',
            'arsip_token' => 'nullable|uuid',
            'peserta_nama' => 'nullable|array',
            'peserta_nama.*' => 'nullable|string|max:150',
            'peserta_nrp' => 'nullable|array',
            'peserta_nrp.*' => 'nullable|string|max:30',
            'peserta_pangkat' => 'nullable|array',
            'peserta_pangkat.*' => 'nullable|string|max:50',
            'peserta_jabatan' => 'nullable|array',
            'peserta_jabatan.*' => 'nullable|string|max:150',
            'peserta_honor' => 'nullable|array',
            'peserta_honor.*' => 'nullable|numeric|min:0',
            'peserta_pph' => 'nullable|array',
            'peserta_pph.*' => 'nullable|numeric|min:0',
            'peserta_rekening' => 'nullable|array',
            'peserta_rekening.*' => 'nullable|string|max:30',
            'peserta_bank' => 'nullable|array',
            'peserta_bank.*' => 'nullable|string|max:50',
            'peserta_nama_rekening' => 'nullable|array',
            'peserta_nama_rekening.*' => 'nullable|string|max:150',
            'peserta_hp' => 'nullable|array',
            'peserta_hp.*' => 'nullable|string|max:20',
            'peserta_no_spt' => 'nullable|array',
            'peserta_no_spt.*' => 'nullable|string|max:100',
            'peserta_no_sppd' => 'nullable|array',
            'peserta_no_sppd.*' => 'nullable|string|max:100',
            'peserta_tujuan' => 'nullable|array',
            'peserta_tujuan.*' => 'nullable|string|max:150',
            'peserta_tgl_berangkat' => 'nullable|array',
            'peserta_tgl_berangkat.*' => 'nullable|date',
            'peserta_lama_hari' => 'nullable|array',
            'peserta_lama_hari.*' => 'nullable|integer|min:1|max:365',
        ]);

        // Rangkai peserta/penerima: baris tanpa nama diabaikan.
        $peserta = [];
        foreach ($validated['peserta_nama'] ?? [] as $i => $namaPeserta) {
            if (trim((string) $namaPeserta) === '') {
                continue;
            }
            $peserta[] = [
                'nama' => trim($namaPeserta),
                'nrp' => $validated['peserta_nrp'][$i] ?? null,
                'pangkat' => $validated['peserta_pangkat'][$i] ?? null,
                'jabatan' => $validated['peserta_jabatan'][$i] ?? null,
                'honor' => (float) ($validated['peserta_honor'][$i] ?? 0),
                'pph' => (float) ($validated['peserta_pph'][$i] ?? 0),
                'rekening' => $validated['peserta_rekening'][$i] ?? null,
                'bank' => $validated['peserta_bank'][$i] ?? null,
                'nama_rekening' => $validated['peserta_nama_rekening'][$i] ?? null,
                'hp' => $validated['peserta_hp'][$i] ?? null,
                'no_spt' => trim((string) ($validated['peserta_no_spt'][$i] ?? '')) ?: null,
                'no_sppd' => trim((string) ($validated['peserta_no_sppd'][$i] ?? '')) ?: null,
                'tujuan' => trim((string) ($validated['peserta_tujuan'][$i] ?? '')) ?: null,
                'tgl_berangkat' => $validated['peserta_tgl_berangkat'][$i] ?? null,
                'lama_hari' => $validated['peserta_lama_hari'][$i] ?? null,
            ];
        }

        $item = DipaBudgetOptionService::resolveActiveItem($validated['dipa_revision_item_id']);

        // Rangkai potongan pajak: baris tanpa nama/nominal diabaikan.
        $potongan = [];
        foreach ($validated['potongan_nama'] ?? [] as $i => $nama) {
            $nominal = (float) ($validated['potongan_nominal'][$i] ?? 0);
            if (trim((string) $nama) === '' || $nominal <= 0) {
                continue;
            }
            $potongan[] = [
                'nama' => trim($nama),
                'nominal' => $nominal,
                'ntpn' => trim((string) ($validated['potongan_ntpn'][$i] ?? '')) ?: null,
            ];
        }

        $bruto = (float) $validated['total_bruto'];
        $totalPotongan = array_sum(array_column($potongan, 'nominal'));
        $netto = round($bruto - $totalPotongan, 2);

        if ($netto <= 0) {
            throw ValidationException::withMessages([
                'total_bruto' => 'Total potongan melebihi atau menyamai nilai bruto — periksa kembali angka dari berkas.',
            ]);
        }

        // Rangkai komponen biaya perjaldin (bundel gabungan berisi beberapa
        // SPP → satu tagihan, satu komponen per SPP): baris kosong diabaikan.
        $komponen = [];
        if ($validated['tipe_tagihan'] === 'PERJALDIN') {
            foreach ($validated['komponen_nama'] ?? [] as $i => $namaKomponen) {
                $nominal = (float) ($validated['komponen_nominal'][$i] ?? 0);
                if (trim((string) $namaKomponen) === '' || $nominal <= 0) {
                    continue;
                }

                try {
                    $itemKomponen = DipaBudgetOptionService::resolveActiveItem(
                        (int) ($validated['komponen_dipa_item_id'][$i] ?? 0)
                    );
                } catch (\Throwable $e) {
                    throw ValidationException::withMessages([
                        'komponen_dipa_item_id' => 'Pilih COA/item anggaran untuk komponen "' . trim($namaKomponen) . '".',
                    ]);
                }

                $komponen[] = [
                    'nama' => trim($namaKomponen),
                    'nominal' => $nominal,
                    'item' => $itemKomponen,
                    'urut' => ! empty($validated['komponen_urut'][$i]) ? (int) $validated['komponen_urut'][$i] : null,
                    'nomor_spp' => trim((string) ($validated['komponen_nomor_spp'][$i] ?? '')) ?: null,
                ];
            }

            if ($komponen !== [] && abs(array_sum(array_column($komponen, 'nominal')) - $bruto) > 1) {
                throw ValidationException::withMessages([
                    'total_bruto' => 'Jumlah nominal seluruh komponen tidak sama dengan bruto tagihan — periksa nominal per komponen.',
                ]);
            }
        }

        try {
            $tagihan = DB::transaction(function () use (
                $request, $validated, $item, $potongan, $peserta, $komponen, $bruto, $totalPotongan, $netto,
                $realizationService, $bkuPostingService, $numberService
            ) {
            $pihakId = $validated['pihak_id'] ?? null;

            // Anti-duplikat: bila nama "pihak baru" ternyata sudah ada di master
            // (dibandingkan longgar), pakai yang lama alih-alih membuat kembar.
            if (! $pihakId && ! empty($validated['pihak_nama_baru'])) {
                $kunci = TagihanArsipReader::kunciNama($validated['pihak_nama_baru']);
                $pihakId = MasterPihak::where('kategori', 'PENGELUARAN')
                    ->get(['id', 'nama_pihak'])
                    ->first(fn ($p) => TagihanArsipReader::kunciNama($p->nama_pihak) === $kunci)
                    ?->id;
            }

            if (! $pihakId && ! empty($validated['pihak_nama_baru'])) {
                $pihakBaru = MasterPihak::create([
                    'kategori' => 'PENGELUARAN',
                    'jenis_entitas' => 'BADAN_USAHA',
                    'kode_pihak' => 'VDR-HIS-' . strtoupper(uniqid()),
                    'npwp' => $validated['pihak_npwp'] ?? null,
                    'nama_pihak' => $validated['pihak_nama_baru'],
                    'nama_penanggung_jawab' => $validated['pihak_direktur'] ?? null,
                    'jabatan_penandatangan' => $validated['pihak_jabatan'] ?? null,
                    'alamat' => $validated['pihak_alamat'] ?? null,
                    'email' => $validated['pihak_email'] ?? null,
                    'no_telepon' => $validated['pihak_telepon'] ?? null,
                    'status_aktif' => true,
                ]);

                // Pola sama dengan vendor kontrak eksternal — tanpa jenis/is_default
                // agar tidak pernah terpilih sebagai rekening sumber BKU.
                if (! empty($validated['pihak_norek'])) {
                    $pihakBaru->rekening()->create([
                        'nama_bank' => $validated['pihak_bank'] ?? 'Bank Tabungan Negara',
                        'nomor_rekening' => $validated['pihak_norek'],
                        'nama_rekening' => $validated['pihak_nama_rekening'] ?? $validated['pihak_nama_baru'],
                    ]);
                }

                $pihakId = $pihakBaru->id;
            }

            // Konsumsi nomor urut pada register SPP_BLU — termasuk urut tiap
            // SPP pada bundel gabungan — agar penomoran otomatis melompatinya.
            $urutRegister = array_values(array_unique(array_filter(array_merge(
                [(int) ($validated['register_nomor_urut'] ?? 0)],
                array_map(fn ($k) => (int) ($k['urut'] ?? 0), $komponen)
            ))));
            if ($urutRegister !== []) {
                $tahunSpp = (int) date('Y', strtotime($validated['tanggal_spp']));
                foreach ($urutRegister as $urut) {
                    try {
                        $numberService->generateByKeyWithNumber('SPP_BLU', $urut, $tahunSpp);
                    } catch (\InvalidArgumentException $e) {
                        throw ValidationException::withMessages(['register_nomor_urut' => $e->getMessage()]);
                    }
                }
            }

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $validated['nomor_tagihan'],
                'tipe_tagihan' => $validated['tipe_tagihan'],
                'master_dipa_id' => $item->dipaRevision?->master_dipa_id,
                'dipa_revision_item_id' => $item->id,
                'pihak_id' => $pihakId,
                'deskripsi' => $validated['deskripsi'],
                'total_bruto' => $bruto,
                'total_potongan' => $totalPotongan,
                'total_netto' => $netto,
                'mekanisme_pembayaran' => MekanismePembayaran::LS_PIHAK_3->value,
                'status' => 'SELESAI',
                'is_historis' => true,
                // Arsip sudah final: tandai persetujuan KPA agar progres
                // pencairan tampil lengkap (bukan tahap tertunda).
                'kpa_approval_status' => 'APPROVED',
                'kpa_approved_at' => $validated['tanggal_spp'],
                'kpa_approved_by' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            // Komponen biaya perjaldin (satu per SPP arsip pada bundel
            // gabungan) — realisasi anggaran dicatat per komponen sesuai COA
            // masing-masing, seperti alur perjaldin terpadu.
            foreach ($komponen as $i => $k) {
                \App\Models\TagihanPerjaldinKomponen::create([
                    'tagihan_id' => $tagihan->id,
                    'kode_komponen' => \Illuminate\Support\Str::limit(
                        strtoupper(\Illuminate\Support\Str::slug($k['nama'], '_')), 40, ''
                    ) . '_' . ($i + 1),
                    'nama_komponen' => \Illuminate\Support\Str::limit(
                        $k['nama'] . ($k['nomor_spp'] ? ' (' . $k['nomor_spp'] . ')' : ''), 100, ''
                    ),
                    'dipa_revision_item_id' => $k['item']->id,
                    'total_nominal' => $k['nominal'],
                    'jumlah_peserta' => 0,
                    'status_proses' => \App\Models\TagihanPerjaldinKomponen::STATUS_SELESAI,
                ]);
            }

            foreach ($potongan as $p) {
                PotonganTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'jenis_potongan' => 'PAJAK',
                    'deskripsi' => 'Potongan historis akun ' . $p['nama'],
                    'dpp' => $bruto,
                    'nama_pajak_snapshot' => $p['nama'],
                    'nominal_potongan' => $p['nominal'],
                    'ntpn' => $p['ntpn'] ?? 'ARSIP',
                ]);
            }

            // Peserta/penerima dari nominatif arsip → tabel detail sesuai tipe,
            // sehingga kartu penerima pada halaman detail ikut terisi.
            foreach ($peserta as $p) {
                if ($validated['tipe_tagihan'] === 'HONORARIUM') {
                    \App\Models\DetailHonorarium::create([
                        'tagihan_id' => $tagihan->id,
                        'nama_personel' => $p['nama'],
                        'nrp_nip' => $p['nrp'],
                        'pangkat_korp' => $p['pangkat'],
                        'jabatan' => $p['jabatan'],
                        'nilai_honor' => $p['honor'],
                        'pph' => $p['pph'],
                        'rekening' => $p['rekening'],
                        'jenis_bank' => $p['bank'],
                        'nama_rekening' => $p['nama_rekening'],
                        'no_hp' => $p['hp'],
                    ]);
                } elseif ($validated['tipe_tagihan'] === 'PERJALDIN') {
                    // Kolom administratif SPT/SPPD wajib isi (NOT NULL) — bila
                    // tidak terbaca dari arsip, ditandai 'ARSIP'; rinciannya
                    // ada di bundel scan terlampir.
                    \App\Models\DetailPerjaldin::create([
                        'tagihan_id' => $tagihan->id,
                        'nama_pegawai' => $p['nama'],
                        'nip' => $p['nrp'],
                        'rekening' => $p['rekening'],
                        'uang_harian' => $p['honor'],
                        'no_spt' => $p['no_spt'] ?? 'ARSIP',
                        'no_sppd' => $p['no_sppd'] ?? 'ARSIP',
                        'tujuan' => $p['tujuan'],
                        'tipe_perjalanan' => 'luar_kota',
                        'tgl_berangkat' => $p['tgl_berangkat'] ?? $validated['tanggal_spp'],
                        'lama_hari' => $p['lama_hari'] ?? 1,
                    ]);
                }
            }

            $tahun = (int) date('Y', strtotime($validated['tanggal_spp']));

            $spp = DokumenSpp::create([
                'tagihan_id' => $tagihan->id,
                'tagihan_perjaldin_komponen_id' => null,
                'dipa_revision_item_id' => $item->id,
                'kategori_pembayaran' => 'SP2D BLU - TRF',
                'jenis_tagihan' => 'NON REMUNERASI',
                'nominal_spp' => $bruto,
                'nomor_spp' => $validated['nomor_spp'],
                'tanggal_spp' => $validated['tanggal_spp'],
                'status' => 'DISETUJUI_FINAL',
                'dibuat_oleh_id' => Auth::id(),
                // Kolom verifikator wajib terisi (NOT NULL) — untuk arsip
                // historis dicatat atas nama perekam.
                'ppk_verifikator_id' => Auth::id(),
            ]);

            $spm = DokumenSpm::create([
                'spp_id' => $spp->id,
                'nomor_spm' => $validated['nomor_spm'],
                'tanggal_spm' => $validated['tanggal_spm'],
                'dipa_revision_item_id' => $item->id,
                'tahun_anggaran' => (string) $tahun,
                'jenis_tagihan' => 'NON REMUNERASI',
                'jatuh_tempo' => 'Segera',
                'cara_bayar' => 'SP2D BLU - TRF',
                'nominal_spm' => $bruto,
                'dibuat_oleh_id' => Auth::id(),
                'ppspm_id' => Auth::id(),
                'status' => DokumenSpm::STATUS_DISETUJUI_FINAL,
            ]);

            $npi = DokumenNpi::create([
                'spm_id' => $spm->id,
                'nomor_npi' => $validated['nomor_npi'],
                'tanggal_npi' => $validated['tanggal_npi'],
                'tahun_anggaran' => (string) $tahun,
                'bendahara_penerimaan_id' => Auth::id(),
                'status' => DokumenNpi::STATUS_DISETUJUI_FINAL,
            ]);

            $sp2d = DokumenSp2d::create([
                'npi_id' => $npi->id,
                'nomor_sp2d' => $validated['nomor_sp2d'],
                'tanggal_sp2d' => $validated['tanggal_sp2d'],
                'bendahara_pengeluaran_id' => Auth::id(),
                'status' => DokumenSp2d::STATUS_EXECUTED,
            ]);

            $arsipPath = null;
            $arsipNama = null;
            $arsipUkuran = null;

            if ($request->hasFile('file_arsip')) {
                $file = $request->file('file_arsip');
                $arsipPath = $file->store('sp2d/bukti-transfer', 'local');
                $arsipNama = $file->getClientOriginalName();
                $arsipUkuran = $file->getSize();
            } elseif (! empty($validated['arsip_token'])) {
                // File sudah diunggah lewat endpoint baca-arsip (OCR) — pakai ulang.
                $tokenPath = 'historis-arsip/' . $validated['arsip_token'] . '.pdf';
                if (Storage::disk('local')->exists($tokenPath)) {
                    $arsipPath = 'sp2d/bukti-transfer/' . $validated['arsip_token'] . '.pdf';
                    Storage::disk('local')->move($tokenPath, $arsipPath);
                    $arsipNama = 'arsip-' . $validated['nomor_tagihan'] . '.pdf';
                    $arsipUkuran = Storage::disk('local')->size($arsipPath);
                }
            }

            if ($arsipPath !== null) {
                $sp2d->arsipDokumen()->create([
                    'jenis_dokumen' => 'BUKTI_TRANSFER_SP2D',
                    'nama_file_asli' => $arsipNama,
                    'path_file' => $arsipPath,
                    'disk' => 'local',
                    'mime_type' => 'application/pdf',
                    'ukuran_file' => $arsipUkuran,
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'keterangan' => 'Bundel arsip tagihan historis (scan SILABI).',
                    'is_active' => true,
                ]);
            }

            // Serapan anggaran + posting BKU bertanggal SP2D historis.
            $realizationService->recordFromSp2d($sp2d);

            $bkuPostingService->postTagihanPengeluaran(
                $tagihan,
                $sp2d,
                'Impor tagihan historis dari arsip.',
                $potongan !== [] ? $bruto : null
            );

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'SYSTEM',
                'status_sebelumnya' => null,
                'status_baru' => 'SELESAI',
                'aksi' => 'IMPORT_HISTORIS',
                'catatan' => sprintf(
                    'Tagihan historis diimpor dari arsip: SPP %s (%s) s.d. SP2D %s (%s), netto Rp %s.',
                    $validated['nomor_spp'],
                    $validated['tanggal_spp'],
                    $validated['nomor_sp2d'],
                    $validated['tanggal_sp2d'],
                    number_format($netto, 0, ',', '.')
                ),
                'ip_address' => $request->ip(),
            ]);

                return $tagihan;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Prasyarat pembukuan/posting belum siap (mis. rekening sumber BKU
            // belum ada) — transaksi sudah di-rollback; tampilkan sebagai error
            // form yang ramah, bukan halaman 500.
            report($e);

            return back()
                ->withInput()
                ->with('error', $e->getMessage() . ' (Tidak ada data yang tersimpan — perbaiki lalu simpan ulang.)');
        }

        return redirect()
            ->route('proses-tagihan.show', $tagihan)
            ->with('success', 'Tagihan historis ' . $tagihan->nomor_tagihan . ' berhasil direkam sampai BKU (tanggal ' . $validated['tanggal_sp2d'] . ').');
    }
}
