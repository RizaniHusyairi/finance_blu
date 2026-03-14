<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Contract;
use App\Models\Budget;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        // Load with contract, budget to show in the list
        $transactions = Transaction::with(['contract', 'budget'])->get();
        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        // Get active contracts for the dropdown
        $contracts = Contract::where('status', 'Active')->with('terms')->get();
        // Get budgets for UP/TUP transactions that don't need a contract
        $budgets = Budget::all();
        
        return view('transactions.create', compact('contracts', 'budgets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_number' => 'required|string|max:255|unique:transactions',
            'date' => 'required|date',
            'type' => 'required|in:LS,UP,TUP',
            'description' => 'required|string',
            'contract_id' => 'nullable|exists:contracts,id',
            'term_id' => 'nullable|exists:contract_terms,id', // Associated termin if LS
            'budget_id' => 'required|exists:budgets,id', // Every transaction needs a budget
            'amount' => 'required|numeric|min:0',
        ]);
        
        // Initial state is Draft
        $validated['status'] = 'Draft';
        $validated['net_amount'] = $validated['amount'];

        $transaction = Transaction::create($validated);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi berhasil dibuat. Silakan tambahkan rincian pajak jika ada, lalu ajukan Verifikasi.');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['contract', 'budget', 'taxes', 'term', 'approvalLogs.user']);
        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        // Only allow edits if status is Draft or Rejected
        if (!in_array($transaction->status, ['Draft', 'Rejected'])) {
             return redirect()->route('transactions.show', $transaction)->with('error', 'Transaksi yang sedang diproses tidak dapat diubah.');
        }
        
        $contracts = Contract::where('status', 'Active')->with('terms')->get();
        $budgets = Budget::all();
        
        return view('transactions.edit', compact('transaction', 'contracts', 'budgets'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if (!in_array($transaction->status, ['Draft', 'Rejected'])) {
             return redirect()->route('transactions.show', $transaction)->with('error', 'Transaksi yang sedang diproses tidak dapat diubah.');
        }

        $validated = $request->validate([
            'transaction_number' => 'required|string|max:255|unique:transactions,transaction_number,' . $transaction->id,
            'date' => 'required|date',
            'type' => 'required|in:LS,UP,TUP',
            'description' => 'required|string',
            'contract_id' => 'nullable|exists:contracts,id',
            'term_id' => 'nullable|exists:contract_terms,id',
            'budget_id' => 'required|exists:budgets,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $validated['net_amount'] = $validated['amount'];
        $transaction->update($validated);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction)
    {
        if (!in_array($transaction->status, ['Draft', 'Rejected'])) {
             return redirect()->route('transactions.index')->with('error', 'Transaksi yang sedang diproses tidak dapat dihapus.');
        }
        
        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }
    
    // ========================
    // Workflow / State Machine
    // ========================
    
    // State Transitions:
    // Draft → Verified (Operator BLU submits)
    // Verified → Approved SPP (PPK approves SPP)  
    // Approved SPP → Approved SPM (PPSPM approves SPM)
    // Approved SPM → Paid SP2D (Bendahara confirms payment)
    // Any state → Rejected (Approver rejects, back to operator for correction)
    
    private $transitions = [
        'Draft'        => 'Verified',
        'Rejected'     => 'Verified',
        'Verified'     => 'Approved SPP',
        'Approved SPP' => 'Approved SPM',
        'Approved SPM' => 'Paid SP2D',
    ];

    public function submit(Transaction $transaction)
    {
        if ($transaction->status !== 'Draft' && $transaction->status !== 'Rejected') {
            return back()->with('error', 'Hanya Draft / Rejected yang bisa diajukan.');
        }
        
        $transaction->update(['status' => 'Verified']);
        
        // Log the action
        $transaction->approvalLogs()->create([
            'user_id'     => auth()->id(),
            'action'      => 'submit',
            'from_status' => 'Draft',
            'to_status'   => 'Verified',
            'notes'       => 'Berkas diajukan untuk verifikasi.',
        ]);
        
        return back()->with('success', 'Berkas berhasil diajukan untuk Verifikasi.');
    }

    public function approve(Transaction $transaction)
    {
        $currentStatus = $transaction->status;
        
        if (!isset($this->transitions[$currentStatus]) || $currentStatus === 'Draft' || $currentStatus === 'Rejected') {
            return back()->with('error', 'Status saat ini tidak dapat di-approve.');
        }
        
        $nextStatus = $this->transitions[$currentStatus];
        $transaction->update(['status' => $nextStatus]);
        
        // If we just moved to Paid SP2D and there's a related termin, mark it as Paid
        if ($nextStatus === 'Paid SP2D') {
            if ($transaction->budget) {
                $transaction->budget->increment('realized_budget', $transaction->gross_amount);
                $transaction->budget->decrement('remaining_budget', $transaction->gross_amount);
            }

            if ($transaction->term_id) {
                $transaction->term()->update(['status' => 'Paid']);
            }
        }
        
        // Log
        $transaction->approvalLogs()->create([
            'user_id'     => auth()->id(),
            'action'      => 'approve',
            'from_status' => $currentStatus,
            'to_status'   => $nextStatus,
            'notes'       => 'Disetujui: ' . $currentStatus . ' → ' . $nextStatus,
        ]);
        
        return back()->with('success', 'Berkas berhasil di-approve ke status: ' . $nextStatus);
    }

    public function reject(Request $request, Transaction $transaction)
    {
        if (in_array($transaction->status, ['Draft', 'Rejected', 'Paid SP2D'])) {
            return back()->with('error', 'Status ini tidak bisa di-reject.');
        }

        $request->validate(['notes' => 'required|string']);

        $oldStatus = $transaction->status;
        $transaction->update(['status' => 'Rejected']);
        
        // Log
        $transaction->approvalLogs()->create([
            'user_id'     => auth()->id(),
            'action'      => 'reject',
            'from_status' => $oldStatus,
            'to_status'   => 'Rejected',
            'notes'       => $request->input('notes'),
        ]);
        
        return back()->with('success', 'Berkas ditolak dan dikembalikan ke Operator.');
    }
}
