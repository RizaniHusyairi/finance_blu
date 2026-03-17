<?php

namespace App\Http\Controllers;

use App\Models\BluPaymentSubmission;
use App\Models\Contract;
use App\Models\Budget;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BluPaymentSubmissionController extends Controller
{
    // ============================
    // Database-backed CRUD methods
    // ============================

    public function create()
    {
        $contracts = Contract::where('status', 'Active')->with('terms')->get();
        $budgets = Budget::all();
        $suppliers = Supplier::orderBy('name')->get();
        $ppks = User::role('PPK')->get();

        // Auto-generate next SPP number: SPP-BLU/APTP-YYYY/NNNN
        $nextSppNumber = $this->generateNextSppNumber();

        return view('blu-payment-submissions.create', compact('contracts', 'budgets', 'suppliers', 'ppks', 'nextSppNumber'));
    }

    /**
     * Generate next SPP number with format: SPP-BLU/APTP-YYYY/NNNN
     */
    private function generateNextSppNumber(): string
    {
        $year = date('Y');
        $prefix = "SPP-BLU/APTP-{$year}/";

        $lastSpp = BluPaymentSubmission::where('spp_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(spp_number, '/', -1) AS UNSIGNED) DESC")
            ->value('spp_number');

        $nextSeq = 1;
        if ($lastSpp) {
            $lastSeq = (int) substr($lastSpp, strrpos($lastSpp, '/') + 1);
            $nextSeq = $lastSeq + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Convert SPP number to SPM number: SPP-BLU/APTP-YYYY/NNNN → SPM-BLU/APTP-YYYY/NNNN
     */
    private function generateSpmFromSpp(string $sppNumber): string
    {
        return str_replace('SPP-BLU', 'SPM-BLU', $sppNumber);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_spp'             => 'required|string|max:255|unique:transactions,spp_number',
            'tanggal_spp'           => 'required|date',
            'jenis_tagihan'         => 'required|string',
            'cara_bayar'            => 'required|string',
            'supplier_id'           => 'required|exists:suppliers,id',
            'uraian'                => 'required|string',
            'jenis_pengajuan'       => 'required|in:Kontrak,Non Kontrak',
            'contract_id'           => 'required_if:jenis_pengajuan,Kontrak|nullable|exists:contracts,id',
            'termin'                => 'nullable|string',
            'jenis_dokumen_dasar'   => 'required|string',
            'nomor_dokumen_dasar'   => 'required|string',
            'tanggal_dokumen_dasar' => 'required|date',
            'budget_id'             => 'required|exists:budgets,id',
            'jumlah_pembayaran'     => 'required|numeric|min:0',
            'ppk_id'                => 'required|exists:users,id',
            'submit_mode'           => 'required|in:draft,submit',
            'potongan'              => 'nullable|array',
            'potongan.*.jenis_potongan' => 'nullable|string',
            'potongan.*.akun_potongan'  => 'nullable|string',
            'potongan.*.jumlah_potongan' => 'nullable|numeric|min:0',
        ]);

        $data = [
            'transaction_number'    => $validated['nomor_spp'],
            'date'                  => $validated['tanggal_spp'],
            'spp_number'            => $validated['nomor_spp'],
            'spp_date'              => $validated['tanggal_spp'],
            'type'                  => $validated['jenis_tagihan'],
            'payment_method'        => $validated['cara_bayar'],
            'supplier_id'           => $validated['supplier_id'],
            'description'           => $validated['uraian'],
            'jenis_pengajuan'       => $validated['jenis_pengajuan'],
            'contract_id'           => $validated['contract_id'] ?? null,
            'term_id'               => null,
            'jenis_dokumen_dasar'   => $validated['jenis_dokumen_dasar'],
            'bast_number'           => $validated['nomor_dokumen_dasar'],
            'bast_date'             => $validated['tanggal_dokumen_dasar'],
            'budget_id'             => $validated['budget_id'],
            'gross_amount'          => $validated['jumlah_pembayaran'],
            'ppk_id'                => $validated['ppk_id'],
            'status'                => $validated['submit_mode'] === 'submit' ? 'Verified' : 'Draft',
        ];

        // Basic neto calculation for initial save
        $data['net_amount'] = $data['gross_amount'];

        $submission = BluPaymentSubmission::create($data);

        // Handle Potongan/Pajak
        if (!empty($validated['potongan'])) {
            foreach ($validated['potongan'] as $p) {
                if (!empty($p['jenis_potongan']) && !empty($p['jumlah_potongan'])) {
                    $submission->taxes()->create([
                        'tax_name'    => $p['jenis_potongan'],
                        'tax_account' => $p['akun_potongan'] ?? null,
                        'amount'      => $p['jumlah_potongan'],
                    ]);
                }
            }
            $submission->syncNetAmount();
        }

        // Add log if submitted
        if ($data['status'] === 'Verified') {
            $submission->approvalLogs()->create([
                'user_id'     => auth()->id(),
                'action'      => 'submit',
                'from_status' => 'Draft',
                'to_status'   => 'Verified',
                'notes'       => 'Berkas diajukan untuk verifikasi SPP oleh Operator.',
            ]);
        }

        return redirect()->route('blu-payment-submissions.show-detail', $submission->transaction_number)
            ->with('success', 'Pengajuan Pembayaran BLU berhasil disimpan.');
    }

    public function showDetail(BluPaymentSubmission $blu_payment_submission)
    {
        $blu_payment_submission->load(['contract', 'budget', 'taxes', 'term', 'approvalLogs.user']);
        return view('blu-payment-submissions.show-detail', ['transaction' => $blu_payment_submission]);
    }

    public function edit(BluPaymentSubmission $blu_payment_submission)
    {
        if (!in_array($blu_payment_submission->status, ['Draft', 'Rejected'])) {
            return redirect()->route('blu-payment-submissions.show-detail', $blu_payment_submission)
                ->with('error', 'Pengajuan yang sedang diproses tidak dapat diubah.');
        }

        $contracts = Contract::where('status', 'Active')->with('terms')->get();
        $budgets = Budget::all();
        $suppliers = Supplier::orderBy('name')->get();
        $ppks = User::role('PPK')->get();

        return view('blu-payment-submissions.edit', [
            'transaction' => $blu_payment_submission, 
            'contracts' => $contracts, 
            'budgets' => $budgets,
            'suppliers' => $suppliers,
            'ppks' => $ppks
        ]);
    }

    public function update(Request $request, BluPaymentSubmission $blu_payment_submission)
    {
        if (!in_array($blu_payment_submission->status, ['Draft', 'Rejected'])) {
            return redirect()->route('blu-payment-submissions.show-detail', $blu_payment_submission)
                ->with('error', 'Pengajuan yang sedang diproses tidak dapat diubah.');
        }

        $validated = $request->validate([
            'transaction_number' => 'required|string|max:255|unique:transactions,transaction_number,' . $blu_payment_submission->id,
            'date' => 'required|date',
            'type' => 'required|in:LS,UP,TUP',
            'description' => 'required|string',
            'contract_id' => 'nullable|exists:contracts,id',
            'term_id' => 'nullable|exists:contract_terms,id',
            'budget_id' => 'required|exists:budgets,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $validated['net_amount'] = $validated['amount'];
        $blu_payment_submission->update($validated);

        return redirect()->route('blu-payment-submissions.show-detail', $blu_payment_submission)
            ->with('success', 'Pengajuan berhasil diperbarui.');
    }

    public function destroy(BluPaymentSubmission $blu_payment_submission)
    {
        if (!in_array($blu_payment_submission->status, ['Draft', 'Rejected'])) {
            return redirect()->route('blu-payment-submissions.index')
                ->with('error', 'Pengajuan yang sedang diproses tidak dapat dihapus.');
        }

        $blu_payment_submission->delete();
        return redirect()->route('blu-payment-submissions.index')
            ->with('success', 'Pengajuan berhasil dihapus.');
    }

    // ========================
    // Workflow / State Machine
    // ========================

    private $transitions = [
        'Draft'        => 'Verified',
        'Rejected'     => 'Verified',
        'Verified'     => 'Approved SPP',
        'Approved SPP' => 'Approved SPM',
        'Approved SPM' => 'Paid SP2D',
    ];

    public function submit(BluPaymentSubmission $blu_payment_submission)
    {
        if ($blu_payment_submission->status !== 'Draft' && $blu_payment_submission->status !== 'Rejected') {
            return back()->with('error', 'Hanya Draft / Rejected yang bisa diajukan.');
        }

        $blu_payment_submission->update(['status' => 'Verified']);

        $blu_payment_submission->approvalLogs()->create([
            'user_id'     => auth()->id(),
            'action'      => 'submit',
            'from_status' => 'Draft',
            'to_status'   => 'Verified',
            'notes'       => 'Berkas diajukan untuk verifikasi.',
        ]);

        return back()->with('success', 'Berkas berhasil diajukan untuk Verifikasi.');
    }

    public function approve(BluPaymentSubmission $blu_payment_submission)
    {
        $currentStatus = $blu_payment_submission->status;

        if (!isset($this->transitions[$currentStatus]) || $currentStatus === 'Draft' || $currentStatus === 'Rejected') {
            return back()->with('error', 'Status saat ini tidak dapat di-approve.');
        }

        $nextStatus = $this->transitions[$currentStatus];
        $blu_payment_submission->update(['status' => $nextStatus]);

        // When SPP is approved → generate SPM number/date automatically
        if ($currentStatus === 'Approved SPP') {
            $spmNumber = $this->generateSpmFromSpp($blu_payment_submission->spp_number);
            $blu_payment_submission->update([
                'spm_number' => $spmNumber,
                'spm_date'   => now()->toDateString(),
            ]);
        }

        if ($nextStatus === 'Paid SP2D') {
            if ($blu_payment_submission->budget) {
                $blu_payment_submission->budget->increment('realized_budget', $blu_payment_submission->gross_amount);
                $blu_payment_submission->budget->decrement('remaining_budget', $blu_payment_submission->gross_amount);
            }

            if ($blu_payment_submission->term_id) {
                $blu_payment_submission->term()->update(['status' => 'Paid']);
            }
        }

        $blu_payment_submission->approvalLogs()->create([
            'user_id'     => auth()->id(),
            'action'      => 'approve',
            'from_status' => $currentStatus,
            'to_status'   => $nextStatus,
            'notes'       => 'Disetujui: ' . $currentStatus . ' → ' . $nextStatus,
        ]);

        return back()->with('success', 'Berkas berhasil di-approve ke status: ' . $nextStatus);
    }

    public function reject(Request $request, BluPaymentSubmission $blu_payment_submission)
    {
        if (in_array($blu_payment_submission->status, ['Draft', 'Rejected', 'Paid SP2D'])) {
            return back()->with('error', 'Status ini tidak bisa di-reject.');
        }

        $request->validate(['notes' => 'required|string']);

        $oldStatus = $blu_payment_submission->status;
        $blu_payment_submission->update(['status' => 'Rejected']);

        $blu_payment_submission->approvalLogs()->create([
            'user_id'     => auth()->id(),
            'action'      => 'reject',
            'from_status' => $oldStatus,
            'to_status'   => 'Rejected',
            'notes'       => $request->input('notes'),
        ]);

        return back()->with('success', 'Berkas ditolak dan dikembalikan ke Operator.');
    }

    // ====================================
    // Database-backed index & show
    // ====================================

    public function index(): View
    {
        $all = BluPaymentSubmission::with(['contract.supplier', 'budget', 'taxes'])
            ->latest('date')
            ->get();

        // Map DB rows into the shape the blade view expects
        $submissions = $all->map(function (BluPaymentSubmission $t) {
            $taxTotal = $t->taxes->sum('amount');
            $netAmount = $t->gross_amount - $taxTotal;

            $statusMap = [
                'Draft'        => 'Draft',
                'Verified'     => 'Menunggu Verifikasi',
                'Approved SPP' => 'Menunggu Persetujuan',
                'Approved SPM' => 'Proses SP2D',
                'Paid SP2D'    => 'Sudah Cair',
                'Rejected'     => 'Direvisi',
            ];

            $contractTypeMap = $t->contract_id ? 'Kontrak' : 'Non-Kontrak';
            $displayStatus = $statusMap[$t->status] ?? $t->status;

            return [
                'submission_number'    => $t->transaction_number,
                'date'                 => $t->date?->toDateString(),
                'date_label'           => $t->date?->isoFormat('D MMMM Y') ?? '-',
                'npi_number'           => $t->npi_number,
                'title'                => $t->description,
                'supplier'             => $t->contract?->supplier?->name ?? '-',
                'supplier_short'       => $t->contract?->supplier?->name ?? '-',
                'payment_type'         => $t->type,
                'contract_type'        => $contractTypeMap,
                'contract_number'      => $t->contract?->contract_number,
                'contract_term_label'  => $t->term?->term_name,
                'gross_amount'         => (float) $t->gross_amount,
                'ppn'                  => (float) $t->taxes->where('tax_name', 'PPN')->sum('amount'),
                'pph'                  => (float) $t->taxes->whereIn('tax_name', ['PPh 21','PPh 22','PPh 23','PPh 4(2)'])->sum('amount'),
                'tax_total'            => (float) $taxTotal,
                'penalty'              => 0,
                'net_amount'           => (float) $netAmount,
                'status'               => $displayStatus,
                'operator'             => '-',
                'payment_note'         => $t->payment_method ?? '-',
                'is_final'             => in_array($t->status, ['Rejected', 'Paid SP2D'], true),
                'can_submit'           => $t->status === 'Draft',
                'can_cancel'           => ! in_array($t->status, ['Rejected', 'Paid SP2D'], true),
                'alerts'               => [],
            ];
        });

        $stats = [
            [
                'label' => 'Total Pengajuan',
                'count' => $submissions->count(),
                'icon'  => 'receipt_long',
                'color' => 'primary',
                'note'  => 'Seluruh pengajuan BLU',
            ],
            [
                'label' => 'Draft',
                'count' => $submissions->where('status', 'Draft')->count(),
                'icon'  => 'edit_note',
                'color' => 'warning',
                'note'  => 'Belum dikirim verifikasi',
            ],
            [
                'label' => 'Menunggu Verifikasi',
                'count' => $submissions->where('status', 'Menunggu Verifikasi')->count(),
                'icon'  => 'fact_check',
                'color' => 'info',
                'note'  => 'Perlu pemeriksaan awal',
            ],
            [
                'label' => 'Disetujui',
                'count' => $submissions->whereIn('status', ['Menunggu Persetujuan', 'Proses SP2D'])->count(),
                'icon'  => 'task_alt',
                'color' => 'success',
                'note'  => 'Siap proses pencairan',
            ],
            [
                'label' => 'Ditolak / Revisi',
                'count' => $submissions->where('status', 'Direvisi')->count(),
                'icon'  => 'rule',
                'color' => 'danger',
                'note'  => 'Butuh tindak lanjut operator',
            ],
            [
                'label' => 'Sudah Cair',
                'count' => $submissions->where('status', 'Sudah Cair')->count(),
                'icon'  => 'account_balance',
                'color' => 'secondary',
                'note'  => 'Dana telah ditransfer',
            ],
        ];

        $warningSummary = collect();

        return view('blu-payment-submissions.index', [
            'submissions'    => $submissions,
            'stats'          => $stats,
            'warningSummary' => $warningSummary,
        ]);
    }

    public function show(string $submissionNumber): View
    {
        $submission = BluPaymentSubmission::with(['contract.supplier', 'budget', 'taxes', 'term', 'approvalLogs.user'])
            ->where('transaction_number', $submissionNumber)
            ->firstOrFail();

        $statusMap = [
            'Draft'        => 'Draft',
            'Verified'     => 'Menunggu Verifikasi',
            'Approved SPP' => 'Menunggu Persetujuan',
            'Approved SPM' => 'Proses SP2D',
            'Paid SP2D'    => 'Sudah Cair',
            'Rejected'     => 'Direvisi',
        ];

        $taxTotal  = $submission->taxes->sum('amount');
        $netAmount = $submission->gross_amount - $taxTotal;

        $data = [
            'submission_number'    => $submission->transaction_number,
            'date'                 => $submission->date?->toDateString(),
            'date_label'           => $submission->date?->isoFormat('D MMMM Y') ?? '-',
            'npi_number'           => $submission->npi_number,
            'title'                => $submission->description,
            'supplier'             => $submission->contract?->supplier?->name ?? '-',
            'supplier_short'       => $submission->contract?->supplier?->name ?? '-',
            'payment_type'         => $submission->type,
            'contract_type'        => $submission->contract_id ? 'Kontrak' : 'Non-Kontrak',
            'contract_number'      => $submission->contract?->contract_number,
            'contract_term_label'  => $submission->term?->term_name,
            'gross_amount'         => (float) $submission->gross_amount,
            'ppn'                  => (float) $submission->taxes->where('tax_name', 'PPN')->sum('amount'),
            'pph'                  => (float) $submission->taxes->whereIn('tax_name', ['PPh 21','PPh 22','PPh 23','PPh 4(2)'])->sum('amount'),
            'tax_total'            => (float) $taxTotal,
            'penalty'              => 0,
            'net_amount'           => (float) $netAmount,
            'status'               => $statusMap[$submission->status] ?? $submission->status,
            'operator'             => '-',
            'payment_note'         => $submission->payment_method ?? '-',
            'is_final'             => in_array($submission->status, ['Rejected', 'Paid SP2D'], true),
            'can_submit'           => $submission->status === 'Draft',
            'can_cancel'           => ! in_array($submission->status, ['Rejected', 'Paid SP2D'], true),
            'alerts'               => [],
            'bast_date'            => $submission->bast_date?->isoFormat('D MMMM Y') ?? '-',
            'payment_method'       => $submission->payment_method ?? '-',
            'disbursement_status'  => $submission->status === 'Paid SP2D' ? 'Sudah Cair' : 'Belum Diproses',
            'sp2d_number'          => $submission->sp2d_number ?? '-',
            'sp2d_date'            => $submission->sp2d_date ? $submission->sp2d_date->isoFormat('D MMMM Y') : '-',
            'contract_info'        => $submission->contract ? [
                'number'               => $submission->contract->contract_number,
                'description'          => $submission->contract->description,
                'total_amount'         => (float) $submission->contract->total_amount,
                'status'               => $submission->contract->status,
            ] : null,
            'documents'            => [],
            'timeline'             => $submission->approvalLogs->map(fn ($log) => [
                'step'     => $log->action,
                'role'     => $log->user?->name ?? 'System',
                'state'    => 'Selesai',
                'datetime' => $log->created_at->isoFormat('D MMM Y, HH:mm'),
                'note'     => $log->notes,
            ])->toArray(),
        ];

        $statusClasses = [
            'Draft'                  => 'warning text-dark',
            'Menunggu Verifikasi'    => 'info text-dark',
            'Menunggu Persetujuan'   => 'primary',
            'Direvisi'               => 'warning text-dark',
            'Ditolak'                => 'danger',
            'Disetujui'              => 'success',
            'Proses SP2D'            => 'secondary',
            'Sudah Cair'             => 'success',
        ];

        $alertClasses = [
            'warning' => 'warning',
            'danger'  => 'danger',
            'success' => 'success',
        ];

        return view('blu-payment-submissions.show', [
            'submission'    => $data,
            'statusClasses' => $statusClasses,
            'alertClasses'  => $alertClasses,
        ]);
    }
}

