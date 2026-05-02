<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tagihan;
use App\Models\DetailPerjaldin;
use App\Models\MasterPegawai;
use App\Models\MasterUangHarianPerjaldin;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Support\DipaBudgetOptionService;
use App\Services\PerjaldinKomponenService;

class PerjaldinController extends Controller
{
    /**
     * Helper: Normalisasi nominal peserta.
     */
    private function normalizePesertaNominals(array $peserta): array
    {
        foreach ($peserta as &$p) {
            foreach (['biaya_tiket', 'biaya_transport', 'biaya_penginapan', 'uang_harian', 'uang_representasi'] as $field) {
                if (isset($p[$field])) {
                    $p[$field] = str_replace(',', '', $p[$field]);
                }
            }
        }
        return $peserta;
    }

    /**
     * Helper: Hitung total masing-masing row peserta.
     */
    private function calculatePesertaRowTotal(array $p): float
    {
        return ($p['biaya_tiket'] ?? 0) + ($p['biaya_transport'] ?? 0)
             + ($p['biaya_penginapan'] ?? 0) + ($p['uang_harian'] ?? 0)
             + ($p['uang_representasi'] ?? 0);
    }

    /**
     * Helper: Hitung total bruto perjaldin.
     */
    private function calculatePerjaldinGrandTotal(array $peserta): float
    {
        $total = 0;
        foreach ($peserta as $p) {
            $total += $this->calculatePesertaRowTotal($p);
        }
        return $total;
    }

    /**
     * Helper: Dapatkan role name.
     */
    private function resolveCurrentRoleName(): string
    {
        $user = auth()->user();
        if ($user && $user->roles && $user->roles->count() > 0) {
            return $user->roles->first()->name;
        }
        return 'SYSTEM';
    }

