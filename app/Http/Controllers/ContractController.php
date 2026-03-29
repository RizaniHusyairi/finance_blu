<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Supplier;
use App\Models\Budget;
use App\Models\User;
use App\Models\ContractTerm;
use App\Models\ContractDocument;
use App\Models\ApprovalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contracts = \App\Models\KontrakPengadaan::with(['vendor', 'addendums'])->latest()->get();
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
     * Display a listing of the contracts for PPK verification.
     */
    public function verifikasiIndex()
    {
        $contractsMenunggu = Contract::with(['supplier', 'addendums'])->where('status', 'Menunggu PPK')->latest()->get();
        // Also get contracts already approved/rejected by PPK
        $contractsRiwayat = Contract::with(['supplier', 'addendums'])->whereIn('status', ['Aktif', 'Ditolak PPK', 'Selesai'])->latest()->get();
        
        // Count for widgets
        $totalMenunggu = $contractsMenunggu->count();
        $totalDisetujui = $contractsRiwayat->where('status', 'Aktif')->count();
        $totalDitolak = $contractsRiwayat->where('status', 'Ditolak PPK')->count();

        // Also get Addendums waiting for approval directly if we want to show them? Actually let's just use the contracts collection 
        // Or we can query addendums separately. For now, let's keep it simple.
        $addendumsMenunggu = \App\Models\ContractAddendum::with('contract')->where('status', 'Menunggu PPK')->latest()->get();
        $totalAddendumMenunggu = $addendumsMenunggu->count();

        return view('contracts.verifikasi_index', compact(
            'contractsMenunggu', 'contractsRiwayat', 'totalMenunggu', 'totalDisetujui', 
            'totalDitolak', 'addendumsMenunggu', 'totalAddendumMenunggu'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = \App\Models\MasterMitraVendor::all();
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
            'vendor_id' => 'required|exists:master_mitra_vendor,id',
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
            'nilai_uang_muka' => 'nullable|numeric|min:0',
            
            // Uploads
            'file_spk' => 'nullable|file|mimes:pdf|max:5120',
            'file_spmk' => 'nullable|file|mimes:pdf|max:5120',
            'file_ringkasan_kontrak' => 'nullable|file|mimes:pdf|max:5120',
            
            // Termin
            'termin_jenis' => 'nullable|array',
            'termin_keterangan' => 'nullable|array',
            'termin_persentase' => 'nullable|array',
            'termin_nilai' => 'nullable|array',
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

            $kontrak = \App\Models\KontrakPengadaan::create([
                'vendor_id' => $validated['vendor_id'],
                'master_dipa_id' => $validated['master_dipa_id'],
                'nomor_spk' => $validated['nomor_spk'],
                'tanggal_spk' => $validated['tanggal_spk'],
                'nama_pekerjaan' => $validated['nama_pekerjaan'],
                'nilai_total_kontrak' => $validated['nilai_total_kontrak'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'ada_uang_muka' => $request->has('ada_uang_muka') ? 1 : 0,
                'nilai_uang_muka' => $request->has('ada_uang_muka') ? ($validated['nilai_uang_muka'] ?? 0) : 0,
                'sisa_uang_muka_belum_lunas' => $request->has('ada_uang_muka') ? ($validated['nilai_uang_muka'] ?? 0) : 0,
                'jangka_waktu' => $validated['jangka_waktu'],
                'satuan_waktu' => $validated['satuan_waktu'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'status_kontrak' => 'AKTIF',
                
                'file_spk' => $pathSpk,
                'file_spmk' => $pathSpmk,
                'file_ringkasan_kontrak' => $pathRingkasan,
            ]);

            // Buat Termin Pembayaran
            if ($validated['metode_pembayaran'] === 'TERMIN') {
                $jns = $request->input('termin_jenis', []);
                $ket = $request->input('termin_keterangan', []);
                $prs = $request->input('termin_persentase', []);
                $nl  = $request->input('termin_nilai', []);
                
                $totalPersenCheck = array_sum($prs);
                if ($totalPersenCheck != 100) {
                    throw new \Exception("Total Persentase Termin harus tepat 100% (Saat ini $totalPersenCheck%).");
                }

                foreach ($jns as $index => $jenis) {
                    \App\Models\KontrakTermin::create([
                        'kontrak_pengadaan_id' => $kontrak->id,
                        'termin_ke' => $index + 1,
                        'keterangan_termin' => $ket[$index] ?? '-',
                        'persentase' => $prs[$index],
                        'nilai_bruto_termin' => $nl[$index],
                        'jenis_termin' => $jenis,
                        'status_termin' => 'READY_TO_BILL' // First termin is ready
                    ]);
                }
                
                // Set status uang muka jika ada
                if ($request->has('ada_uang_muka')) {
                    $terminUm = \App\Models\KontrakTermin::where('kontrak_pengadaan_id', $kontrak->id)
                        ->where('jenis_termin', 'UANG_MUKA')->first();
                    if (!$terminUm) {
                        throw new \Exception("Anda menyatakan ada uang muka, tetapi tidak ada skema termin berjenis 'Uang Muka'.");
                    }
                }

                // Lock termin selanjutnya (termin > 1) sementara
                \App\Models\KontrakTermin::where('kontrak_pengadaan_id', $kontrak->id)
                    ->where('termin_ke', '>', 1)
                    ->update(['status_termin' => 'LOCKED']);
            } else {
                // LUMPSUM
                \App\Models\KontrakTermin::create([
                    'kontrak_pengadaan_id' => $kontrak->id,
                    'termin_ke' => 1,
                    'keterangan_termin' => 'Pelunasan Sekaligus (LUMPSUM)',
                    'persentase' => 100,
                    'nilai_bruto_termin' => $validated['nilai_total_kontrak'],
                    'jenis_termin' => 'PELUNASAN',
                    'status_termin' => 'READY_TO_BILL'
                ]);
            }

            DB::commit();

            return redirect()->route('contracts.index')->with('success', 'Kontrak Pengadaan berhasil dibuat (Status: AKTIF).');
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
     * Submit a Draft contract for PPK approval.
     */
    public function submit(Contract $contract)
    {
        if ($contract->status !== 'Draft' && $contract->status !== 'Revisi' && $contract->status !== 'Ditolak PPK') {
            return back()->with('error', 'Kontrak tidak dapat diajukan dari status saat ini.');
        }

        $oldStatus = $contract->status;
        $contract->update([
            'status'       => 'Menunggu PPK',
            'submitted_by' => Auth::id(),
        ]);

        ApprovalLog::create([
            'contract_id'  => $contract->id,
            'user_id'      => Auth::id(),
            'role_name'    => Auth::user()->getRoleNames()->first() ?? '-',
            'status_from'  => $oldStatus,
            'status_to'    => 'Menunggu PPK',
            'notes'        => 'Kontrak diajukan untuk persetujuan PPK.',
        ]);

        return back()->with('success', 'Kontrak berhasil diajukan ke PPK untuk persetujuan.');
    }

    /**
     * PPK approves a contract.
     */
    public function approve(Request $request, Contract $contract)
    {
        if ($contract->status !== 'Menunggu PPK') {
            return back()->with('error', 'Kontrak tidak dalam status menunggu persetujuan.');
        }

        $contract->update(['status' => 'Aktif']);

        ApprovalLog::create([
            'contract_id'  => $contract->id,
            'user_id'      => Auth::id(),
            'role_name'    => 'PPK',
            'status_from'  => 'Menunggu PPK',
            'status_to'    => 'Aktif',
            'notes'        => $request->input('notes', 'Kontrak disetujui oleh PPK.'),
        ]);

        return back()->with('success', 'Kontrak berhasil disetujui.');
    }

    /**
     * PPK rejects a contract.
     */
    public function reject(Request $request, Contract $contract)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        if ($contract->status !== 'Menunggu PPK') {
            return back()->with('error', 'Kontrak tidak dalam status menunggu persetujuan.');
        }

        $contract->update(['status' => 'Ditolak PPK']);

        ApprovalLog::create([
            'contract_id'  => $contract->id,
            'user_id'      => Auth::id(),
            'role_name'    => 'PPK',
            'status_from'  => 'Menunggu PPK',
            'status_to'    => 'Ditolak PPK',
            'notes'        => $request->input('notes'),
        ]);

        return back()->with('success', 'Kontrak ditolak.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contract $contract)
    {
        $suppliers = Supplier::all();
        $budgets = Budget::all();
        return view('contracts.edit', compact('contract', 'suppliers', 'budgets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contract $contract)
    {
         $validated = $request->validate([
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number,' . $contract->id,
            'date' => 'required|date',
            'description' => 'required|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'budget_id' => 'required|exists:budgets,id',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string',
            'ketentuan_sanksi' => 'nullable|string',
        ]);

        $contract->update($validated);

        return redirect()->route('contracts.index')->with('success', 'Contract updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Contract deleted successfully.');
    }
}
