<?php

namespace App\Http\Controllers;

use App\Services\BtnVirtualAccountService;
use Illuminate\Http\Request;

class BtnPaymentCallbackController extends Controller
{
    public function __invoke(Request $request, BtnVirtualAccountService $service)
    {
        $result = $service->handlePaymentCallback($request->all());

        return response()->json([
            'status' => $result['matched'] ? 'success' : 'unmatched',
            'message' => $result['matched']
                ? 'Payment callback processed.'
                : 'Payment callback accepted, invoice not found.',
            'transaction_id' => $result['transaction']->id,
        ]);
    }
}
