<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Services\BtnVirtualAccountService;
use Illuminate\Http\Request;

class BtnPaymentCallbackController extends Controller
{
    public function __invoke(Request $request, BtnVirtualAccountService $service)
    {
        // INT-01: secret callback WAJIB dikonfigurasi. Tanpa ini, pihak luar tanpa
        // kredensial dapat mengirim callback palsu dan menandai tagihan PNBP LUNAS.
        // Tolak total bila secret belum diisi — JANGAN proses callback tanpa verifikasi.
        $secret = IntegrationSetting::getValue('btn.callback_secret');
        abort_if(
            blank($secret),
            503,
            'Callback BTN belum dikonfigurasi (callback_secret kosong).'
        );

        $provided = $request->header('X-Callback-Secret') ?: $request->input('callback_secret');
        abort_unless(
            is_string($provided) && hash_equals((string) $secret, $provided),
            401,
            'Invalid callback signature.'
        );

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
