<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ArsipDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contracts = \App\Models\KontrakPengadaan::with(['vendor', 'addendums', 'termin'])->latest()->get();
        $addendums = \App\Models\KontrakAddendum::with(['kontrakUtama.vendor'])->latest()->get();

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
        $query = \App\Models\KontrakPengadaan::with(['vendor', 'dipa'])->latest();
        
        $filter = $request->input('status', 'ALL');
        
        if ($filter === 'PENDING_REVIEW') {
            $query->where('status_kontrak', 'PENDING_REVIEW');
        } elseif ($filter === 'DRAFT') {
            $query->where('status_kontrak', 'DRAFT');
        } elseif ($filter === 'ALL') {
            $query->whereIn('status_kontrak', ['PENDING_REVIEW', 'DRAFT']); 
        }

        $contracts = $query->get();
        return view('contracts.verifikasi_index', compact('contracts', 'filter'));
    }

    /**
     * Display the specified draft contract for PPK verification (The Decision Room).
     */
    public function verifikasiShow($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with(['vendor.rekening', 'dipa', 'termin'])->findOrFail($id);
        
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
        // Hanya DIPA tahun berjalan
        $dipas = \App\Models\MasterDipa::where('tahun_anggaran', date('Y'))->get();

        return view('contracts.create', compact('vendors', 'dipas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:master_pihak,id',
            'master_dipa_id' => 'required|exists:master_dipas,id',
            'nama_pekerjaan' => 'required|string',
            'nomor_spk' => 'required|string|unique:kontrak_pengadaan,nomor_spk',
            'tanggal_spk' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'satuan_waktu' => 'required|in:HARI,MINGGU,BULAN',
            'jangka_waktu' => 'required|integer|min:1',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'nilai_total_kontrak' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:LUMPSUM,TERMIN',
            'ada_uang_muka' => 'nullable|boolean',
            'nilai_uang_muka' => 'nullable|numeric|min:0|lte:nilai_total_kontrak',

            // Uploads
            'file_spk' => 'nullable|file|mimes:pdf|max:5120',
            'file_spmk' => 'nullable|file|mimes:pdf|max:5120',
            'file_ringkasan_kontrak' => 'nullable|file|mimes:pdf|max:5120',
            'file_jaminan_um' => 'nullable|file|mimes:pdf|max:5120',
            
            // Termin progress + retensi
            'progress_keterangan' => 'nullable|array',
            'progress_keterangan.*' => 'nullable|string|max:255',
            'progress_persentase' => 'nullable|array',
            'progress_persentase.*' => 'nullable|numeric|min:0.0001|max:100',
            'retensi_keterangan' => 'nullable|string|max:255',
            'retensi_persentase' => 'nullable|numeric|min:0.0001|max:100',
        ]);

        try {
            DB::beginTransaction();

            $dipa = \App\Models\MasterDipa::findOrFail($validated['master_dipa_id']);
            
            // Cek sisa pagu DIPA
            $terpakai = \App\Models\KontrakPengadaan::where('master_dipa_id', $dipa->id)
                ->where('status_kontrak', '!=', 'DIBATALKAN')
                ->sum('nilai_total_kontrak');
            $sisaPagu = $dipa->total_pagu - $terpakai;

            if ($validated['nilai_total_kontrak'] > $sisaPagu) {
                return back()->withInput()->withErrors([
                    'error' => "Gagal disimpan: Nilai Kontrak (Rp " . number_format($validated['nilai_total_kontrak'],0,',','.') . 
                               ") melebihi sisa pagu anggaran DIPA yang tersedia (Rp " . number_format($sisaPagu,0,',','.') . ")."
                ]);
            }

            // Clean format Rupiah to numeric (already done by validate if input is right? No, standard HTML <input> gives the string, wait, frontend JS `oninput` copies the clean value to hidden inputs `nilai_total_kontrak_value` and `nilai_uang_muka_value` so laravel receives clean numerics).

            // Upload files
            $pathSpk = $request->hasFile('file_spk') ? $request->file('file_spk')->store('kontrak/spk', 'public') : null;
            $pathSpmk = $request->hasFile('file_spmk') ? $request->file('file_spmk')->store('kontrak/spmk', 'public') : null;
            $pathRingkasan = $request->hasFile('file_ringkasan_kontrak') ? $request->file('file_ringkasan_kontrak')->store('kontrak/ringkasan', 'public') : null;
            $pathJaminanUm = $request->hasFile('file_jaminan_um') ? $request->file('file_jaminan_um')->store('kontrak/jaminan-uang-muka', 'public') : null;

            $adaUangMuka = $request->has('ada_uang_muka');
            $nilaiUangMuka = $adaUangMuka ? (float) ($validated['nilai_uang_muka'] ?? 0) : 0;
            $initialSisaUangMuka = $nilaiUangMuka;

            $kontrak = \App\Models\KontrakPengadaan::create([
                'vendor_id' => $validated['vendor_id'],
                'master_dipa_id' => $validated['master_dipa_id'],
                'nomor_spk' => $validated['nomor_spk'],
                'tanggal_spk' => $validated['tanggal_spk'],
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'nilai_total_kontrak' => $validated['nilai_total_kontrak'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'ada_uang_muka' => $adaUangMuka ? 1 : 0,
                'nilai_uang_muka' => $nilaiUangMuka,
                'sisa_uang_muka_belum_lunas' => $initialSisaUangMuka,
                'jangka_waktu' => $validated['jangka_waktu'],
                'satuan_waktu' => $validated['satuan_waktu'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'status_kontrak' => 'DRAFT',
            ]);

            $this->syncKontrakArsip($kontrak, [
                'SPK' => $pathSpk,
                'SPMK' => $pathSpmk,
                'RINGKASAN_KONTRAK' => $pathRingkasan,
                'JAMINAN_UANG_MUKA' => $pathJaminanUm,
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
        $kontrak = \App\Models\KontrakPengadaan::with(['termin.detailKontrak.tagihan.logs.user', 'addendums', 'vendor', 'dipa'])->findOrFail($id);

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
            return [
                'tanggal' => $add->created_at,
                'judul' => 'Addendum ' . $add->nomor_addendum . ' Dibuat',
                'aktor' => 'Sistem',
                'catatan' => $add->keterangan_alasan ?? 'Perubahan kontrak',
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
     * Submit Multiple Draft contracts for PPK approval.
     */
    public function submitBulk(Request $request)
    {
        $request->validate([
            'contract_ids' => 'required|array',
            'contract_ids.*' => 'exists:kontrak_pengadaan,id',
        ]);

        $contracts = \App\Models\KontrakPengadaan::whereIn('id', $request->contract_ids)->where('status_kontrak', 'DRAFT')->get();

        if ($contracts->isEmpty()) {
            return back()->with('error', 'Tidak ada kontrak dengan status DRAFT yang diplih.');
        }

        DB::beginTransaction();
        try {
            foreach ($contracts as $contract) {
                $contract->update([
                    'status_kontrak' => 'PENDING_REVIEW',
                ]);

                \App\Models\LogStatusDokumen::create([
                    'dokumen_type'      => \App\Models\KontrakPengadaan::class,
                    'dokumen_id'        => $contract->id,
                    'user_id'           => Auth::id(),
                    'role_saat_itu'     => Auth::user()->getRoleNames()->first() ?? '-',
                    'status_sebelumnya' => 'DRAFT',
                    'status_baru'       => 'PENDING_REVIEW',
                    'aksi'              => 'SUBMIT_BULK',
                    'catatan'           => 'Kontrak diajukan untuk persetujuan PPK (Bulk).',
                    'ip_address'        => request()->ip(),
                ]);
            }
            DB::commit();
            return back()->with('success', $contracts->count() . ' Kontrak berhasil diajukan ke PPK.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengajukan kontrak: ' . $e->getMessage());
        }
    }

    /**
     * Submit a Draft contract for PPK approval.
     */
    public function submit($id)
    {
        $contract = \App\Models\KontrakPengadaan::findOrFail($id);

        if ($contract->status_kontrak !== 'DRAFT' && $contract->status_kontrak !== 'DRAFT') {
            return back()->with('error', 'Kontrak tidak dapat diajukan dari status saat ini.');
        }

        $oldStatus = $contract->status_kontrak;
        $contract->update([
            'status_kontrak' => 'PENDING_REVIEW',
        ]);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type'      => \App\Models\KontrakPengadaan::class,
            'dokumen_id'        => $contract->id,
            'user_id'           => Auth::id(),
            'role_saat_itu'     => Auth::user()->getRoleNames()->first() ?? '-',
            'status_sebelumnya' => $oldStatus,
            'status_baru'       => 'PENDING_REVIEW',
            'aksi'              => 'SUBMIT',
            'catatan'           => 'Kontrak diajukan untuk persetujuan PPK.',
            'ip_address'        => request()->ip(),
        ]);

        return back()->with('success', 'Kontrak berhasil diajukan ke PPK untuk persetujuan.');
    }

    /**
     * PPK approves a contract.
     */
    public function approve(Request $request, $id)
    {
        $contract = \App\Models\KontrakPengadaan::findOrFail($id);

        if ($contract->status_kontrak !== 'PENDING_REVIEW') {
            return back()->with('error', 'Kontrak tidak dalam status menunggu persetujuan.');
        }

        $contract->update(['status_kontrak' => 'AKTIF']);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type'      => \App\Models\KontrakPengadaan::class,
            'dokumen_id'        => $contract->id,
            'user_id'           => Auth::id(),
            'role_saat_itu'     => 'PPK',
            'status_sebelumnya' => 'PENDING_REVIEW',
            'status_baru'       => 'AKTIF',
            'aksi'              => 'APPROVE',
            'catatan'           => $request->input('notes', 'Kontrak disetujui oleh PPK.'),
            'ip_address'        => request()->ip(),
        ]);

        $this->notifyRoles(
            ['Pejabat Pengadaan'],
            'Kontrak Disetujui PPK',
            "Kontrak {$contract->nomor_spk} telah disetujui PPK dan berstatus AKTIF.",
            route('contracts.show', $contract->id)
        );

        return redirect()
            ->route('contracts.verifikasi')
            ->with('success', "Kontrak {$contract->nomor_spk} berhasil disetujui dan diaktifkan.");
    }

    /**
     * PPK rejects a contract.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['notes' => 'required|string|max:500']);
        $contract = \App\Models\KontrakPengadaan::findOrFail($id);

        if ($contract->status_kontrak !== 'PENDING_REVIEW') {
            return back()->with('error', 'Kontrak tidak dalam status menunggu persetujuan.');
        }

        $contract->update(['status_kontrak' => 'DRAFT']);

        \App\Models\LogStatusDokumen::create([
            'dokumen_type'      => \App\Models\KontrakPengadaan::class,
            'dokumen_id'        => $contract->id,
            'user_id'           => Auth::id(),
            'role_saat_itu'     => 'PPK',
            'status_sebelumnya' => 'PENDING_REVIEW',
            'status_baru'       => 'DRAFT',
            'aksi'              => 'REJECT',
            'catatan'           => $request->input('notes'),
            'ip_address'        => request()->ip(),
        ]);

        $this->notifyRoles(
            ['Pejabat Pengadaan'],
            'Kontrak Dikembalikan PPK',
            "Kontrak {$contract->nomor_spk} dikembalikan PPK untuk revisi: {$request->input('notes')}",
            route('contracts.edit', $contract->id)
        );

        return redirect()
            ->route('contracts.verifikasi')
            ->with('success', "Kontrak {$contract->nomor_spk} berhasil dikembalikan untuk revisi.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $kontrak = \App\Models\KontrakPengadaan::with('termin')->findOrFail($id);
        
        // Prevent editing if not DRAFT or DRAFT
        if (!in_array($kontrak->status_kontrak, ['DRAFT', 'DRAFT'])) {
            return redirect()->route('contracts.index')->with('error', 'Kontrak tidak dapat diubah karena sudah diajukan atau aktif.');
        }

        $vendors = \App\Models\MasterPihak::query()
            ->where('status_aktif', true)
            ->whereIn('kategori', ['PENGELUARAN', 'KEDUANYA'])
            ->orderBy('nama_pihak')
            ->get();
        $dipas = \App\Models\MasterDipa::where('tahun_anggaran', date('Y'))->get();

        return view('contracts.edit', compact('kontrak', 'vendors', 'dipas'));
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

        $validated = $request->validate([
            'vendor_id' => 'required|exists:master_pihak,id',
            'master_dipa_id' => 'required|exists:master_dipas,id',
            'nama_pekerjaan' => 'required|string',
            'nomor_spk' => 'required|string|unique:kontrak_pengadaan,nomor_spk,' . $kontrak->id,
            'tanggal_spk' => 'required|date',
            'tanggal_mulai' => 'required|date',
            'satuan_waktu' => 'required|in:HARI,MINGGU,BULAN',
            'jangka_waktu' => 'required|integer|min:1',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'nilai_total_kontrak' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:LUMPSUM,TERMIN',
            'ada_uang_muka' => 'nullable|boolean',
            'nilai_uang_muka' => 'nullable|numeric|min:0|lte:nilai_total_kontrak',
            
            // Uploads
            'file_spk' => 'nullable|file|mimes:pdf|max:5120',
            'file_spmk' => 'nullable|file|mimes:pdf|max:5120',
            'file_ringkasan_kontrak' => 'nullable|file|mimes:pdf|max:5120',
            'file_jaminan_um' => 'nullable|file|mimes:pdf|max:5120',
            
            // Termin progress + retensi
            'progress_keterangan' => 'nullable|array',
            'progress_keterangan.*' => 'nullable|string|max:255',
            'progress_persentase' => 'nullable|array',
            'progress_persentase.*' => 'nullable|numeric|min:0.0001|max:100',
            'retensi_keterangan' => 'nullable|string|max:255',
            'retensi_persentase' => 'nullable|numeric|min:0.0001|max:100',
        ]);

        try {
            DB::beginTransaction();

            $dipa = \App\Models\MasterDipa::findOrFail($validated['master_dipa_id']);
            
            // Cek sisa pagu DIPA (exclude this contract itself)
            $terpakai = \App\Models\KontrakPengadaan::where('master_dipa_id', $dipa->id)
                ->whereNotIn('status_kontrak', ['DIBATALKAN', 'DRAFT', 'DRAFT'])
                ->where('id', '!=', $kontrak->id)
                ->sum('nilai_total_kontrak');
            $sisaPagu = $dipa->total_pagu - $terpakai;

            if ($validated['nilai_total_kontrak'] > $sisaPagu) {
                return back()->withInput()->withErrors([
                    'error' => "Gagal disimpan: Nilai Kontrak (Rp " . number_format($validated['nilai_total_kontrak'],0,',','.') . 
                               ") melebihi sisa pagu anggaran DIPA yang tersedia (Rp " . number_format($sisaPagu,0,',','.') . ")."
                ]);
            }

            // Upload files (delete old ones if new uploaded)
            if ($request->hasFile('file_spk')) {
                if ($kontrak->file_spk) \Illuminate\Support\Facades\Storage::disk('public')->delete($kontrak->file_spk);
                $validated['file_spk'] = $request->file('file_spk')->store('kontrak/spk', 'public');
            } else {
                $validated['file_spk'] = $kontrak->file_spk;
            }

            if ($request->hasFile('file_spmk')) {
                if ($kontrak->file_spmk) \Illuminate\Support\Facades\Storage::disk('public')->delete($kontrak->file_spmk);
                $validated['file_spmk'] = $request->file('file_spmk')->store('kontrak/spmk', 'public');
            } else {
                $validated['file_spmk'] = $kontrak->file_spmk;
            }

            if ($request->hasFile('file_ringkasan_kontrak')) {
                if ($kontrak->file_ringkasan_kontrak) \Illuminate\Support\Facades\Storage::disk('public')->delete($kontrak->file_ringkasan_kontrak);
                $validated['file_ringkasan_kontrak'] = $request->file('file_ringkasan_kontrak')->store('kontrak/ringkasan', 'public');
            } else {
                $validated['file_ringkasan_kontrak'] = $kontrak->file_ringkasan_kontrak;
            }

            if ($request->hasFile('file_jaminan_um')) {
                if ($kontrak->file_jaminan_uang_muka) \Illuminate\Support\Facades\Storage::disk('public')->delete($kontrak->file_jaminan_uang_muka);
                $validated['file_jaminan_um'] = $request->file('file_jaminan_um')->store('kontrak/jaminan-uang-muka', 'public');
            } else {
                $validated['file_jaminan_um'] = $kontrak->file_jaminan_uang_muka;
            }

            $ada_uang_muka = $request->has('ada_uang_muka') ? 1 : 0;
            $nilai_uang_muka = $ada_uang_muka ? (float) ($validated['nilai_uang_muka'] ?? 0) : 0;

            $kontrak->update([
                'vendor_id' => $validated['vendor_id'],
                'master_dipa_id' => $validated['master_dipa_id'],
                'nomor_spk' => $validated['nomor_spk'],
                'tanggal_spk' => $validated['tanggal_spk'],
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'nilai_total_kontrak' => $validated['nilai_total_kontrak'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'ada_uang_muka' => $ada_uang_muka,
                'nilai_uang_muka' => $nilai_uang_muka,
                'sisa_uang_muka_belum_lunas' => $nilai_uang_muka,
                'jangka_waktu' => $validated['jangka_waktu'],
                'satuan_waktu' => $validated['satuan_waktu'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
            ]);

            if (!$ada_uang_muka) {
                $existingJaminanUm = $kontrak->arsipDokumen()->where('jenis_dokumen', 'JAMINAN_UANG_MUKA')->first();
                if ($existingJaminanUm) {
                    \Illuminate\Support\Facades\Storage::disk($existingJaminanUm->disk ?? 'public')->delete($existingJaminanUm->path_file);
                    $existingJaminanUm->delete();
                }
            }

            $this->syncKontrakArsip($kontrak, [
                'SPK' => $validated['file_spk'],
                'SPMK' => $validated['file_spmk'],
                'RINGKASAN_KONTRAK' => $validated['file_ringkasan_kontrak'],
                'JAMINAN_UANG_MUKA' => $ada_uang_muka ? $validated['file_jaminan_um'] : null,
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

    private function buildTerminScheme(Request $request, float $nilaiTotalKontrak): array
    {
        $tolerance = 0.01;
        $progressPersentase = collect($request->input('progress_persentase', []))
            ->map(fn ($value) => (float) $value)
            ->values();
        $progressKeterangan = collect($request->input('progress_keterangan', []))->values();
        $retensiPersentase = (float) $request->input('retensi_persentase', 0);
        $retensiKeterangan = trim((string) $request->input('retensi_keterangan', ''));

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

        if ($retensiPersentase <= 0) {
            throw new \Exception('Retensi wajib diisi untuk kontrak dengan metode pembayaran TERMIN.');
        }

        $totalProgressPersentase = collect($progressRows)->sum('persentase');
        $totalProgressDanRetensi = $totalProgressPersentase + $retensiPersentase;

        if ($totalProgressDanRetensi > (100 + $tolerance)) {
            throw new \Exception('Total persentase progress dan retensi tidak boleh melebihi 100%.');
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

        $rows[] = [
            'jenis_termin' => 'RETENSI',
            'keterangan_termin' => $retensiKeterangan !== '' ? $retensiKeterangan : 'Retensi',
            'persentase' => round($retensiPersentase, 4),
            'nilai_bruto_termin' => $this->calculateTermValue($nilaiTotalKontrak, $retensiPersentase),
        ];

        $totalPersentase = collect($rows)->sum('persentase');
        if (abs($totalPersentase - 100) > $tolerance) {
            throw new \Exception('Total persentase termin harus 100% setelah sistem membentuk progress, pelunasan, dan retensi.');
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