    /**
     * Helper: Tulis log tagihan.
     */
    private function writeTagihanLog(Tagihan $tagihan, ?string $oldStatus, string $newStatus, string $aksi, ?string $catatan = null): void
    {
        $tagihan->logs()->create([
            'user_id' => auth()->id(),
            'role_saat_itu' => $this->resolveCurrentRoleName(),
            'status_sebelumnya' => $oldStatus,
            'status_baru' => $newStatus,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Daftar semua Tagihan Perjaldin (menggantikan Perjaldin lawas).
     */
    public function index()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        return view('perjaldins.index', compact('tagihans'));
    }

    /**
     * Form tambah Perjaldin (menggunakan input manual Pegawai).
     */
    public function create()
    {
        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $masterProvinsi = MasterUangHarianPerjaldin::orderBy('provinsi')->get();
        $ppkUsers = User::role('PPK')->with('profilable')->orderByDisplayName()->get();
        $ppspmUsers = User::role('PPSPM')->with('profilable')->orderByDisplayName()->get();
        $bendaharaPenerimaanUsers = User::role('Bendahara Penerimaan')->with('profilable')->orderByDisplayName()->get();
        $bendaharaUsers = User::role('Bendahara Pengeluaran')->with('profilable')->orderByDisplayName()->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->with('profilable')->orderByDisplayName()->first();
        $koorKeuanganUsers = User::role('Koordinator Keuangan')->with('profilable')->orderByDisplayName()->get();
        $masterPegawai = MasterPegawai::where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('perjaldins.create', compact('budgetGroups', 'masterProvinsi', 'ppkUsers', 'ppspmUsers', 'bendaharaPenerimaanUsers', 'bendaharaUsers', 'kasubbagUser', 'koorKeuanganUsers', 'masterPegawai'));
    }

    /**
     * Simpan data Perjaldin sebagai Tagihan + DetailPerjaldin.
     */
    public function store(Request $request)
    {
        // Strip commas from currency inputs
        $input = $request->all();
        if (isset($input['peserta']) && is_array($input['peserta'])) {
            $input['peserta'] = $this->normalizePesertaNominals($input['peserta']);
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'nomor_perjaldin' => 'required|string|max:100',
            'periode_bulan' => 'required|integer|min:1|max:12',
            'periode_tahun' => 'required|integer|min:2000|max:2100',
            'kota_ttd' => 'required|string|max:100',
            'tanggal_ttd' => 'required|date',
            'ppk_user_id' => 'nullable|exists:users,id',
            'ppk_nama_snapshot' => 'required|string|max:150',
            'ppk_nip_snapshot' => 'required|string|max:100',
            'ppspm_user_id' => 'required|exists:users,id',
            'ppspm_nama_snapshot' => 'required|string|max:150',
            'ppspm_nip_snapshot' => 'nullable|string|max:100',
            'bendahara_penerimaan_user_id' => 'required|exists:users,id',
            'bendahara_penerimaan_nama_snapshot' => 'required|string|max:150',
            'bendahara_penerimaan_nip_snapshot' => 'nullable|string|max:100',
            'bendahara_pengeluaran_user_id' => 'required|exists:users,id',
            'bendahara_pengeluaran_nama_snapshot' => 'required|string|max:150',
            'bendahara_pengeluaran_nip_snapshot' => 'required|string|max:100',
            'kasubbag_user_id' => 'required|exists:users,id',
            'kasubbag_nama_snapshot' => 'required|string|max:150',
            'kasubbag_nip_snapshot' => 'nullable|string|max:100',
            'koordinator_keuangan_user_id' => 'nullable|exists:users,id',
            'koordinator_keuangan_nama_snapshot' => 'nullable|string|max:150',
            'koordinator_keuangan_nip_snapshot' => 'nullable|string|max:100',

            'peserta' => 'required|array|min:1',
            'peserta.*.nama_pegawai' => 'required|string|max:150',
            'peserta.*.nip' => 'nullable|string|max:100',
            'peserta.*.no_spt' => 'required|string|max:100',
            'peserta.*.no_sppd' => 'required|string|max:100',
            'peserta.*.provinsi_id' => 'required|exists:master_uang_harian_perjaldins,id',
            'peserta.*.tipe_perjalanan' => 'required|string|in:luar_kota,dalam_kota_lebih_8_jam,diklat',
            'peserta.*.spt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'peserta.*.tujuan' => 'nullable|string|max:255',
            'peserta.*.rekening' => 'nullable|string|max:100',
            'peserta.*.tgl_berangkat' => 'required|date',
            'peserta.*.lama_hari' => 'required|integer|min:1',
            'peserta.*.biaya_tiket' => 'nullable|numeric|min:0',
            'peserta.*.biaya_transport' => 'nullable|numeric|min:0',
            'peserta.*.biaya_penginapan' => 'nullable|numeric|min:0',
            'peserta.*.uang_harian' => 'nullable|numeric|min:0',
            'peserta.*.uang_representasi' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Hitung total bruto dari semua peserta
            $totalBruto = $this->calculatePerjaldinGrandTotal($request->peserta);

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $request->nomor_perjaldin,
                'tipe_tagihan' => 'PERJALDIN',
                'deskripsi' => $request->deskripsi,
                'periode_bulan' => $request->periode_bulan,
                'periode_tahun' => $request->periode_tahun,
                'kota_ttd' => $request->kota_ttd,
                'tanggal_ttd' => $request->tanggal_ttd,
                'ppk_user_id' => $request->ppk_user_id,
                'ppk_nama_snapshot' => $request->ppk_nama_snapshot,
                'ppk_nip_snapshot' => $request->ppk_nip_snapshot,
                'ppspm_user_id' => $request->ppspm_user_id,
                'ppspm_nama_snapshot' => $request->ppspm_nama_snapshot,
                'ppspm_nip_snapshot' => $request->ppspm_nip_snapshot,
                'bendahara_penerimaan_user_id' => $request->bendahara_penerimaan_user_id,
                'bendahara_penerimaan_nama_snapshot' => $request->bendahara_penerimaan_nama_snapshot,
                'bendahara_penerimaan_nip_snapshot' => $request->bendahara_penerimaan_nip_snapshot,
                'bendahara_pengeluaran_user_id' => $request->bendahara_pengeluaran_user_id,
                'bendahara_pengeluaran_nama_snapshot' => $request->bendahara_pengeluaran_nama_snapshot,
                'bendahara_pengeluaran_nip_snapshot' => $request->bendahara_pengeluaran_nip_snapshot,
                'kasubbag_user_id' => $request->kasubbag_user_id,
                'kasubbag_nama_snapshot' => $request->kasubbag_nama_snapshot,
                'kasubbag_nip_snapshot' => $request->kasubbag_nip_snapshot,
                'koordinator_keuangan_user_id' => $request->koordinator_keuangan_user_id,
                'koordinator_keuangan_nama_snapshot' => $request->koordinator_keuangan_nama_snapshot,
                'koordinator_keuangan_nip_snapshot' => $request->koordinator_keuangan_nip_snapshot,
                'total_bruto' => $totalBruto,
                'total_potongan' => 0,
                'total_netto' => $totalBruto,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);

            // Insert detail per peserta
            foreach ($request->peserta as $index => $pesertaData) {
                // Upload file handler
                $sptFilePath = null;
                $sptFileName = null;
                
                if ($request->hasFile("peserta.{$index}.spt_file")) {
                    $file = $request->file("peserta.{$index}.spt_file");
                    $sptFileName = $file->getClientOriginalName();
                    $sptFilePath = $file->store('perjaldin/spt', 'public');
                }

                DetailPerjaldin::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_pegawai' => $pesertaData['nama_pegawai'],
                    'nip' => $pesertaData['nip'] ?? null,
                    'no_spt' => $pesertaData['no_spt'],
                    'no_sppd' => $pesertaData['no_sppd'] ?? null,
                    'provinsi_id' => $pesertaData['provinsi_id'] ?? null,
                    'tipe_perjalanan' => $pesertaData['tipe_perjalanan'] ?? null,
                    'tujuan' => $pesertaData['tujuan'] ?? null,
                    'rekening' => $pesertaData['rekening'] ?? null,
                    'tgl_berangkat' => $pesertaData['tgl_berangkat'],
                    'lama_hari' => $pesertaData['lama_hari'],
                    'biaya_tiket' => $pesertaData['biaya_tiket'] ?? 0,
                    'biaya_transport' => $pesertaData['biaya_transport'] ?? 0,
                    'biaya_penginapan' => $pesertaData['biaya_penginapan'] ?? 0,
                    'uang_harian' => $pesertaData['uang_harian'] ?? 0,
                    'uang_representasi' => $pesertaData['uang_representasi'] ?? 0,
                    'spt_file_path' => $sptFilePath,
                    'spt_file_name' => $sptFileName,
                ]);
            }

            // Log status awal dengan schema v2
            $this->writeTagihanLog($tagihan, null, 'DRAFT', 'CREATE', 'Tagihan Perjaldin dibuat oleh Operator.');

            // Rebuild rekap komponen otomatis
            app(PerjaldinKomponenService::class)->rebuildFromTagihan($tagihan);

            DB::commit();
            return redirect()->route('perjaldins.index')->with('success', 'Data Perjaldin berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    /**
     * Ajukan beberapa Tagihan Perjaldin sekaligus ke Workflow.
     */
    public function bulkSubmit(Request $request, \App\Services\PerjaldinWorkflowService $workflowService)
    {
        $request->validate([
            'tagihan_ids' => 'required|array',
            'tagihan_ids.*' => 'exists:tagihan,id',
        ]);

        $tagihans = Tagihan::whereIn('id', $request->tagihan_ids)
            ->where('tipe_tagihan', 'PERJALDIN')
            ->get();

        $updatedCount = 0;
        $failedCount = 0;

        foreach ($tagihans as $tagihan) {
            try {
                $workflowService->submit($tagihan, auth()->user(), request()->ip());
                $updatedCount++;
            } catch (\Exception $e) {
                // Ignore errors for individual tagihan in bulk submit to allow valid ones to pass
                $failedCount++;
            }
        }

        if ($updatedCount > 0) {
            // Kirim notifikasi ke PPK (Optional)
            try {
                $verifikators = \App\Models\User::role([
                    'PPSPM',
                    'Bendahara Penerimaan',
                    'Bendahara Pengeluaran',
                    'PPK',
                ])->get();
                \Illuminate\Support\Facades\Notification::send($verifikators, new \App\Notifications\WorkflowNotification([
                    'title' => 'Pengajuan Perjaldin Baru',
                    'message' => "Ada {$updatedCount} pengajuan Perjaldin baru masuk alur verifikasi.",
                    'url' => route('dashboard'),
                    'icon' => 'assignment',
                    'color' => 'warning'
                ]));
            } catch (\Exception $e) {
                // Notifikasi gagal tidak menghentikan proses
            }
        }

        $msg = "{$updatedCount} Perjaldin berhasil diajukan.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} gagal karena status tidak valid).";
        }

        return redirect()->route('perjaldins.index')->with('success', $msg);
    }

    /**
     * Detail satu Tagihan Perjaldin.
     */
    public function show($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with([
                'detailPerjaldin.pegawai',
                'detailPerjaldin.provinsi',
                'komponenPerjaldin.dokumenSpp.spm.npi.sp2d',
                'komponenPerjaldin.dipaRevisionItem.coa',
                'logs' => fn($q) => $q->latest(),
                'workflowInstances' => fn($q) => $q->latest(),
                'workflowInstances.approvals.actedByUser',
            ])
            ->findOrFail($id);

        $budgetGroups = DipaBudgetOptionService::groupedOptions();

        return view('perjaldins.show', compact('tagihan', 'budgetGroups'));
    }

    /**
     * Form edit Tagihan Perjaldin.
     */
    public function editPerjaldin($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with('detailPerjaldin.pegawai')
            ->findOrFail($id);

        if (!in_array($tagihan->status, $this->editablePerjaldinStatuses())) {
            return redirect()->route('perjaldins.index')
                ->withErrors(['error' => 'Tagihan tidak bisa diedit karena statusnya sudah: ' . $tagihan->status]);
        }

        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $masterProvinsi = MasterUangHarianPerjaldin::orderBy('provinsi')->get();
        $ppkUsers = User::role('PPK')->with('profilable')->orderByDisplayName()->get();
        $ppspmUsers = User::role('PPSPM')->with('profilable')->orderByDisplayName()->get();
        $bendaharaPenerimaanUsers = User::role('Bendahara Penerimaan')->with('profilable')->orderByDisplayName()->get();
        $bendaharaUsers = User::role('Bendahara Pengeluaran')->with('profilable')->orderByDisplayName()->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->with('profilable')->orderByDisplayName()->first();
        $koorKeuanganUsers = User::role('Koordinator Keuangan')->with('profilable')->orderByDisplayName()->get();
        $masterPegawai = MasterPegawai::where('status_aktif', true)->orderBy('nama_lengkap')->get();

        return view('perjaldins.edit-perjaldin', compact('tagihan', 'budgetGroups', 'masterProvinsi', 'ppkUsers', 'ppspmUsers', 'bendaharaPenerimaanUsers', 'bendaharaUsers', 'kasubbagUser', 'koorKeuanganUsers', 'masterPegawai'));
    }

    /**
     * Update Tagihan Perjaldin beserta detail pesertanya.
     */
    public function updatePerjaldin(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);

        if (!in_array($tagihan->status, $this->editablePerjaldinStatuses())) {
            return redirect()->route('perjaldins.index')
                ->withErrors(['error' => 'Tagihan tidak bisa diedit karena statusnya sudah: ' . $tagihan->status]);
        }

        // Strip commas
        $input = $request->all();
        if (isset($input['peserta']) && is_array($input['peserta'])) {
            $input['peserta'] = $this->normalizePesertaNominals($input['peserta']);
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'nomor_perjaldin' => 'required|string|max:100',
            'periode_bulan' => 'required|integer|min:1|max:12',
            'periode_tahun' => 'required|integer|min:2000|max:2100',
            'kota_ttd' => 'required|string|max:100',
            'tanggal_ttd' => 'required|date',
            'ppk_user_id' => 'nullable|exists:users,id',
            'ppk_nama_snapshot' => 'required|string|max:150',
            'ppk_nip_snapshot' => 'required|string|max:100',
            'ppspm_user_id' => 'required|exists:users,id',
            'ppspm_nama_snapshot' => 'required|string|max:150',
            'ppspm_nip_snapshot' => 'nullable|string|max:100',
            'bendahara_penerimaan_user_id' => 'required|exists:users,id',
            'bendahara_penerimaan_nama_snapshot' => 'required|string|max:150',
            'bendahara_penerimaan_nip_snapshot' => 'nullable|string|max:100',
            'bendahara_pengeluaran_user_id' => 'required|exists:users,id',
            'bendahara_pengeluaran_nama_snapshot' => 'required|string|max:150',
            'bendahara_pengeluaran_nip_snapshot' => 'required|string|max:100',
            'kasubbag_user_id' => 'required|exists:users,id',
            'kasubbag_nama_snapshot' => 'required|string|max:150',
            'kasubbag_nip_snapshot' => 'nullable|string|max:100',
            'koordinator_keuangan_user_id' => 'nullable|exists:users,id',
            'koordinator_keuangan_nama_snapshot' => 'nullable|string|max:150',
            'koordinator_keuangan_nip_snapshot' => 'nullable|string|max:100',

            'peserta' => 'required|array|min:1',
            'peserta.*.detail_id' => 'nullable|exists:detail_perjaldin,id',
            'peserta.*.nama_pegawai' => 'required|string|max:150',
            'peserta.*.nip' => 'nullable|string|max:100',
            'peserta.*.no_spt' => 'required|string|max:100',
            'peserta.*.no_sppd' => 'required|string|max:100',
            'peserta.*.provinsi_id' => 'required|exists:master_uang_harian_perjaldins,id',
            'peserta.*.tipe_perjalanan' => 'required|string|in:luar_kota,dalam_kota_lebih_8_jam,diklat',
            'peserta.*.spt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'peserta.*.tujuan' => 'nullable|string|max:255',
            'peserta.*.rekening' => 'nullable|string|max:100',
            'peserta.*.tgl_berangkat' => 'required|date',
            'peserta.*.lama_hari' => 'required|integer|min:1',
            'peserta.*.biaya_tiket' => 'nullable|numeric|min:0',
            'peserta.*.biaya_transport' => 'nullable|numeric|min:0',
            'peserta.*.biaya_penginapan' => 'nullable|numeric|min:0',
            'peserta.*.uang_harian' => 'nullable|numeric|min:0',
            'peserta.*.uang_representasi' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Hitung total bruto
            $totalBruto = $this->calculatePerjaldinGrandTotal($request->peserta);

            $oldStatus = $tagihan->status;

            $tagihan->update([
                'nomor_tagihan' => $request->nomor_perjaldin,
                'deskripsi' => $request->deskripsi,
                'periode_bulan' => $request->periode_bulan,
                'periode_tahun' => $request->periode_tahun,
                'kota_ttd' => $request->kota_ttd,
                'tanggal_ttd' => $request->tanggal_ttd,
                'ppk_user_id' => $request->ppk_user_id,
                'ppk_nama_snapshot' => $request->ppk_nama_snapshot,
                'ppk_nip_snapshot' => $request->ppk_nip_snapshot,
                'ppspm_user_id' => $request->ppspm_user_id,
                'ppspm_nama_snapshot' => $request->ppspm_nama_snapshot,
                'ppspm_nip_snapshot' => $request->ppspm_nip_snapshot,
                'bendahara_penerimaan_user_id' => $request->bendahara_penerimaan_user_id,
                'bendahara_penerimaan_nama_snapshot' => $request->bendahara_penerimaan_nama_snapshot,
                'bendahara_penerimaan_nip_snapshot' => $request->bendahara_penerimaan_nip_snapshot,
                'bendahara_pengeluaran_user_id' => $request->bendahara_pengeluaran_user_id,
                'bendahara_pengeluaran_nama_snapshot' => $request->bendahara_pengeluaran_nama_snapshot,
                'bendahara_pengeluaran_nip_snapshot' => $request->bendahara_pengeluaran_nip_snapshot,
                'kasubbag_user_id' => $request->kasubbag_user_id,
                'kasubbag_nama_snapshot' => $request->kasubbag_nama_snapshot,
                'kasubbag_nip_snapshot' => $request->kasubbag_nip_snapshot,
                'koordinator_keuangan_user_id' => $request->koordinator_keuangan_user_id,
                'koordinator_keuangan_nama_snapshot' => $request->koordinator_keuangan_nama_snapshot,
                'koordinator_keuangan_nip_snapshot' => $request->koordinator_keuangan_nip_snapshot,
                'total_bruto' => $totalBruto,
                'total_netto' => $totalBruto - $tagihan->total_potongan,
                'status' => 'DRAFT', // Reset ke draft saat diedit
            ]);

            // Ambil detail lama untuk mempertahankan spt_file jika tidak diupload baru
            $oldDetails = DetailPerjaldin::where('tagihan_id', $tagihan->id)->get()->keyBy('id');
            $keptDetailIds = array_filter(array_column($request->peserta, 'detail_id'));
            
            // Hapus file fisik dari detail yang di-remove user
            foreach ($oldDetails as $oldId => $oldDetail) {
                if (!in_array($oldId, $keptDetailIds) && $oldDetail->spt_file_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldDetail->spt_file_path);
                }
            }

            // Hapus detail lama lalu re-insert
            DetailPerjaldin::where('tagihan_id', $tagihan->id)->delete();

            foreach ($request->peserta as $index => $pesertaData) {
                $sptFilePath = null;
                $sptFileName = null;
                $oldExisting = null;

                if (!empty($pesertaData['detail_id']) && $oldDetails->has($pesertaData['detail_id'])) {
                    $oldExisting = $oldDetails->get($pesertaData['detail_id']);
                    $sptFilePath = $oldExisting->spt_file_path;
                    $sptFileName = $oldExisting->spt_file_name;
                }

                if ($request->hasFile("peserta.{$index}.spt_file")) {
                    $file = $request->file("peserta.{$index}.spt_file");
                    $sptFileName = $file->getClientOriginalName();
                    $sptFilePath = $file->store('perjaldin/spt', 'public');
                    
                    if ($oldExisting && $oldExisting->spt_file_path) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($oldExisting->spt_file_path);
                    }
                }

                DetailPerjaldin::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_pegawai' => $pesertaData['nama_pegawai'],
                    'nip' => $pesertaData['nip'] ?? null,
                    'no_spt' => $pesertaData['no_spt'],
                    'no_sppd' => $pesertaData['no_sppd'] ?? null,
                    'provinsi_id' => $pesertaData['provinsi_id'] ?? null,
                    'tipe_perjalanan' => $pesertaData['tipe_perjalanan'] ?? null,
                    'tujuan' => $pesertaData['tujuan'] ?? null,
                    'rekening' => $pesertaData['rekening'] ?? null,
                    'tgl_berangkat' => $pesertaData['tgl_berangkat'],
                    'lama_hari' => $pesertaData['lama_hari'],
                    'biaya_tiket' => $pesertaData['biaya_tiket'] ?? 0,
                    'biaya_transport' => $pesertaData['biaya_transport'] ?? 0,
                    'biaya_penginapan' => $pesertaData['biaya_penginapan'] ?? 0,
                    'uang_harian' => $pesertaData['uang_harian'] ?? 0,
                    'uang_representasi' => $pesertaData['uang_representasi'] ?? 0,
                    'spt_file_path' => $sptFilePath,
                    'spt_file_name' => $sptFileName,
                ]);
            }

            // Log status dengan schema v2
            $this->writeTagihanLog($tagihan, $oldStatus, 'DRAFT', 'UPDATE', 'Data perjaldin diperbarui oleh Operator.');

            // Rebuild rekap komponen otomatis
            app(PerjaldinKomponenService::class)->rebuildFromTagihan($tagihan);

            DB::commit();
            return redirect()->route('perjaldins.index')->with('success', 'Data Perjaldin berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus Tagihan Perjaldin beserta seluruh detail.
     */
    public function destroyPerjaldin($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);

        if (!in_array($tagihan->status, $this->editablePerjaldinStatuses())) {
            return redirect()->route('perjaldins.index')
                ->withErrors(['error' => 'Tidak dapat menghapus tagihan dengan status: ' . $tagihan->status]);
        }

        DetailPerjaldin::where('tagihan_id', $tagihan->id)->delete();
        $tagihan->logs()->delete();
        $tagihan->delete();

        return redirect()->route('perjaldins.index')->with('success', 'Perjaldin beserta seluruh datanya berhasil dihapus.');
    }

    public function exportPdf($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with(['detailPerjaldin.pegawai', 'detailPerjaldin.provinsi'])
            ->findOrFail($id);

        $data = [
            'tagihan' => $tagihan,
            'details' => $tagihan->detailPerjaldin
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('perjaldins.pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Nominatif_Perjaldin_' . \Illuminate\Support\Str::slug($tagihan->nomor_tagihan, '_') . '.pdf');
    }

    private function editablePerjaldinStatuses(): array
    {
        return [
            'DRAFT',
            'REVISI_PPK',
            'REVISI_PPSPM',
            'REVISI_BENDAHARA',
            'REVISI_BENDAHARA_PENERIMAAN',
            'REVISI_BENDAHARA_PENGELUARAN',
            'REVISI_KASUBBAG',
            'DITOLAK_PPK',
            'DITOLAK_PPSPM',
            'DITOLAK_BENDAHARA_PENERIMAAN',
            'DITOLAK_BENDAHARA_PENGELUARAN',
            'DITOLAK_KASUBBAG',
        ];
    }
}
