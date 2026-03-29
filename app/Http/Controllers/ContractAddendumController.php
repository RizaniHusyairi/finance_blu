<?php

namespace App\Http\Controllers;

use App\Models\ContractAddendum;
use App\Models\Contract;
use App\Models\ApprovalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractAddendumController extends Controller
{
    public function create(Contract $contract)
    {
        return view('contracts.create_addendum', compact('contract'));
    }

    public function store(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'addendum_number' => 'required|string|max:255',
            'date' => 'required|date',
            'new_total_amount' => 'nullable|numeric',
            'new_end_date' => 'nullable|date',
            'reason' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $validated['status'] = 'Menunggu PPK';
            $validated['submitted_by'] = Auth::id();

            $addendum = $contract->addendums()->create($validated);

            ApprovalLog::create([
                'contract_id'  => $contract->id, // Use contract_id for tracking
                'user_id'      => Auth::id(),
                'role_name'    => Auth::user()->getRoleNames()->first() ?? '-',
                'status_from'  => null,
                'status_to'    => 'Menunggu PPK (Addendum)',
                'notes'        => 'Adendum nomor ' . $validated['addendum_number'] . ' diajukan untuk persetujuan PPK.',
            ]);

            DB::commit();
            return redirect()->route('contracts.show', $contract)->with('success', 'Addendum berhasil ditambahkan dan diajukan ke PPK.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan adendum: ' . $e->getMessage());
        }
    }

    public function submit(Contract $contract, ContractAddendum $addendum)
    {
        if ($addendum->status !== 'Draft' && $addendum->status !== 'Ditolak') {
            return back()->with('error', 'Adendum tidak dapat diajukan dari status saat ini.');
        }

        $oldStatus = $addendum->status;
        $addendum->update([
            'status' => 'Menunggu PPK',
            'submitted_by' => Auth::id()
        ]);

        ApprovalLog::create([
            'contract_id'  => $contract->id,
            'user_id'      => Auth::id(),
            'role_name'    => Auth::user()->getRoleNames()->first() ?? '-',
            'status_from'  => $oldStatus . ' (Addendum)',
            'status_to'    => 'Menunggu PPK (Addendum)',
            'notes'        => 'Adendum nomor ' . $addendum->addendum_number . ' diajukan ulang untuk persetujuan PPK.',
        ]);

        return back()->with('success', 'Adendum berhasil diajukan ke PPK untuk persetujuan.');
    }

    public function approve(Request $request, Contract $contract, ContractAddendum $addendum)
    {
        if ($addendum->status !== 'Menunggu PPK') {
            return back()->with('error', 'Adendum tidak dalam status menunggu persetujuan.');
        }

        try {
            DB::beginTransaction();

            $addendum->update(['status' => 'Disetujui']);

            // Update contract's current total amount and end date based on approved addendum
            $updates = [];
            if ($addendum->new_total_amount) {
                $updates['total_amount'] = $addendum->new_total_amount;
            }
            if ($addendum->new_end_date) {
                $updates['end_date'] = $addendum->new_end_date;
            }

            if (!empty($updates)) {
                $contract->update($updates);
            }

            ApprovalLog::create([
                'contract_id'  => $contract->id,
                'user_id'      => Auth::id(),
                'role_name'    => 'PPK',
                'status_from'  => 'Menunggu PPK (Addendum)',
                'status_to'    => 'Disetujui (Addendum)',
                'notes'        => $request->input('notes', 'Adendum nomor ' . $addendum->addendum_number . ' disetujui oleh PPK.'),
            ]);

            DB::commit();
            return back()->with('success', 'Adendum berhasil disetujui dan data kontrak diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui adendum: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, Contract $contract, ContractAddendum $addendum)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        if ($addendum->status !== 'Menunggu PPK') {
            return back()->with('error', 'Adendum tidak dalam status menunggu persetujuan.');
        }

        $addendum->update(['status' => 'Ditolak']);

        ApprovalLog::create([
            'contract_id'  => $contract->id,
            'user_id'      => Auth::id(),
            'role_name'    => 'PPK',
            'status_from'  => 'Menunggu PPK (Addendum)',
            'status_to'    => 'Ditolak (Addendum)',
            'notes'        => 'Penolakan Adendum ' . $addendum->addendum_number . ': ' . $request->input('notes'),
        ]);

        return back()->with('success', 'Adendum berhasil ditolak.');
    }

    public function destroy(Contract $contract, ContractAddendum $addendum)
    {
        $addendum->delete();
        // Note: Ideally deleting an addendum should revert the contract's total_amount/end_date to a previous state, 
        // but for simplicity in this prototype, we just delete the record.
        return redirect()->route('contracts.show', $contract)->with('success', 'Addendum berhasil dihapus.');
    }
}
