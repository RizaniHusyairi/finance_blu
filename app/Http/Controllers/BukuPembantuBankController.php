<?php

namespace App\Http\Controllers;

use App\Models\RekeningBank;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class BukuPembantuBankController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.bank.index', $this->pembukuanService->buildBankIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'search'])
        ));
    }

    public function show(RekeningBank $rekening, Request $request)
    {
        return view('pembukuan.bank.show', $this->pembukuanService->buildBankRekeningDetail(
            $rekening,
            $request->only(['start_date', 'end_date', 'arah_mutasi', 'status_rekonsiliasi'])
        ));
    }
}
