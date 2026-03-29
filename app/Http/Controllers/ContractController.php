<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Supplier;
use App\Models\Budget;
use App\Models\User;
use App\Models\ContractTerm;
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
        $contracts = Contract::with(['supplier', 'budget', 'addendums', 'transactions' => function($q) {
            $q->where('status', 'Paid SP2D');
        }])->latest()->get();

        $totalAktif = $contracts->where('status', 'Aktif')->count();
        $totalSelesai = $contracts->whereIn('status', ['Selesai'])->count();
        $totalAdendum = $contracts->filter(function($c) { return $c->addendums->count() > 0; })->count();
        $totalNilaiAll = $contracts->sum('total_amount');
        
        $hampirHabisNilai = 0;
        $hampirHabisMasa = 0;

        foreach ($contracts as $c) {
            $realisasi = $c->transactions->sum('gross_amount') ?? 0;
            $c->realisasi_pembayaran = $realisasi;
            $c->sisa_kontrak = $c->total_amount - $realisasi;

            if ($c->status == 'Aktif') {
                if ($c->total_amount > 0 && $c->sisa_kontrak <= ($c->total_amount * 0.2)) {
                    $hampirHabisNilai++;
                }
                if ($c->end_date && \Carbon\Carbon::parse($c->end_date)->isBetween(now(), now()->addDays(30))) {
                    $hampirHabisMasa++;
                }
            }
        }

        return view('contracts.index', compact(
            'contracts', 'totalAktif', 'totalSelesai', 'totalAdendum', 
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
    public function create()
    {
        $suppliers = Supplier::all();
        $budgets = Budget::all();
        
        // Fetch PPK and Pejabat Pengadaan
        $ppk_users = User::role('PPK')->get();
        $pengadaan_users = User::role('Pejabat Pengadaan')->get();
        
        // Generate Transaction ID
        $latest = Contract::latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        $idTransaksi = 'TRX-KONTRAK-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return view('contracts.create', compact('suppliers', 'budgets', 'ppk_users', 'pengadaan_users', 'idTransaksi'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_number' => 'required|string|max:255|unique:contracts',
            'date' => 'required|date',
            'description' => 'required|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'budget_id' => 'required|exists:budgets,id',
            'total_amount' => 'required|numeric|min:0',
            'mata_uang' => 'required|string',
            'cara_bayar' => 'required|string',
            'tahun_anggaran' => 'required|integer',
            'ppk_id' => 'required|exists:users,id',
            'pejabat_pengadaan_id' => 'nullable|exists:users,id',
            'id_transaksi' => 'required|string',
            'nomor_spk_sp' => 'nullable|string',
            'ketentuan_sanksi' => 'nullable|string',
            
            // Waktu Pekerjaan
            'jangka_waktu_pekerjaan' => 'required|integer|min:1',
            'satuan_waktu_pekerjaan' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            
            // Pemeliharaan
            'ada_masa_pemeliharaan' => 'nullable|boolean',
            'jangka_waktu_pemeliharaan' => 'nullable|integer',
            'tanggal_mulai_pemeliharaan' => 'nullable|date',
            'tanggal_selesai_pemeliharaan' => 'nullable|date',
            
            // Addendum Base (Opsional)
            'ada_addendum' => 'nullable|boolean',
            
            // Jaminan UM
            'ada_jaminan_um' => 'nullable|boolean',
            'penjamin_um' => 'nullable|string',
            'nomor_jaminan_um' => 'nullable|string',
            'tanggal_jaminan_um' => 'nullable|date',
            'masa_berlaku_jaminan' => 'nullable|integer',
            'tanggal_mulai_jaminan' => 'nullable|date',
            'tanggal_selesai_jaminan' => 'nullable|date',

            // Uang Muka
            'ada_uang_muka' => 'nullable|boolean',
            'nilai_uang_muka' => 'nullable|numeric',
            'persentase_uang_muka' => 'nullable|numeric',
            'jumlah_angsuran_um' => 'nullable|integer',
            'jumlah_termin' => 'required|integer|min:1',

            // Array Termins // Only required if cara_bayar is termin, but our JS disables inputs, so it might send empty. For safety, let's keep it required but ensure JS sends.
            'termins' => 'required|array',
            
            // Array UM
            'angsuran_ums' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $status = $request->has('simpan_draft') ? 'Draft' : 'Menunggu PPK';

            $contract = Contract::create([
                'id_transaksi' => $validated['id_transaksi'],
                'contract_number' => $validated['contract_number'],
                'nomor_spk_sp' => $validated['nomor_spk_sp'] ?? null,
                'date' => $validated['date'],
                'description' => $validated['description'],
                'ketentuan_sanksi' => $validated['ketentuan_sanksi'] ?? null,
                'supplier_id' => $validated['supplier_id'],
                'budget_id' => $validated['budget_id'],
                'total_amount' => $validated['total_amount'],
                'mata_uang' => $validated['mata_uang'],
                'cara_bayar' => $validated['cara_bayar'],
                'tahun_anggaran' => $validated['tahun_anggaran'],
                'ppk_id' => $validated['ppk_id'],
                'pejabat_pengadaan_id' => $validated['pejabat_pengadaan_id'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'jangka_waktu_pekerjaan' => $validated['jangka_waktu_pekerjaan'],
                'satuan_waktu_pekerjaan' => $validated['satuan_waktu_pekerjaan'],
                'ada_masa_pemeliharaan' => $request->has('ada_masa_pemeliharaan') ? 1 : 0,
                'jangka_waktu_pemeliharaan' => $validated['jangka_waktu_pemeliharaan'] ?? null,
                'tanggal_mulai_pemeliharaan' => $validated['tanggal_mulai_pemeliharaan'] ?? null,
                'tanggal_selesai_pemeliharaan' => $validated['tanggal_selesai_pemeliharaan'] ?? null,
                'jumlah_termin' => $validated['jumlah_termin'],
                'ada_uang_muka' => $request->has('ada_uang_muka') ? 1 : 0,
                'nilai_uang_muka' => $validated['nilai_uang_muka'] ?? null,
                'persentase_uang_muka' => $validated['persentase_uang_muka'] ?? null,
                'jumlah_angsuran_um' => $validated['jumlah_angsuran_um'] ?? null,
                'penjamin_um' => $validated['penjamin_um'] ?? null,
                'nomor_jaminan_um' => $validated['nomor_jaminan_um'] ?? null,
                'tanggal_jaminan_um' => $validated['tanggal_jaminan_um'] ?? null,
                'masa_berlaku_jaminan' => $validated['masa_berlaku_jaminan'] ?? null,
                'tanggal_mulai_jaminan' => $validated['tanggal_mulai_jaminan'] ?? null,
                'tanggal_selesai_jaminan' => $validated['tanggal_selesai_jaminan'] ?? null,
                'status' => $status,
                'submitted_by' => Auth::id(),
                'type' => 'Jasa', // Default, but can be updated or extracted from form if needed
            ]);

            // Save Termins
            if (isset($validated['termins'])) {
                foreach ($validated['termins'] as $termin) {
                    ContractTerm::create([
                        'contract_id' => $contract->id,
                        'type' => 'Termin',
                        'term_name' => $termin['keterangan'] ?? 'Termin',
                        'percentage' => $termin['persentase'] ?? 0,
                        'amount' => $termin['nilai'] ?? 0,
                        'target_date' => $termin['target_date'] ?? null,
                        'status' => 'Pending'
                    ]);
                }
            }

            // Save Uang Muka if exists
            if ($request->has('ada_uang_muka') && isset($validated['angsuran_ums'])) {
                foreach ($validated['angsuran_ums'] as $angsuran) {
                    ContractTerm::create([
                        'contract_id' => $contract->id,
                        'type' => 'Uang Muka',
                        'term_name' => $angsuran['keterangan'] ?? 'Potongan Uang Muka',
                        'percentage' => 0, // Um usually deducted, distinct metric
                        'amount' => $angsuran['nilai'] ?? 0,
                        'status' => 'Pending'
                    ]);
                }
            }

            // (Optional) addendum base if needed in the future, skipping addendum creation on `create`

            // Log approval if submitted directly
            if ($status === 'Menunggu PPK') {
                ApprovalLog::create([
                    'contract_id'  => $contract->id,
                    'user_id'      => Auth::id(),
                    'role_name'    => Auth::user()->getRoleNames()->first() ?? '-',
                    'status_from'  => null,
                    'status_to'    => 'Menunggu PPK',
                    'notes'        => 'Kontrak diajukan untuk persetujuan PPK.',
                ]);
            }

            DB::commit();

            return redirect()->route('contracts.index')->with('success', 'Kontrak berhasil ' . ($status === 'Draft' ? 'disimpan sebagai draft.' : 'diajukan ke PPK.'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan kontrak: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Contract $contract)
    {
        $contract->load(['supplier', 'budget', 'addendums', 'terms', 'transactions', 'approvalLogs.user', 'submittedBy']);
        return view('contracts.show', compact('contract'));
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
