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

        $cocokPihak = null;
        if (! empty($hasil['fields']['pihak_nama'])) {
            $cocokPihak = MasterPihak::where('kategori', 'PENGELUARAN')
                ->whereRaw('UPPER(nama_pihak) = ?', [strtoupper($hasil['fields']['pihak_nama'])])
                ->value('id');
        }

        return response()->json([
            'ok' => true,
            'token' => $token,
            'fields' => $hasil['fields'],
            'potongan' => $hasil['potongan'],
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
            'file_arsip' => 'nullable|file|mimes:pdf|max:25600',
            'arsip_token' => 'nullable|uuid',
        ]);

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

        try {
            $tagihan = DB::transaction(function () use (
                $request, $validated, $item, $potongan, $bruto, $totalPotongan, $netto,
                $realizationService, $bkuPostingService, $numberService
            ) {
            $pihakId = $validated['pihak_id'] ?? null;
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

            // Konsumsi nomor urut pada register SPP_BLU agar penomoran otomatis
            // berikutnya melompati nomor arsip ini.
            if (! empty($validated['register_nomor_urut'])) {
                $tahunSpp = (int) date('Y', strtotime($validated['tanggal_spp']));
                try {
                    $numberService->generateByKeyWithNumber('SPP_BLU', (int) $validated['register_nomor_urut'], $tahunSpp);
                } catch (\InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['register_nomor_urut' => $e->getMessage()]);
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
                'created_by' => Auth::id(),
            ]);

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
