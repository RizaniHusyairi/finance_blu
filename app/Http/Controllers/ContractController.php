<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Models\ArsipDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\DocumentNumberService;
use App\Services\EmailNotificationService;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Notification;
use App\Support\DipaBudgetOptionService;
use App\Support\ContractDocumentTte;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contracts = \App\Models\KontrakPengadaan::with(['vendor', 'ppkUser.profilable', 'addendums', 'termin'])->latest()->get();
        $addendums = \App\Models\KontrakAddendum::with(['kontrakUtama.vendor', 'logs.user'])->latest()->get();

        $totalAktif = $contracts->where('status_kontrak', 'AKTIF')->count();
        $totalSelesai = $contracts->where('status_kontrak', 'SELESAI')->count();
        $totalAdendum = $contracts->filter(function($c) { return $c->addendums->count() > 0; })->count();
        $totalNilaiAll = $contracts->sum('nilai_total_kontrak');
        
        $hampirHabisNilai = 0;
        $hampirHabisMasa = 0;

        foreach ($contracts as $c) {
            // Placeholder: hitung realisasi jika tagihan/termin nanti dibuat
            $realisasi = 0; 
            $c->realisasi_pembayaran = $realisasi;
            $c->sisa_kontrak = $c->nilai_total_kontrak - $realisasi;

            if ($c->status_kontrak == 'AKTIF') {
                if ($c->nilai_total_kontrak > 0 && $c->sisa_kontrak <= ($c->nilai_total_kontrak * 0.2)) {
                    $hampirHabisNilai++;
                }
                if ($c->tanggal_selesai && \Carbon\Carbon::parse($c->tanggal_selesai)->isBetween(now(), now()->addDays(30))) {
                    $hampirHabisMasa++;
                }
            }
        }

        return view('contracts.index', compact(
            'contracts', 'addendums', 'totalAktif', 'totalSelesai', 'totalAdendum', 
            'totalNilaiAll', 'hampirHabisNilai', 'hampirHabisMasa'
        ));
    }

    /**
     * Display a listing of the contracts for PPK verification (The Queue).
     */
    public function verifikasiIndex(Request $request)
    {
        $query = \App\Models\KontrakPengadaan::with(['vendor', 'ppkUser.profilable', 'dipa'])->latest();
        
        $filter = $request->input('status', 'ALL');
        
        if ($filter === 'PENDING_REVIEW') {
            $query->where('status_kontrak', 'PENDING_REVIEW');
        } elseif ($filter === 'DRAFT') {
            $query->where('status_kontrak', 'DRAFT');
        } elseif ($filter === 'REVISI') {
            $query->where('status_kontrak', 'REVISI');
        } elseif ($filter === 'ALL') {
            $query->whereIn('status_kontrak', ['PENDING_REVIEW', 'DRAFT', 'REVISI']); 
        }

        if (Auth::user()?->hasRole('PPK')) {
            $query->where('ppk_user_id', Auth::id());
        }

        $contracts = $query->get();

        $historyQuery = \App\Models\KontrakPengadaan::with(['vendor', 'ppkUser.profilable', 'dipa', 'ppkApprover.profilable'])
            ->whereNotNull('ppk_approved_at')
            ->whereIn('status_kontrak', ['AKTIF', 'SELESAI'])
            ->orderByDesc('ppk_approved_at');

        if (Auth::user()?->hasRole('PPK')) {
            $historyQuery->where('ppk_user_id', Auth::id());
        }

        $historyContracts = $historyQuery->get();

        return view('contracts.verifikasi_index', compact('contracts', 'filter', 'historyContracts'));
    }

    /**
     * Display the specified draft contract for PPK verification (The Decision Room).
     */
    public function verifikasiShow($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with(['vendor.rekening', 'ppkUser.profilable', 'dipa.activeRevision', 'dipaRevisionItem.coa', 'termin', 'arsipDokumen'])->findOrFail($id);
        $this->ensureAssignedPpk($kontrak);
        
        // Cek Pagu DIPA
        $terpakai = \App\Models\KontrakPengadaan::where('master_dipa_id', $kontrak->master_dipa_id)
            ->whereNotIn('status_kontrak', ['DIBATALKAN', 'DRAFT', 'DRAFT']) // Hitung yang Aktif, Selesai, dan Pending PPK lain
            ->where('id', '!=', $kontrak->id)
            ->sum('nilai_total_kontrak');
            
        $sisaPagu = ($kontrak->dipa->total_pagu ?? 0) - $terpakai;
        $sisaPaguNanti = $sisaPagu - $kontrak->nilai_total_kontrak;
        
        return view('contracts.ppk_show', compact('kontrak', 'terpakai', 'sisaPagu', 'sisaPaguNanti'));
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = \App\Models\MasterPihak::query()
            ->where('status_aktif', true)
            ->whereIn('kategori', ['PENGELUARAN', 'KEDUANYA'])
            ->orderBy('nama_pihak')
            ->get();
        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $ppkUsers = User::role('PPK')->with('profilable')->orderByDisplayName()->get();
        $documentNumberService = app(DocumentNumberService::class);
        $nomorSpkPreview = $documentNumberService->previewByKey('SPK');
        $nomorSpmkPreview = $documentNumberService->previewByKey('SPMK', null, 1);

        return view('contracts.create', compact('vendors', 'budgetGroups', 'ppkUsers', 'nomorSpkPreview', 'nomorSpmkPreview'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->metode_pembayaran === 'LUMPSUM') {
            $request->merge([
                'progress_keterangan' => null,
                'progress_persentase' => null,
                'gunakan_retensi' => 0,
                'retensi_keterangan' => null,
                'retensi_persentase' => null,
            ]);
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:master_pihak,id',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'nama_pekerjaan' => 'required|string',
            'ppk_user_id' => 'required|exists:users,id',
            'nomor_surat_undangan_pengadaan' => 'required|string|max:150',
            'nomor_ba_hasil_pengadaan' => 'required|string|max:150',
            'tanggal_spk' => 'required|date',
            'tanggal_spmk' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'satuan_waktu' => 'required|in:HARI,MINGGU,BULAN',
            'jangka_waktu' => 'required|integer|min:1',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'masa_pemeliharaan_hari' => 'nullable|integer|min:0|required_if:gunakan_retensi,1',
            'tanggal_mulai_pemeliharaan' => 'nullable|date',
            'tanggal_selesai_pemeliharaan' => 'nullable|date|after_or_equal:tanggal_mulai_pemeliharaan',
            'ketentuan_denda' => 'nullable|string',
            'nilai_total_kontrak' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:LUMPSUM,TERMIN',
            'ada_uang_muka' => 'nullable|boolean',
            'nilai_uang_muka' => 'nullable|numeric|min:0|lte:nilai_total_kontrak',

            // Uploads
            'file_jaminan_um' => 'nullable|file|mimes:pdf|max:5120',
            'gambar_rab' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            
            // Termin progress + retensi
            'progress_keterangan' => 'nullable|array',
            'progress_keterangan.*' => 'nullable|string|max:255',
            'progress_persentase' => 'nullable|array',
            'progress_persentase.*' => 'nullable|numeric|min:0.0001|max:100',
            'gunakan_retensi' => 'nullable|boolean',
            'retensi_keterangan' => 'nullable|required_if:gunakan_retensi,1|string|max:255',
            'retensi_persentase' => 'nullable|required_if:gunakan_retensi,1|numeric|min:0.0001|max:100',
        ], [
            'masa_pemeliharaan_hari.required_if' => 'Masa pemeliharaan wajib diisi (minimal 1 hari) ketika kontrak menggunakan retensi.',
            'retensi_persentase.required_if' => 'Persentase retensi wajib diisi saat opsi retensi diaktifkan.',
            'retensi_keterangan.required_if' => 'Keterangan retensi wajib diisi saat opsi retensi diaktifkan.',
        ]);

        if ($request->boolean('gunakan_retensi') && (int) ($validated['masa_pemeliharaan_hari'] ?? 0) < 1) {
            return back()->withInput()->withErrors([
                'masa_pemeliharaan_hari' => 'Masa pemeliharaan minimal 1 hari karena kontrak menggunakan retensi.',
            ]);
        }

        try {
            DB::beginTransaction();

            $selectedItem = DipaBudgetOptionService::resolveActiveItem($validated['dipa_revision_item_id']);
            $dipa = $selectedItem->dipaRevision->masterDipa;
            $selectedPpk = $this->resolvePpkUser((int) $validated['ppk_user_id']);
            $documentNumberService = app(DocumentNumberService::class);
            $nomorSpk = $documentNumberService->generateByKey('SPK');
            $nomorSpmk = $documentNumberService->generateByKey('SPMK');
            
            // Cek sisa pagu COA
            $sisaPagu = $selectedItem->sisa_pagu;

            if ($validated['nilai_total_kontrak'] > $sisaPagu) {
                return back()->withInput()->withErrors([
                    'error' => "Gagal disimpan: Nilai Kontrak (Rp " . number_format($validated['nilai_total_kontrak'],0,',','.') . 
                               ") melebihi sisa pagu COA yang tersedia (Rp " . number_format($sisaPagu,0,',','.') . ")."
                ]);
            }

            // Clean format Rupiah to numeric (already done by validate if input is right? No, standard HTML <input> gives the string, wait, frontend JS `oninput` copies the clean value to hidden inputs `nilai_total_kontrak_value` and `nilai_uang_muka_value` so laravel receives clean numerics).

            // Upload files
            $pathJaminanUm = $request->hasFile('file_jaminan_um') ? $request->file('file_jaminan_um')->store('kontrak/jaminan-uang-muka', 'public') : null;
            $pathGambarRab = $request->hasFile('gambar_rab') ? $request->file('gambar_rab')->store('kontrak/gambar-rab', 'public') : null;

            $adaUangMuka = $request->has('ada_uang_muka');
            $nilaiUangMuka = $adaUangMuka ? (float) ($validated['nilai_uang_muka'] ?? 0) : 0;
            $initialSisaUangMuka = $nilaiUangMuka;

            $kontrak = \App\Models\KontrakPengadaan::create([
                'vendor_id' => $validated['vendor_id'],
                'ppk_user_id' => $selectedPpk->id,
                'master_dipa_id' => $dipa->id,
                'dipa_revision_item_id' => $selectedItem->id,
                'nomor_spk' => $nomorSpk,
                'tanggal_spk' => $validated['tanggal_spk'],
                'nomor_spmk' => $nomorSpmk,
                'tanggal_spmk' => $validated['tanggal_spmk'],
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'nomor_surat_undangan_pengadaan' => $validated['nomor_surat_undangan_pengadaan'],
                'nomor_ba_hasil_pengadaan' => $validated['nomor_ba_hasil_pengadaan'],
                'nilai_total_kontrak' => $validated['nilai_total_kontrak'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'ada_uang_muka' => $adaUangMuka ? 1 : 0,
                'nilai_uang_muka' => $nilaiUangMuka,
                'sisa_uang_muka_belum_lunas' => $initialSisaUangMuka,
                'jangka_waktu' => $validated['jangka_waktu'],
                'satuan_waktu' => $validated['satuan_waktu'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'masa_pemeliharaan_hari' => $validated['masa_pemeliharaan_hari'] ?? 0,
                'tanggal_mulai_pemeliharaan' => $validated['tanggal_mulai_pemeliharaan'] ?? null,
                'tanggal_selesai_pemeliharaan' => $validated['tanggal_selesai_pemeliharaan'] ?? null,
                'ketentuan_denda' => $validated['ketentuan_denda'] ?? null,
                'status_kontrak' => 'DRAFT',
            ]);

            $this->syncKontrakArsip($kontrak, [
                'JAMINAN_UANG_MUKA' => $pathJaminanUm,
                'GAMBAR_RAB' => $pathGambarRab,
            ]);

            if ($validated['metode_pembayaran'] === 'TERMIN') {
                $terminPayload = $this->buildTerminScheme($request, (float) $validated['nilai_total_kontrak']);
                $terminPayload = $this->attachAngsuranUangMuka($terminPayload, $nilaiUangMuka);

                foreach ($terminPayload as $index => $termin) {
                    \App\Models\KontrakTermin::create([
                        'kontrak_pengadaan_id' => $kontrak->id,
                        'termin_ke' => $index + 1,
                        'keterangan_termin' => $termin['keterangan_termin'],
                        'persentase' => $termin['persentase'],
                        'nilai_bruto_termin' => $termin['nilai_bruto_termin'],
                        'potongan_angsuran_uang_muka' => $termin['potongan_angsuran_uang_muka'],
                        'jenis_termin' => $termin['jenis_termin'],
                        'status_termin' => $index === 0 ? 'READY_TO_BILL' : 'LOCKED',
                    ]);
                }
            } else {
                // LUMPSUM
                \App\Models\KontrakTermin::create([
                    'kontrak_pengadaan_id' => $kontrak->id,
                    'termin_ke' => 1,
                    'keterangan_termin' => 'Pelunasan Sekaligus (LUMPSUM)',
                    'persentase' => 100,
                    'nilai_bruto_termin' => $validated['nilai_total_kontrak'],
                    'potongan_angsuran_uang_muka' => $nilaiUangMuka,
                    'jenis_termin' => 'PELUNASAN',
                    'status_termin' => 'READY_TO_BILL'
                ]);
            }

            DB::commit();

            return redirect()->route('contracts.index')->with('success', 'Kontrak Pengadaan berhasil dibuat (Status: DRAFT). Menunggu diajukan ke PPK.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan kontrak: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with([
            'termin.detailKontrak.tagihan.logs.user',
            'addendums.logs.user',
            'addendums.arsipDokumen',
            'vendor.rekening',
            'ppkUser.profilable',
            'dipa.activeRevision',
            'dipaRevisionItem.coa',
            'arsipDokumen',
            'jaminanKontrak',
        ])->findOrFail($id);

        // 1. Ambil log pembuatan kontrak
        $logPembuatan = collect([[
            'tanggal' => $kontrak->created_at,
            'judul' => 'Kontrak (SPK) Dibuat & Aktif',
            'aktor' => 'Sistem (Pembuat Awal)',
            'catatan' => 'Nilai: Rp ' . number_format($kontrak->nilai_total_kontrak, 0, ',', '.'),
            'ikon' => 'bi-file-signature'
        ]]);

        // 2. Ambil log dari Addendum (jika ada)
        $logAddendum = $kontrak->addendums->map(function($add) {
            $latestLog = $add->logs->sortByDesc('created_at')->first();

            return [
                'tanggal' => $latestLog?->created_at ?? $add->created_at,
                'judul' => 'Addendum ' . $add->nomor_addendum . ' - ' . str_replace('_', ' ', $add->status_workflow ?? 'DRAFT'),
                'aktor' => $latestLog?->user?->name ?? 'Sistem',
                'catatan' => $latestLog?->catatan ?? ($add->keterangan_alasan ?? 'Perubahan kontrak'),
                'ikon' => 'bi-pencil-square'
            ];
        });

        // 3. Ambil pergerakan Tagihan & SP2D dari Termin Kontrak ini
        $logPencairan = collect();
        foreach ($kontrak->termin as $termin) {
            if ($termin->detailKontrak && $termin->detailKontrak->tagihan) {
                foreach ($termin->detailKontrak->tagihan->logs as $log) {
                    $logPencairan->push([
                        'tanggal' => $log->created_at,
                        'judul' => 'Status Tagihan: ' . $log->status_baru,
                        'aktor' => $log->user ? $log->user->name . ' (' . $log->role_saat_itu . ')' : 'Sistem (' . $log->role_saat_itu . ')',
                        'catatan' => $log->catatan ?? '-',
                        'ikon' => 'bi-cash-coin'
                    ]);
                }
            }
        }

        // 4. Gabungkan semua log, lalu urutkan dari yang paling baru
        $semuaAktivitas = $logPembuatan
            ->concat($logAddendum)
            ->concat($logPencairan)
            ->sortByDesc('tanggal')
            ->values();

        return view('contracts.show', compact('kontrak', 'semuaAktivitas'));
    }




    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with(['termin', 'arsipDokumen'])->findOrFail($id);
        
        // Prevent editing if not DRAFT or DRAFT
        if (!in_array($kontrak->status_kontrak, ['DRAFT', 'DRAFT'])) {
            return redirect()->route('contracts.index')->with('error', 'Kontrak tidak dapat diubah karena sudah diajukan atau aktif.');
        }

        $vendors = \App\Models\MasterPihak::query()
            ->where('status_aktif', true)
            ->whereIn('kategori', ['PENGELUARAN', 'KEDUANYA'])
            ->orderBy('nama_pihak')
            ->get();
        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $ppkUsers = User::role('PPK')->with('profilable')->orderByDisplayName()->get();
        $selectedPpkUserId = $kontrak->ppk_user_id;

        return view('contracts.edit', compact('kontrak', 'vendors', 'budgetGroups', 'ppkUsers', 'selectedPpkUserId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $kontrak = \App\Models\KontrakPengadaan::findOrFail($id);
        
        if (!in_array($kontrak->status_kontrak, ['DRAFT', 'DRAFT'])) {
            return redirect()->route('contracts.index')->with('error', 'Kontrak tidak dapat diubah karena statusnya saat ini.');
        }

        if ($request->metode_pembayaran === 'LUMPSUM') {
            $request->merge([
                'progress_keterangan' => null,
                'progress_persentase' => null,
                'gunakan_retensi' => 0,
                'retensi_keterangan' => null,
                'retensi_persentase' => null,
            ]);
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:master_pihak,id',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'nama_pekerjaan' => 'required|string',
            'ppk_user_id' => 'required|exists:users,id',
            'nomor_surat_undangan_pengadaan' => 'required|string|max:150',
            'nomor_ba_hasil_pengadaan' => 'required|string|max:150',
            'tanggal_spk' => 'required|date',
            'tanggal_spmk' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'satuan_waktu' => 'required|in:HARI,MINGGU,BULAN',
            'jangka_waktu' => 'required|integer|min:1',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'masa_pemeliharaan_hari' => 'nullable|integer|min:0|required_if:gunakan_retensi,1',
            'tanggal_mulai_pemeliharaan' => 'nullable|date',
            'tanggal_selesai_pemeliharaan' => 'nullable|date|after_or_equal:tanggal_mulai_pemeliharaan',
            'ketentuan_denda' => 'nullable|string',
            'nilai_total_kontrak' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:LUMPSUM,TERMIN',
            'ada_uang_muka' => 'nullable|boolean',
            'nilai_uang_muka' => 'nullable|numeric|min:0|lte:nilai_total_kontrak',
            
            // Uploads
            'file_jaminan_um' => 'nullable|file|mimes:pdf|max:5120',
            'gambar_rab' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            
            // Termin progress + retensi
            'progress_keterangan' => 'nullable|array',
            'progress_keterangan.*' => 'nullable|string|max:255',
            'progress_persentase' => 'nullable|array',
            'progress_persentase.*' => 'nullable|numeric|min:0.0001|max:100',
            'gunakan_retensi' => 'nullable|boolean',
            'retensi_keterangan' => 'nullable|required_if:gunakan_retensi,1|string|max:255',
            'retensi_persentase' => 'nullable|required_if:gunakan_retensi,1|numeric|min:0.0001|max:100',
        ], [
            'masa_pemeliharaan_hari.required_if' => 'Masa pemeliharaan wajib diisi (minimal 1 hari) ketika kontrak menggunakan retensi.',
            'retensi_persentase.required_if' => 'Persentase retensi wajib diisi saat opsi retensi diaktifkan.',
            'retensi_keterangan.required_if' => 'Keterangan retensi wajib diisi saat opsi retensi diaktifkan.',
        ]);

        if ($request->boolean('gunakan_retensi') && (int) ($validated['masa_pemeliharaan_hari'] ?? 0) < 1) {
            return back()->withInput()->withErrors([
                'masa_pemeliharaan_hari' => 'Masa pemeliharaan minimal 1 hari karena kontrak menggunakan retensi.',
            ]);
        }

        try {
            DB::beginTransaction();

            $selectedItem = DipaBudgetOptionService::resolveActiveItem($validated['dipa_revision_item_id']);
            $dipa = $selectedItem->dipaRevision->masterDipa;
            $selectedPpk = $this->resolvePpkUser((int) $validated['ppk_user_id']);
            
            // Cek sisa pagu COA
            $sisaPagu = $selectedItem->sisa_pagu;
            if ($kontrak->dipa_revision_item_id == $selectedItem->id) {
                $sisaPagu += $kontrak->nilai_total_kontrak;
            }

            if ($validated['nilai_total_kontrak'] > $sisaPagu) {
                return back()->withInput()->withErrors([
                    'error' => "Gagal disimpan: Nilai Kontrak (Rp " . number_format($validated['nilai_total_kontrak'],0,',','.') . 
                               ") melebihi sisa pagu COA yang tersedia (Rp " . number_format($sisaPagu,0,',','.') . ")."
                ]);
            }

            // Upload files (delete old ones if new uploaded)
            if ($request->hasFile('file_jaminan_um')) {
                if ($kontrak->file_jaminan_uang_muka) \Illuminate\Support\Facades\Storage::disk('public')->delete($kontrak->file_jaminan_uang_muka);
                $validated['file_jaminan_um'] = $request->file('file_jaminan_um')->store('kontrak/jaminan-uang-muka', 'public');
            } else {
                $validated['file_jaminan_um'] = $kontrak->file_jaminan_uang_muka;
            }

            if ($request->hasFile('gambar_rab')) {
                if ($kontrak->file_gambar_rab) \Illuminate\Support\Facades\Storage::disk('public')->delete($kontrak->file_gambar_rab);
                $validated['gambar_rab'] = $request->file('gambar_rab')->store('kontrak/gambar-rab', 'public');
            } else {
                $validated['gambar_rab'] = $kontrak->file_gambar_rab;
            }

            $ada_uang_muka = $request->has('ada_uang_muka') ? 1 : 0;
            $nilai_uang_muka = $ada_uang_muka ? (float) ($validated['nilai_uang_muka'] ?? 0) : 0;

            $kontrak->update([
                'vendor_id' => $validated['vendor_id'],
                'ppk_user_id' => $selectedPpk->id,
                'master_dipa_id' => $dipa->id,
                'dipa_revision_item_id' => $selectedItem->id,
                'tanggal_spk' => $validated['tanggal_spk'],
                'tanggal_spmk' => $validated['tanggal_spmk'],
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'nomor_surat_undangan_pengadaan' => $validated['nomor_surat_undangan_pengadaan'],
                'nomor_ba_hasil_pengadaan' => $validated['nomor_ba_hasil_pengadaan'],
                'nilai_total_kontrak' => $validated['nilai_total_kontrak'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'ada_uang_muka' => $ada_uang_muka,
                'nilai_uang_muka' => $nilai_uang_muka,
                'sisa_uang_muka_belum_lunas' => $nilai_uang_muka,
                'jangka_waktu' => $validated['jangka_waktu'],
                'satuan_waktu' => $validated['satuan_waktu'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'masa_pemeliharaan_hari' => $validated['masa_pemeliharaan_hari'] ?? 0,
                'tanggal_mulai_pemeliharaan' => $validated['tanggal_mulai_pemeliharaan'] ?? null,
                'tanggal_selesai_pemeliharaan' => $validated['tanggal_selesai_pemeliharaan'] ?? null,
                'ketentuan_denda' => $validated['ketentuan_denda'] ?? null,
            ]);

            if (!$ada_uang_muka) {
                $existingJaminanUm = $kontrak->arsipDokumen()->where('jenis_dokumen', 'JAMINAN_UANG_MUKA')->first();
                if ($existingJaminanUm) {
                    \Illuminate\Support\Facades\Storage::disk($existingJaminanUm->disk ?? 'public')->delete($existingJaminanUm->path_file);
                    $existingJaminanUm->delete();
                }
            }

            $this->syncKontrakArsip($kontrak, [
                'JAMINAN_UANG_MUKA' => $ada_uang_muka ? $validated['file_jaminan_um'] : null,
                'GAMBAR_RAB' => $validated['gambar_rab'],
            ]);

            // Hapus Termin Lama
            \App\Models\KontrakTermin::where('kontrak_pengadaan_id', $kontrak->id)->delete();

            if ($validated['metode_pembayaran'] === 'TERMIN') {
                $terminPayload = $this->buildTerminScheme($request, (float) $validated['nilai_total_kontrak']);
                $terminPayload = $this->attachAngsuranUangMuka($terminPayload, $nilai_uang_muka);

                foreach ($terminPayload as $index => $termin) {
                    \App\Models\KontrakTermin::create([
                        'kontrak_pengadaan_id' => $kontrak->id,
                        'termin_ke' => $index + 1,
                        'keterangan_termin' => $termin['keterangan_termin'],
                        'persentase' => $termin['persentase'],
                        'nilai_bruto_termin' => $termin['nilai_bruto_termin'],
                        'potongan_angsuran_uang_muka' => $termin['potongan_angsuran_uang_muka'],
                        'jenis_termin' => $termin['jenis_termin'],
                        'status_termin' => $index === 0 ? 'READY_TO_BILL' : 'LOCKED'
                    ]);
                }
            } else {
                \App\Models\KontrakTermin::create([
                    'kontrak_pengadaan_id' => $kontrak->id,
                    'termin_ke' => 1,
                    'keterangan_termin' => 'Pelunasan Sekaligus (LUMPSUM)',
                    'persentase' => 100,
                    'nilai_bruto_termin' => $validated['nilai_total_kontrak'],
                    'potongan_angsuran_uang_muka' => $nilai_uang_muka,
                    'jenis_termin' => 'PELUNASAN',
                    'status_termin' => 'READY_TO_BILL'
                ]);
            }

            DB::commit();

            return redirect()->route('contracts.index')->with('success', 'Perubahan Kontrak berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal mengubah kontrak: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $contract = \App\Models\KontrakPengadaan::findOrFail($id);
        
        if (!in_array($contract->status_kontrak, ['DRAFT', 'DRAFT'])) {
            return redirect()->route('contracts.index')->with('error', 'Kontrak aktif tidak dapat dihapus.');
        }
        
        foreach ($contract->arsipDokumen as $arsip) {
            \Illuminate\Support\Facades\Storage::disk($arsip->disk ?? 'public')->delete($arsip->path_file);
            $arsip->delete();
        }
        
        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Kontrak berhasil dihapus.');
    }

    public function submit($id)
    {
        abort_unless(Auth::user()?->hasAnyRole(['Super Admin', 'Pejabat Pengadaan']), 403);

        $kontrak = \App\Models\KontrakPengadaan::findOrFail($id);

        if (! in_array($kontrak->status_kontrak, ['DRAFT', 'REVISI'], true)) {
            return back()->with('error', 'Kontrak hanya dapat diajukan dari status DRAFT atau REVISI.');
        }

        $statusSebelumnya = $kontrak->status_kontrak;

        $kontrak->update([
            'status_kontrak' => 'PENDING_REVIEW',
            'diajukan_at' => now(),
            'ppk_catatan' => null,
        ]);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type' => \App\Models\KontrakPengadaan::class,
            'dokumen_id' => $kontrak->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Sistem',
            'status_sebelumnya' => $statusSebelumnya,
            'status_baru' => 'PENDING_REVIEW',
            'aksi' => 'SUBMIT',
            'catatan' => 'Kontrak diajukan ke PPK tanpa upload dokumen final TTD.',
            'ip_address' => request()->ip(),
        ]);

        if ($kontrak->ppkUser) {
            Notification::send($kontrak->ppkUser, new WorkflowNotification([
                'title' => 'Kontrak Menunggu Persetujuan PPK',
                'message' => 'Kontrak ' . $kontrak->nomor_spk . ' telah diajukan untuk disetujui.',
                'url' => route('contracts.verifikasi.show', $kontrak->id),
                'icon' => 'task_alt',
                'color' => 'primary',
            ]));

            // Notifikasi WhatsApp ke PPK bahwa Pengadaan mengajukan persetujuan kontrak.
            $this->kirimWaPengajuanKontrakKePpk($kontrak);
        }

        return back()->with('success', 'Kontrak berhasil diajukan ke PPK.');
    }

    /**
     * Kirim notifikasi WhatsApp ke PPK saat Pejabat Pengadaan mengajukan
     * persetujuan kontrak. Gagal kirim tidak menggagalkan proses submit.
     */
    private function kirimWaPengajuanKontrakKePpk(\App\Models\KontrakPengadaan $kontrak): void
    {
        try {
            $kontrak->loadMissing(['ppkUser.profilable', 'vendor']);
            $ppk = $kontrak->ppkUser;

            $noHp = $ppk?->profilable->nomor_hp ?? null;
            if (! $ppk || ! $noHp) {
                \Illuminate\Support\Facades\Log::warning(
                    'WA pengajuan kontrak: PPK atau nomor HP tidak tersedia untuk kontrak ' . $kontrak->nomor_spk
                );
                return;
            }

            $url = route('contracts.verifikasi.show', $kontrak->id);

            $vendorNama = $kontrak->vendor?->nama_pihak ?? '-';
            $nilai = 'Rp ' . number_format((float) $kontrak->nilai_total_kontrak, 0, ',', '.');
            $pengaju = Auth::user()?->name ?? 'Pejabat Pengadaan';

            $message = "*PENGAJUAN PERSETUJUAN KONTRAK (PPK)*\n\n";
            $message .= "Yth. {$ppk->name},\n";
            $message .= "Terdapat kontrak/SPK yang diajukan oleh Pejabat Pengadaan dan menunggu persetujuan Anda.\n\n";
            $message .= "*Nomor SPK:* {$kontrak->nomor_spk}\n";
            $message .= "*Pekerjaan:* " . ($kontrak->nama_pekerjaan ?: '-') . "\n";
            $message .= "*Vendor:* {$vendorNama}\n";
            $message .= "*Nilai Kontrak:* {$nilai}\n";
            $message .= "*Diajukan oleh:* {$pengaju}\n\n";
            $message .= "Silakan buka tautan berikut untuk meninjau dan menyetujui:\n";
            $message .= $url . "\n\n";
            $message .= "_Login terlebih dahulu untuk mengakses halaman verifikasi._";

            app(WhatsappService::class)->sendMessage($noHp, $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal kirim WA pengajuan kontrak ke PPK: ' . $e->getMessage());
        }
    }

    public function approve($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::findOrFail($id);
        $this->ensureAssignedPpk($kontrak);

        abort_unless(Auth::user()?->hasAnyRole(['Super Admin', 'PPK']), 403);

        if ($kontrak->status_kontrak !== 'PENDING_REVIEW') {
            return back()->with('error', 'Kontrak hanya dapat disetujui saat status PENDING REVIEW.');
        }

        $kontrak->update([
            'status_kontrak' => 'AKTIF',
            'ppk_approved_at' => now(),
            'ppk_approved_by' => Auth::id(),
            'ppk_catatan' => null,
        ]);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type' => \App\Models\KontrakPengadaan::class,
            'dokumen_id' => $kontrak->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Sistem',
            'status_sebelumnya' => 'PENDING_REVIEW',
            'status_baru' => 'AKTIF',
            'aksi' => 'APPROVE',
            'catatan' => 'Kontrak disetujui PPK. Dokumen SPK, SPMK, dan Ringkasan Kontrak kini menggunakan TTE QR.',
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('contracts.verifikasi')
            ->with('success', 'Kontrak disetujui dan aktif. PDF SPK, SPMK, dan Ringkasan Kontrak kini ber-TTE QR.');
    }

    public function reject(Request $request, $id)
    {
        $kontrak = \App\Models\KontrakPengadaan::findOrFail($id);
        $this->ensureAssignedPpk($kontrak);

        abort_unless(Auth::user()?->hasAnyRole(['Super Admin', 'PPK']), 403);

        if ($kontrak->status_kontrak !== 'PENDING_REVIEW') {
            return back()->with('error', 'Kontrak hanya dapat dikembalikan saat status PENDING REVIEW.');
        }

        $validated = $request->validate([
            'notes' => 'required|string|min:10',
        ]);

        $kontrak->update([
            'status_kontrak' => 'REVISI',
            'ppk_catatan' => $validated['notes'],
        ]);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type' => \App\Models\KontrakPengadaan::class,
            'dokumen_id' => $kontrak->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'Sistem',
            'status_sebelumnya' => 'PENDING_REVIEW',
            'status_baru' => 'REVISI',
            'aksi' => 'REVISION',
            'catatan' => $validated['notes'],
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('contracts.verifikasi')->with('success', 'Kontrak dikembalikan untuk revisi.');
    }

    public function uploadSpkFinal(Request $request, $id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with('arsipDokumen')->findOrFail($id);

        $request->validate([
            'file_spk_final_ttd' => 'required|file|mimes:pdf|max:10240',
        ]);

        $this->replaceKontrakArsipAktif(
            $kontrak,
            'SPK_FINAL_TTD',
            $request->file('file_spk_final_ttd')->store('kontrak/spk-final-ttd', 'public'),
            $request->file('file_spk_final_ttd')->getClientOriginalName()
        );

        $activated = $kontrak->activateIfDocumentsComplete();
        $msg = 'SPK final bertandatangan berhasil diunggah.';
        if ($activated) $msg .= ' Kontrak kini telah AKTIF secara otomatis.';
        
        return back()->with('success', $msg);
    }

    public function uploadSpkGambarRab(Request $request, $id)
    {
        abort_unless(
            Auth::user()?->hasAnyRole(['Super Admin', 'Pejabat Pengadaan']),
            403,
            'Hanya user pengadaan yang dapat mengunggah Gambar RAB.'
        );

        $kontrak = \App\Models\KontrakPengadaan::with('arsipDokumen')->findOrFail($id);

        $request->validate([
            'gambar_rab' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'gambar_rab.required' => 'Gambar RAB wajib diunggah sebelum export PDF Draft SPK.',
            'gambar_rab.image' => 'File RAB harus berupa gambar.',
            'gambar_rab.mimes' => 'Gambar RAB harus berformat JPG, JPEG, atau PNG.',
            'gambar_rab.max' => 'Ukuran Gambar RAB maksimal 5 MB.',
        ]);

        $file = $request->file('gambar_rab');

        $this->replaceKontrakArsipAktif(
            $kontrak,
            'GAMBAR_RAB',
            $file->store('kontrak/gambar-rab', 'public'),
            $file->getClientOriginalName()
        );

        return back()->with('success', 'Gambar RAB berhasil diunggah. PDF Draft SPK sudah dapat diexport.');
    }

    public function viewSpkGambarRab($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with('arsipDokumen')->findOrFail($id);
        $gambarRabArsip = $kontrak->gambar_rab_arsip;

        abort_unless($gambarRabArsip, 404);

        $disk = $gambarRabArsip->disk ?: 'public';
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($gambarRabArsip->path_file), 404);

        return $storage->response(
            $gambarRabArsip->path_file,
            $gambarRabArsip->nama_file_asli ?: basename($gambarRabArsip->path_file)
        );
    }

    public function exportSpkPdf($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with([
            'vendor.rekening',
            'ppkUser.profilable',
            'dipa.activeRevision',
            'dipaRevisionItem.coa',
            'arsipDokumen',
        ])->findOrFail($id);

        $gambarRabArsip = $kontrak->gambar_rab_arsip;

        if (!$gambarRabArsip) {
            return back()->withErrors([
                'gambar_rab' => 'Gambar RAB wajib diunggah terlebih dahulu sebelum export PDF Draft SPK.',
            ]);
        }

        $gambarRabDataUri = $this->buildImageDataUriFromArsip($gambarRabArsip);

        if (!$gambarRabDataUri) {
            return back()->withErrors([
                'gambar_rab' => 'File Gambar RAB tidak ditemukan atau tidak dapat dibaca. Silakan unggah ulang Gambar RAB.',
            ]);
        }

        $pdf = Pdf::loadView('contracts.spk_pdf', [
            'kontrak' => $kontrak,
            'vendor' => $kontrak->vendor,
            'rekeningVendor' => optional($kontrak->vendor)->rekening?->first(),
            'dipa' => $kontrak->dipa,
            'activeRevision' => optional($kontrak->dipa)->activeRevision,
            'itemAnggaran' => $kontrak->dipaRevisionItem,
            'coa' => optional($kontrak->dipaRevisionItem)->coa,
            'gambarRabDataUri' => $gambarRabDataUri,
            'terbilangNilaiKontrak' => function_exists('terbilang_rupiah')
                ? terbilang_rupiah((float) $kontrak->nilai_total_kontrak)
                : null,
            'tteQrFilePath' => ContractDocumentTte::tteQrFilePath($kontrak, 'spk'),
        ])->setPaper('a4', 'portrait');

        $safeNomor = str_replace(['/', '\\', ' '], ['-', '-', '_'], $kontrak->nomor_spk);

        return $pdf->stream('SPK_' . $safeNomor . '.pdf');
    }

    public function uploadSpmkFinal(Request $request, $id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with('arsipDokumen')->findOrFail($id);

        $request->validate([
            'file_spmk_final_ttd' => 'required|file|mimes:pdf|max:10240',
        ]);

        $this->replaceKontrakArsipAktif(
            $kontrak,
            'SPMK_FINAL_TTD',
            $request->file('file_spmk_final_ttd')->store('kontrak/spmk-final-ttd', 'public'),
            $request->file('file_spmk_final_ttd')->getClientOriginalName()
        );

        $activated = $kontrak->activateIfDocumentsComplete();
        $msg = 'SPMK final bertandatangan berhasil diunggah.';
        if ($activated) $msg .= ' Kontrak kini telah AKTIF secara otomatis.';
        
        return back()->with('success', $msg);
    }

    public function uploadRingkasanKontrakFinal(Request $request, $id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with('arsipDokumen')->findOrFail($id);

        $request->validate([
            'file_ringkasan_kontrak_final_ttd' => 'required|file|mimes:pdf|max:10240',
        ]);

        $this->replaceKontrakArsipAktif(
            $kontrak,
            'RINGKASAN_KONTRAK_FINAL_TTD',
            $request->file('file_ringkasan_kontrak_final_ttd')->store('kontrak/ringkasan-kontrak-final-ttd', 'public'),
            $request->file('file_ringkasan_kontrak_final_ttd')->getClientOriginalName()
        );

        $activated = $kontrak->activateIfDocumentsComplete();
        $msg = 'Ringkasan Kontrak final bertandatangan berhasil diunggah.';
        if ($activated) $msg .= ' Kontrak kini telah AKTIF secara otomatis.';

        return back()->with('success', $msg);
    }

    /**
     * Kirim link portal upload dokumen final (TTD basah) ke nomor WhatsApp vendor.
     * Portal publik memuat form upload SPK, SPMK, dan Ringkasan Kontrak yang sudah ditandatangani.
     */
    public function sendWaVendor($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with('vendor')->findOrFail($id);

        abort_unless(Auth::user()?->hasAnyRole(['Super Admin', 'Pejabat Pengadaan', 'PPK']), 403);

        if (! ContractDocumentTte::isApproved($kontrak)) {
            return back()->with('error', 'Kontrak belum disetujui PPK secara elektronik.');
        }

        $vendor = $kontrak->vendor;
        $noHp = $vendor?->no_telepon;

        if (empty($noHp)) {
            return back()->with('error', 'Nomor telepon vendor belum diisi pada Master Pihak.');
        }

        $portalUrl = URL::signedRoute('public.vendor.contract-upload.show', ['id' => $kontrak->id]);

        $pesan = "*Portal Upload Dokumen Kontrak*\n\n"
            . "Yth. " . ($vendor->nama_pihak ?? 'Vendor') . ",\n\n"
            . "Berikut link unggah dokumen kontrak final bertandatangan untuk pekerjaan:\n"
            . "*" . $kontrak->nama_pekerjaan . "*\n"
            . "Nomor SPK: " . ($kontrak->nomor_spk ?? '-') . "\n\n"
            . "Mohon unggah dokumen berikut melalui tautan ini (sudah ditandatangani lengkap):\n"
            . "1. SPK Final\n"
            . "2. SPMK Final\n"
            . "3. Ringkasan Kontrak Final\n\n"
            . "Link Portal Upload:\n" . $portalUrl . "\n\n"
            . "Terima kasih.";

        $emailPesan = "Yth. " . ($vendor->nama_pihak ?? 'Vendor') . ",\n\n"
            . "Dengan hormat,\n\n"
            . "Sehubungan dengan penyelesaian dokumen kontrak, kami mohon Bapak/Ibu melakukan unggah dokumen kontrak final bertandatangan melalui portal SIKEREN-BLU.\n\n"
            . "Nama Pekerjaan : " . ($kontrak->nama_pekerjaan ?? '-') . "\n"
            . "Nomor SPK : " . ($kontrak->nomor_spk ?? '-') . "\n\n"
            . "Dokumen yang perlu diunggah:\n"
            . "1. SPK Final\n"
            . "2. SPMK Final\n"
            . "3. Ringkasan Kontrak Final\n\n"
            . "Tautan portal upload:\n"
            . $portalUrl . "\n\n"
            . "Mohon tautan ini digunakan secara bertanggung jawab dan tidak diteruskan kepada pihak yang tidak berkepentingan.\n\n"
            . "Hormat kami,\n"
            . "SIKEREN-BLU";

        $waService = app(\App\Services\WhatsappService::class);
        $ok = $waService->sendMessage($noHp, $pesan);

        if ((bool) \App\Models\IntegrationSetting::getValue('email.contract_upload.enabled', true)) {
            app(EmailNotificationService::class)->sendNotification(
                (string) ($vendor?->email ?? ''),
                'Permintaan Upload Dokumen Kontrak ' . ($kontrak->nomor_spk ?? $kontrak->id),
                $emailPesan,
                $kontrak,
                'send_contract_upload_email'
            );
        }

        if (! $ok) {
            return back()->with('error', 'Gagal mengirim pesan WhatsApp ke vendor. Silakan cek log integrasi.');
        }

        return back()->with('success', 'Link portal upload berhasil dikirim ke WhatsApp vendor (' . $noHp . ') dan email diproses bila alamat vendor tersedia.');
    }

    public function exportSpmkPdf($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with([
            'vendor.rekening',
            'ppkUser.profilable',
            'dipa.activeRevision',
            'dipaRevisionItem.coa',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('contracts.spmk_pdf', [
            'kontrak' => $kontrak,
            'vendor' => $kontrak->vendor,
            'rekeningVendor' => optional($kontrak->vendor)->rekening?->first(),
            'dipa' => $kontrak->dipa,
            'activeRevision' => optional($kontrak->dipa)->activeRevision,
            'itemAnggaran' => $kontrak->dipaRevisionItem,
            'coa' => optional($kontrak->dipaRevisionItem)->coa,
            'tteQrFilePath' => ContractDocumentTte::tteQrFilePath($kontrak, 'spmk'),
        ])->setPaper('a4', 'portrait');

        $safeNomor = str_replace(['/', '\\', ' '], ['-', '-', '_'], $kontrak->nomor_spmk ?: $kontrak->nomor_spk);

        return $pdf->stream('SPMK_' . $safeNomor . '.pdf');
    }

    public function exportRingkasanKontrakPdf($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with([
            'vendor.rekening',
            'ppkUser.profilable',
            'dipa.activeRevision',
            'dipaRevisionItem.coa',
        ])->findOrFail($id);

        $rekeningVendor = optional($kontrak->vendor)->rekening?->first();
        $caraPembayaran = $kontrak->metode_pembayaran === 'TERMIN'
            ? 'Pembayaran dilakukan secara termin melalui rekening penyedia'
            : 'Pembayaran dilakukan secara lumpsum melalui rekening penyedia';

        if ($rekeningVendor) {
            $caraPembayaran .= ' pada Bank ' . $rekeningVendor->nama_bank . ' Nomor Rekening ' . $rekeningVendor->nomor_rekening . ' a.n. ' . $rekeningVendor->nama_rekening . '.';
        } else {
            $caraPembayaran .= '.';
        }

        $pdf = Pdf::loadView('contracts.ringkasan_kontrak_pdf', [
            'kontrak' => $kontrak,
            'vendor' => $kontrak->vendor,
            'rekeningVendor' => $rekeningVendor,
            'dipa' => $kontrak->dipa,
            'activeRevision' => optional($kontrak->dipa)->activeRevision,
            'itemAnggaran' => $kontrak->dipaRevisionItem,
            'coa' => optional($kontrak->dipaRevisionItem)->coa,
            'caraPembayaran' => $caraPembayaran,
            'tteQrFilePath' => ContractDocumentTte::tteQrFilePath($kontrak, 'ringkasan_kontrak'),
        ])->setPaper('a4', 'portrait');

        $safeNomor = str_replace(['/', '\\', ' '], ['-', '-', '_'], $kontrak->nomor_spk ?: 'ringkasan-kontrak');

        return $pdf->stream('Ringkasan_Kontrak_' . $safeNomor . '.pdf');
    }

    private function syncKontrakArsip(\App\Models\KontrakPengadaan $kontrak, array $files): void
    {
        foreach ($files as $jenis => $path) {
            if (!$path) {
                continue;
            }

            $existing = $kontrak->arsipDokumen()->where('jenis_dokumen', $jenis)->first();

            if ($existing) {
                \Illuminate\Support\Facades\Storage::disk($existing->disk ?? 'public')->delete($existing->path_file);
                $existing->delete();
            }

            $kontrak->arsipDokumen()->create([
                'jenis_dokumen' => $jenis,
                'nama_file_asli' => basename($path),
                'path_file' => $path,
                'disk' => 'public',
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ]);
        }
    }

    private function replaceKontrakArsipAktif(\App\Models\KontrakPengadaan $kontrak, string $jenisDokumen, string $path, ?string $originalName = null): ArsipDokumen
    {
        $kontrak->arsipDokumen()
            ->where('jenis_dokumen', $jenisDokumen)
            ->where('is_active', true)
            ->get()
            ->each(function (ArsipDokumen $arsip) {
                $arsip->update(['is_active' => false]);
            });

        return $kontrak->arsipDokumen()->create([
            'jenis_dokumen' => $jenisDokumen,
            'nama_file_asli' => $originalName ?: basename($path),
            'path_file' => $path,
            'disk' => 'public',
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'is_active' => true,
        ]);
    }

    private function hasSpkFinalTtd(\App\Models\KontrakPengadaan $kontrak): bool
    {
        if ($kontrak->relationLoaded('arsipDokumen')) {
            return $kontrak->arsipDokumen
                ->where('jenis_dokumen', 'SPK_FINAL_TTD')
                ->where('is_active', true)
                ->isNotEmpty();
        }

        return $kontrak->arsipDokumen()
            ->where('jenis_dokumen', 'SPK_FINAL_TTD')
            ->where('is_active', true)
            ->exists();
    }

    private function buildImageDataUriFromArsip(?ArsipDokumen $arsip): ?string
    {
        if (!$arsip || !$arsip->path_file) {
            return null;
        }

        $disk = $arsip->disk ?: 'public';
        $storage = Storage::disk($disk);

        if (!$storage->exists($arsip->path_file)) {
            return null;
        }

        $fullPath = $storage->path($arsip->path_file);
        $mimeType = function_exists('mime_content_type') ? mime_content_type($fullPath) : null;
        $mimeType = $mimeType ?: match (strtolower(pathinfo($arsip->path_file, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($fullPath));
    }

    private function buildTerminScheme(Request $request, float $nilaiTotalKontrak): array
    {
        $tolerance = 0.01;
        $gunakanRetensi = $request->boolean('gunakan_retensi');
        $progressPersentase = collect($request->input('progress_persentase', []))
            ->map(fn ($value) => (float) $value)
            ->values();
        $progressKeterangan = collect($request->input('progress_keterangan', []))->values();
        $retensiPersentase = $gunakanRetensi ? (float) $request->input('retensi_persentase', 0) : 0;
        $retensiKeterangan = $gunakanRetensi ? trim((string) $request->input('retensi_keterangan', '')) : '';

        $progressRows = [];
        foreach ($progressPersentase as $index => $persentase) {
            if ($persentase <= 0) {
                continue;
            }

            $keterangan = trim((string) ($progressKeterangan[$index] ?? ''));
            $progressRows[] = [
                'persentase' => $persentase,
                'keterangan_termin' => $keterangan !== '' ? $keterangan : 'Termin Progress ' . (count($progressRows) + 1),
            ];
        }

        if (count($progressRows) === 0) {
            throw new \Exception('Minimal harus ada 1 termin progress jika metode pembayaran TERMIN.');
        }

        if ($gunakanRetensi && $retensiPersentase <= 0) {
            throw new \Exception('Retensi wajib diisi untuk kontrak dengan metode pembayaran TERMIN.');
        }

        $totalProgressPersentase = collect($progressRows)->sum('persentase');
        $totalProgressDanRetensi = $totalProgressPersentase + $retensiPersentase;

        if ($totalProgressDanRetensi > (100 + $tolerance)) {
            throw new \Exception($gunakanRetensi
                ? 'Total persentase progress dan retensi tidak boleh melebihi 100%.'
                : 'Total persentase progress tidak boleh melebihi 100%.');
        }

        $persentasePelunasan = round(100 - $totalProgressDanRetensi, 4);
        if ($persentasePelunasan < -$tolerance) {
            throw new \Exception('Urutan termin tidak valid. Total progress dan retensi menghasilkan pelunasan negatif.');
        }

        if (abs($persentasePelunasan) <= $tolerance) {
            $persentasePelunasan = 0;
        }

        $rows = [];
        foreach ($progressRows as $row) {
            $rows[] = [
                'jenis_termin' => 'PROGRESS',
                'keterangan_termin' => $row['keterangan_termin'],
                'persentase' => round($row['persentase'], 4),
                'nilai_bruto_termin' => $this->calculateTermValue($nilaiTotalKontrak, $row['persentase']),
            ];
        }

        $rows[] = [
            'jenis_termin' => 'PELUNASAN',
            'keterangan_termin' => 'Pelunasan',
            'persentase' => $persentasePelunasan,
            'nilai_bruto_termin' => $this->calculateTermValue($nilaiTotalKontrak, $persentasePelunasan),
        ];

        if ($gunakanRetensi) {
            $rows[] = [
                'jenis_termin' => 'RETENSI',
                'keterangan_termin' => $retensiKeterangan !== '' ? $retensiKeterangan : 'Retensi',
                'persentase' => round($retensiPersentase, 4),
                'nilai_bruto_termin' => $this->calculateTermValue($nilaiTotalKontrak, $retensiPersentase),
            ];
        }

        $totalPersentase = collect($rows)->sum('persentase');
        if (abs($totalPersentase - 100) > $tolerance) {
            throw new \Exception($gunakanRetensi
                ? 'Total persentase termin harus 100% setelah sistem membentuk progress, pelunasan, dan retensi.'
                : 'Total persentase termin harus 100% setelah sistem membentuk progress dan pelunasan.');
        }

        return $rows;
    }

    private function calculateTermValue(float $nilaiTotalKontrak, float $persentase): float
    {
        return round(($persentase / 100) * $nilaiTotalKontrak, 2);
    }

    private function attachAngsuranUangMuka(array $rows, float $nilaiUangMuka): array
    {
        if ($nilaiUangMuka <= 0) {
            return array_map(function (array $row) {
                $row['potongan_angsuran_uang_muka'] = 0;
                return $row;
            }, $rows);
        }

        $eligibleIndexes = [];
        $eligibleTotal = 0;

        foreach ($rows as $index => $row) {
            if (in_array($row['jenis_termin'], ['PROGRESS', 'PELUNASAN'], true) && (float) $row['nilai_bruto_termin'] > 0) {
                $eligibleIndexes[] = $index;
                $eligibleTotal += (float) $row['nilai_bruto_termin'];
            }
        }

        if ($eligibleTotal <= 0 || count($eligibleIndexes) === 0) {
            return array_map(function (array $row) {
                $row['potongan_angsuran_uang_muka'] = 0;
                return $row;
            }, $rows);
        }

        $remaining = round($nilaiUangMuka, 2);

        foreach ($rows as $index => &$row) {
            $row['potongan_angsuran_uang_muka'] = 0;

            if (!in_array($index, $eligibleIndexes, true)) {
                continue;
            }

            $isLastEligible = $index === end($eligibleIndexes);
            if ($isLastEligible) {
                $row['potongan_angsuran_uang_muka'] = max(0, round($remaining, 2));
                continue;
            }

            $allocation = round(($row['nilai_bruto_termin'] / $eligibleTotal) * $nilaiUangMuka, 2);
            $allocation = min($allocation, $remaining);
            $row['potongan_angsuran_uang_muka'] = $allocation;
            $remaining = round($remaining - $allocation, 2);
        }
        unset($row);

        return $rows;
    }

    private function resolvePpkUser(int $userId): User
    {
        $user = User::role('PPK')->with('profilable')->findOrFail($userId);

        if (!$user->pegawai || empty($user->pegawai->nip)) {
            throw new \RuntimeException('User PPK yang dipilih belum memiliki data pegawai atau NIP yang lengkap.');
        }

        return $user;
    }

    private function ensureAssignedPpk(\App\Models\KontrakPengadaan $kontrak): void
    {
        if (!Auth::user()?->hasRole('PPK')) {
            return;
        }

        if ((int) $kontrak->ppk_user_id !== (int) Auth::id()) {
            abort(403, 'Kontrak ini tidak ditugaskan kepada Anda untuk diverifikasi.');
        }
    }

    private function notifyRoles(array $roles, string $judul, string $pesan, ?string $linkUrl = null): void
    {
        Notification::send(User::role($roles)->get(), new WorkflowNotification([
            'title' => $judul,
            'message' => $pesan,
            'url' => $linkUrl,
            'icon' => 'notifications',
            'color' => 'primary',
        ]));
    }
}
