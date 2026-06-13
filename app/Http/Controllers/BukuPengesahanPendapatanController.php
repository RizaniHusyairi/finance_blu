<?php

namespace App\Http\Controllers;

use App\Models\LaporanPengesahanBlu;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuPengesahanPendapatanController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.pengesahan_pendapatan.index', $this->pembukuanService->buildPengesahanPendapatanIndexData(
            $request->only(['bulan', 'tahun', 'status_pengesahan'])
        ));
    }

    public function show(LaporanPengesahanBlu $laporan)
    {
        return view('pembukuan.pengesahan_pendapatan.show', $this->pembukuanService->buildPengesahanPendapatanDetail($laporan));
    }

    public function pdf(Request $request)
    {
        $data = $this->pembukuanService->buildPengesahanPendapatanIndexData(
            $request->only(['bulan', 'tahun', 'status_pengesahan'])
        );

        return $this->pembukuanService->streamPdf(
            'pembukuan.pengesahan_pendapatan.pdf',
            $data,
            'Buku_Pengesahan_Pendapatan_' . now()->format('Ymd_His') . '.pdf'
        );
    }

    /**
     * Generator record laporan_pengesahan_blu per periode — dipakai bersama
     * oleh Buku Pengesahan Belanja dan Buku Pengesahan Pendapatan.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
        ], [], [
            'bulan' => 'bulan periode',
            'tahun' => 'tahun periode',
        ]);

        try {
            $laporan = $this->pembukuanService->generateLaporanPengesahan(
                (int) $validated['bulan'],
                (int) $validated['tahun'],
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Laporan pengesahan {$laporan->nomor_laporan} berhasil dibuat (status DRAFT).");
    }
}
