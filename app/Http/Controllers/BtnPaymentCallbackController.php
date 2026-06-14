<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Services\BtnVirtualAccountService;
use Illuminate\Http\Request;

class BtnPaymentCallbackController extends Controller
{
    public function __invoke(Request $request, BtnVirtualAccountService $service)
    {
        // Verifikasi keaslian callback bila secret dikonfigurasi.
        // WAJIB diisi untuk produksi agar tidak ada pihak luar yang bisa
        // mengirim callback palsu dan menandai tagihan LUNAS.
        $secret = IntegrationSetting::getValue('btn.callback_secret');
        if (filled($secret)) {
            $provided = $request->header('X-Callback-Secret') ?: $request->input('callback_secret');
            abort_unless(
                is_string($provided) && hash_equals((string) $secret, $provided),
                401,
                'Invalid callback signature.'
            );
        }

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
