<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class HonorariumController extends Controller
{
    public function index()
    {
        $honorariums = Transaction::with(['budget'])
            ->where('type', 'HONORARIUM')
            ->latest()
            ->get();

        return view('honorarium.index', compact('honorariums'));
    }

    public function create()
    {
        $budgets = Budget::orderBy('coa')->get();
        $ppks = User::role('PPK')->get();

        $nextNumber = $this->generateNextHonorariumNumber();

        return view('honorarium.create', compact('budgets', 'ppks', 'nextNumber'));
    }

    public function store(Request $request)
        {
            $validated = $request->validate([
                'transaction_number'   => 'required|string|max:255|unique:transactions,transaction_number',
                'date'                 => 'required|date',
                'spp_number'           => 'required|string|max:255|unique:transactions,spp_number',
                'spp_date'             => 'required|date',
                'bast_number'          => 'required|string|max:255',
                'bast_date'            => 'required|date',
                'activity_number'      => 'nullable|string|max:255',
                'budget_id'            => 'required|exists:budgets,id',
                'ppk_id'               => 'nullable|exists:users,id',
                'description'          => 'required|string',
                'submit_type'          => 'required|in:draft,submit_ppk',

                'items'                        => 'required|array|min:1',
                'items.*.name'                 => 'required|string|max:255',
                'items.*.nrp'                  => 'nullable|string|max:255',
                'items.*.rank_corps'           => 'nullable|string|max:255',
                'items.*.position'             => 'nullable|string|max:255',
                'items.*.honor_amount'         => 'required|numeric|min:0',
                'items.*.pph_amount'           => 'nullable|numeric|min:0',
                'items.*.bank_account_number'  => 'nullable|string|max:255',
                'items.*.bank_name'            => 'nullable|string|max:255',
                'items.*.bank_account_name'    => 'nullable|string|max:255',
                'items.*.phone_number'         => 'nullable|string|max:255',
            ]);

            $grossAmount = collect($validated['items'])->sum(function ($item) {
                return (float) ($item['honor_amount'] ?? 0);
            });

            $totalPph = collect($validated['items'])->sum(function ($item) {
                return (float) ($item['pph_amount'] ?? 0);
            });

            $netAmount = $grossAmount - $totalPph;

            $transaction = Transaction::create([
                'activity_number'      => $validated['activity_number'] ?? null,
                'transaction_number'   => $validated['transaction_number'],
                'date'                 => $validated['date'],
                'type'                 => 'HONORARIUM',
                'payment_method'       => 'SP2D BLU - TRF',
                'description'          => $validated['description'],
                'jenis_pengajuan'      => 'Non Kontrak',
                'jenis_dokumen_dasar'  => 'BAST',
                'bast_number'          => $validated['bast_number'],
                'bast_date'            => $validated['bast_date'],
                'spp_number'           => $validated['spp_number'],
                'spp_date'             => $validated['spp_date'],
                'budget_id'            => $validated['budget_id'],
                'ppk_id'               => $validated['ppk_id'] ?? null,
                'gross_amount'         => $grossAmount,
                'net_amount'           => $netAmount,
                'status'               => $validated['submit_type'] === 'submit_ppk'
                ? 'Menunggu Persetujuan PPK'
                : 'Draft',
            ]);

            foreach ($validated['items'] as $index => $item) {
                $honor = (float) ($item['honor_amount'] ?? 0);
                $pph = (float) ($item['pph_amount'] ?? 0);

                $transaction->honorariumItems()->create([
                    'sequence_no'          => $index + 1,
                    'name'                 => $item['name'],
                    'nrp'                  => $item['nrp'] ?? null,
                    'rank_corps'           => $item['rank_corps'] ?? null,
                    'position'             => $item['position'] ?? null,
                    'honor_amount'         => $honor,
                    'pph_amount'           => $pph,
                    'net_amount'           => $honor - $pph,
                    'bank_account_number'  => $item['bank_account_number'] ?? null,
                    'bank_name'            => $item['bank_name'] ?? null,
                    'bank_account_name'    => $item['bank_account_name'] ?? null,
                    'phone_number'         => $item['phone_number'] ?? null,
                ]);
            }

            return redirect()
                ->route('honorarium.index')
                ->with('success', 'Data honorarium berhasil disimpan.');
        }

        public function show(Transaction $honorarium)
{
    abort_if($honorarium->type !== 'HONORARIUM', 404);

    $honorarium->load(['budget', 'honorariumItems']);

    return view('honorarium.show', compact('honorarium'));
}

public function edit(Transaction $honorarium)
{
    abort_if($honorarium->type !== 'HONORARIUM', 404);

    if ($this->isApproved($honorarium)) {
        return redirect()
            ->route('honorarium.index')
            ->with('error', 'Data yang sudah di-approve tidak bisa diedit.');
    }

    $budgets = Budget::orderBy('coa')->get();
    $ppks = User::role('PPK')->get();
    $honorarium->load('honorariumItems');

    return view('honorarium.edit', compact('honorarium', 'budgets', 'ppks'));
}

public function update(Request $request, Transaction $honorarium)
{
    abort_if($honorarium->type !== 'HONORARIUM', 404);

    if ($this->isApproved($honorarium)) {
        return redirect()
            ->route('honorarium.index')
            ->with('error', 'Data yang sudah di-approve tidak bisa diedit.');
    }

    $validated = $request->validate([
    'date'                 => 'required|date',
    'spp_number'           => 'required|string|max:255|unique:transactions,spp_number,' . $honorarium->id,
    'spp_date'             => 'required|date',
    'bast_number'          => 'required|string|max:255',
    'bast_date'            => 'required|date',
    'activity_number'      => 'nullable|string|max:255',
    'budget_id'            => 'required|exists:budgets,id',
    'ppk_id'               => 'nullable|exists:users,id',
    'description'          => 'required|string',
    'submit_type'          => 'required|in:draft,submit_ppk',

    'items'                        => 'required|array|min:1',
    'items.*.name'                 => 'required|string|max:255',
    'items.*.nrp'                  => 'nullable|string|max:255',
    'items.*.rank_corps'           => 'nullable|string|max:255',
    'items.*.position'             => 'nullable|string|max:255',
    'items.*.honor_amount'         => 'required|numeric|min:0',
    'items.*.pph_amount'           => 'nullable|numeric|min:0',
    'items.*.bank_account_number'  => 'nullable|string|max:255',
    'items.*.bank_name'            => 'nullable|string|max:255',
    'items.*.bank_account_name'    => 'nullable|string|max:255',
    'items.*.phone_number'         => 'nullable|string|max:255',
]);

    $grossAmount = collect($validated['items'])->sum(fn ($item) => (float) ($item['honor_amount'] ?? 0));
    $totalPph = collect($validated['items'])->sum(fn ($item) => (float) ($item['pph_amount'] ?? 0));
    $netAmount = $grossAmount - $totalPph;

    $honorarium->update([
    'activity_number'      => $validated['activity_number'] ?? null,
    'date'                 => $validated['date'],
    'payment_method'       => 'SP2D BLU - TRF',
    'description'          => $validated['description'],
    'jenis_pengajuan'      => 'Non Kontrak',
    'jenis_dokumen_dasar'  => 'BAST',
    'bast_number'          => $validated['bast_number'],
    'bast_date'            => $validated['bast_date'],
    'spp_number'           => $validated['spp_number'],
    'spp_date'             => $validated['spp_date'],
    'budget_id'            => $validated['budget_id'],
    'ppk_id'               => $validated['ppk_id'] ?? null,
    'gross_amount'         => $grossAmount,
    'net_amount'           => $netAmount,
    'status'               => $validated['submit_type'] === 'submit_ppk'
        ? 'Menunggu Persetujuan PPK'
        : 'Draft',
]);

    $honorarium->honorariumItems()->delete();

    foreach ($validated['items'] as $index => $item) {
        $honor = (float) ($item['honor_amount'] ?? 0);
        $pph = (float) ($item['pph_amount'] ?? 0);

        $honorarium->honorariumItems()->create([
            'sequence_no'          => $index + 1,
            'name'                 => $item['name'],
            'nrp'                  => $item['nrp'] ?? null,
            'rank_corps'           => $item['rank_corps'] ?? null,
            'position'             => $item['position'] ?? null,
            'honor_amount'         => $honor,
            'pph_amount'           => $pph,
            'net_amount'           => $honor - $pph,
            'bank_account_number'  => $item['bank_account_number'] ?? null,
            'bank_name'            => $item['bank_name'] ?? null,
            'bank_account_name'    => $item['bank_account_name'] ?? null,
            'phone_number'         => $item['phone_number'] ?? null,
        ]);
    }

    return redirect()
        ->route('honorarium.index')
        ->with('success', 'Data honorarium berhasil diupdate.');
}

public function pendingPpk()
{
    $honorariums = Transaction::with(['budget', 'honorariumItems'])
        ->where('type', 'HONORARIUM')
        ->where('ppk_id', auth()->id())
        ->where('status', 'Menunggu Persetujuan PPK')
        ->latest()
        ->get();

    return view('honorarium.ppk-pending', compact('honorariums'));
}

public function approvePpk(Transaction $honorarium)
{
    abort_if($honorarium->type !== 'HONORARIUM', 404);

    if ((int) $honorarium->ppk_id !== (int) auth()->id()) {
        abort(403, 'Pengajuan ini bukan untuk PPK yang sedang login.');
    }

    if ($honorarium->status !== 'Menunggu Persetujuan PPK') {
        return redirect()
            ->route('honorarium.ppk.pending')
            ->with('error', 'Status data ini tidak bisa disetujui.');
    }

    $honorarium->update([
        'status' => 'Disetujui PPK',
    ]);

    // optional log
    if (method_exists($honorarium, 'approvalLogs')) {
        $honorarium->approvalLogs()->create([
            'user_id' => auth()->id(),
            'status_from' => 'Menunggu Persetujuan PPK',
            'status_to' => 'Disetujui PPK',
            'notes' => 'Pengajuan honorarium disetujui oleh PPK.',
        ]);
    }

    return redirect()
        ->route('honorarium.ppk.pending')
        ->with('success', 'Honorarium berhasil disetujui PPK.');
}

public function rejectPpk(Request $request, Transaction $honorarium)
{
    abort_if($honorarium->type !== 'HONORARIUM', 404);

    if ((int) $honorarium->ppk_id !== (int) auth()->id()) {
        abort(403, 'Pengajuan ini bukan untuk PPK yang sedang login.');
    }

    if ($honorarium->status !== 'Menunggu Persetujuan PPK') {
        return redirect()
            ->route('honorarium.ppk.pending')
            ->with('error', 'Status data ini tidak bisa ditolak.');
    }

    $honorarium->update([
        'status' => 'Ditolak PPK',
    ]);

    // optional log
    if (method_exists($honorarium, 'approvalLogs')) {
        $honorarium->approvalLogs()->create([
            'user_id' => auth()->id(),
            'status_from' => 'Menunggu Persetujuan PPK',
            'status_to' => 'Ditolak PPK',
            'notes' => 'Pengajuan honorarium ditolak oleh PPK.',
        ]);
    }

    return redirect()
        ->route('honorarium.ppk.pending')
        ->with('success', 'Honorarium berhasil ditolak PPK.');
}

public function destroy(Transaction $honorarium)
{
    abort_if($honorarium->type !== 'HONORARIUM', 404);

    if ($this->isApproved($honorarium)) {
        return redirect()
            ->route('honorarium.index')
            ->with('error', 'Data yang sudah di-approve tidak bisa dihapus.');
    }

    $honorarium->honorariumItems()->delete();
    $honorarium->delete();

    return redirect()
        ->route('honorarium.index')
        ->with('success', 'Data honorarium berhasil dihapus.');
}

private function isApproved(Transaction $honorarium): bool
{
    return in_array($honorarium->status, [
        'Menunggu Persetujuan PPK',
        'Disetujui PPK',
        'Approved',
        'Approved SPP',
        'Approved SPM',
        'Paid SP2D',
    ]);
}
    private function generateNextHonorariumNumber(): string
    {
        $year = now()->format('Y');

        $last = Transaction::whereYear('created_at', $year)
            ->where('type', 'HONORARIUM')
            ->whereNotNull('transaction_number')
            ->orderByDesc('id')
            ->first();

        $next = 1;

        if ($last && preg_match('/(\d+)$/', $last->transaction_number, $match)) {
            $next = ((int) $match[1]) + 1;
        }

        return 'HON-BLU/APTP-' . $year . '/' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}