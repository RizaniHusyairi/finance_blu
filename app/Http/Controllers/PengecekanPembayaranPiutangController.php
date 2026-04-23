<?php

namespace App\Http\Controllers;

use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;

class PengecekanPembayaranPiutangController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.piutang.index', $this->pembukuanService->buildPiutangIndexData(
            $request->only(['start_date', 'end_date', 'status_pembayaran', 'search'])
        ));
    }
}
