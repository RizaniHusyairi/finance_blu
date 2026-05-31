<?php

namespace App\Http\Controllers;

use App\Models\BukuKasUmum;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuKasUmumController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->pembukuanService->buildBkuIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'arus_kas', 'sumber_transaksi'])
        );

        return view('pembukuan.bku.index', $data);
    }

    public function storeSaldoAwal(Request $request)
    {
        $validated = $request->validate([
            'rekening_bank_id' => ['required', 'integer', 'exists:rekening_bank,id'],
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'uraian' => ['nullable', 'string', 'max:255'],
        ], [], [
            'rekening_bank_id' => 'rekening bank',
            'tanggal' => 'tanggal saldo awal',
            'nominal' => 'nominal saldo awal',
        ]);

        try {
            $this->pembukuanService->createSaldoAwal(
                (int) $validated['rekening_bank_id'],
                $validated['tanggal'],
                (float) $validated['nominal'],
                $validated['uraian'] ?? null,
            );
        } catch (\DomainException $e) {
            return redirect()
                ->route('pembukuan.bku.index', ['rekening_bank_id' => $validated['rekening_bank_id']])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('pembukuan.bku.index', ['rekening_bank_id' => $validated['rekening_bank_id']])
            ->with('success', 'Saldo awal berhasil dicatat ke Buku Kas Umum.');
    }

    public function show(BukuKasUmum $id)
    {
        return view('pembukuan.bku.show', $this->pembukuanService->buildBkuDetail($id));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildBkuIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'arus_kas', 'sumber_transaksi'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.bku.pdf',
            $data,
            'Buku_Kas_Umum_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    public function excel(Request $request)
    {
        return $this->pembukuanService->streamBkuExcel(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'arus_kas', 'sumber_transaksi']),
            'Buku_Kas_Umum_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
