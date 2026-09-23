<?php

namespace App\Http\Controllers;

use App\Models\IntegrationLog;
use App\Models\PaymentTransaction;
use App\Models\TagihanJasa;
use App\Services\Btn\BtnSnapConfig;
use App\Services\Btn\BtnSnapException;
use App\Services\Btn\BtnSnapMoney;
use App\Services\Btn\BtnSnapSignature;
use App\Services\BtnVirtualAccountService;
use App\Services\Pembukuan\PiutangSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BtnSnapController extends Controller
{
    public function __construct(private BtnSnapConfig $config, private BtnSnapSignature $signature) {}

    public function inquiry(Request $request)
    {
        return $this->handle($request, '24', function (array $payload) {
            return DB::transaction(function () use ($payload) {
                $tagihan = $this->invoice($payload, '24');
                $this->assertPayable($tagihan, '24');
                $stored = $tagihan->btn_va_data['request'];

                return [
                    'responseCode' => '2002400', 'responseMessage' => 'Successful',
                    'virtualAccountData' => [
                        'inquiryStatus' => '00', 'inquiryReason' => ['english' => 'Success', 'indonesia' => 'Sukses'],
                        'partnerServiceId' => $stored['partnerServiceId'], 'customerNo' => $stored['customerNo'],
                        'virtualAccountNo' => $stored['virtualAccountNo'], 'virtualAccountName' => $stored['virtualAccountName'],
                        'inquiryRequestId' => $payload['inquiryRequestId'],
                        'totalAmount' => ['value' => BtnSnapMoney::decimal($this->outstanding($tagihan)), 'currency' => 'IDR'],
                        'virtualAccountTrxType' => $stored['virtualAccountTrxType'],
                        'billDetails' => [], 'additionalInfo' => (object) [],
                    ],
                ];
            });
        });
    }

    public function payment(Request $request)
    {
        return $this->handle($request, '25', function (array $payload) {
            return DB::transaction(function () use ($payload) {
                $tagihan = $this->invoice($payload, '25');
                $amount = BtnSnapMoney::cents($payload['paidAmount']['value']);
                $reference = 'BTN-SNAP-'.hash('sha256', trim($payload['partnerServiceId']).'|'.$payload['paymentRequestId']);
                // Lock the invoice before summing payments. This also serializes first-time callbacks.
                $existing = PaymentTransaction::where('provider', 'btn')->where('external_reference', $reference)->lockForUpdate()->first();
                if ($existing) {
                    if ($existing->tagihan_jasa_id !== $tagihan->id || BtnSnapMoney::cents($existing->amount) !== $amount
                        || data_get($existing->payload, 'trxId') !== $payload['trxId']
                        || data_get($existing->payload, 'referenceNo') !== ($payload['referenceNo'] ?? null)
                        || ! $this->date(data_get($existing->payload, 'trxDateTime'), '25')->equalTo($this->date($payload['trxDateTime'], '25'))
                        || BtnSnapMoney::cents(data_get($existing->payload, 'totalAmount.value')) !== BtnSnapMoney::cents($payload['totalAmount']['value'])) {
                        throw new BtnSnapException('Conflicting payment request', '4092501');
                    }
                    if ($existing->provider_response) {
                        return $existing->provider_response;
                    }
                    throw new BtnSnapException('Payment requires reconciliation', '5002500');
                }
                $this->assertPayable($tagihan, '25', false);
                $stored = $tagihan->btn_va_data['request'];
                $paidAt = $this->date($payload['trxDateTime'], '25');
                if ($paidAt->greaterThan(now()->addSeconds(120)) || $paidAt->greaterThan(Carbon::parse($stored['expiredDate']))) {
                    throw new BtnSnapException('Transaction Expired', '4032500');
                }
                $outstanding = $this->outstanding($tagihan);
                if ($amount <= 0 || $amount > $outstanding
                    || BtnSnapMoney::cents($payload['totalAmount']['value']) !== $outstanding
                    || ($stored['virtualAccountTrxType'] === 'F' && $amount !== $outstanding)) {
                    throw new BtnSnapException('Invalid Amount', '4042513');
                }
                $total = BtnSnapMoney::cents((string) $tagihan->jumlah_dibayar) + $amount;
                $localTotal = BtnSnapMoney::cents(number_format($tagihan->total_dengan_denda, 2, '.', ''));
                $full = $total >= $localTotal;
                $response = [
                    'responseCode' => '2002500', 'responseMessage' => 'Successful',
                    'virtualAccountData' => array_intersect_key($payload, array_flip([
                        'partnerServiceId', 'customerNo', 'virtualAccountNo', 'trxId', 'paymentRequestId',
                        'paidAmount', 'totalAmount', 'trxDateTime', 'referenceNo', 'journalNum', 'flagAdvise', 'billDetails', 'freeTexts',
                    ])) + [
                        'virtualAccountName' => $stored['virtualAccountName'],
                        'paymentFlagStatus' => '00', 'paymentFlagReason' => ['english' => 'Success', 'indonesia' => 'Sukses'],
                    ],
                    'additionalInfo' => (object) [],
                ];
                PaymentTransaction::create([
                    'provider' => 'btn', 'external_reference' => $reference, 'tagihan_jasa_id' => $tagihan->id,
                    'virtual_account' => $tagihan->nomor_va, 'amount' => BtnSnapMoney::decimal($amount),
                    'status' => $full ? 'paid' : 'partial', 'paid_at' => $paidAt,
                    'payment_channel' => $payload['channelCode'] ?? null, 'payload' => $payload, 'provider_response' => $response,
                ]);
                $tagihan->update([
                    'jumlah_dibayar' => BtnSnapMoney::decimal($total), 'sisa_tagihan' => BtnSnapMoney::decimal(max(0, $localTotal - $total)),
                    'payment_reference' => $payload['referenceNo'] ?? $payload['paymentRequestId'],
                    'payment_channel' => $payload['channelCode'] ?? null, 'last_payment_sync_at' => now(),
                ] + ($full ? [
                    'status' => 'LUNAS', 'status_pembayaran' => 'lunas', 'paid_at' => $paidAt,
                    'tanggal_lunas' => $paidAt->copy()->timezone(config('app.timezone'))->toDateString(),
                ] : []));
                $payment = ['amount' => $total / 100, 'paid_at' => $paidAt, 'reference' => $tagihan->payment_reference];
                $sync = app(PiutangSyncService::class);
                $booked = $full ? $sync->syncFromLunas($tagihan, $payment) : $sync->syncFromPartial($tagihan, $payment);
                if (! $booked) {
                    // Existing bookkeeping service reports failures with null. Roll back the outer transaction too.
                    throw new BtnSnapException('Bookkeeping unavailable; retry payment', '5002500');
                }
                IntegrationLog::create([
                    'provider' => 'btn', 'action' => 'snap_payment', 'direction' => 'inbound', 'status' => 'success',
                    'reference_type' => TagihanJasa::class, 'reference_id' => $tagihan->id,
                    'response_payload' => ['responseCode' => '2002500'],
                ]);
                if ($full) {
                    DB::afterCommit(function () use ($tagihan, $payment) {
                        try {
                            app(BtnVirtualAccountService::class)->sendLunasNotification($tagihan, $payment);
                        } catch (\Throwable) {
                            Log::error('Notifikasi pelunasan SNAP gagal.', ['tagihan_id' => $tagihan->id]);
                        }
                    });
                }

                return $response;
            });
        });
    }

    private function handle(Request $request, string $service, callable $callback)
    {
        try {
            $this->authenticate($request, $service);
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload) || array_is_list($payload)) {
                throw new BtnSnapException('Parsing Error', '400'.$service.'00');
            }
            $rules = [
                'partnerServiceId' => ['required', 'string', 'max:8'], 'customerNo' => ['required', 'string', 'regex:/^\d{1,15}$/D'],
                'virtualAccountNo' => ['required', 'string', 'max:24', 'regex:/^ *\d+$/D'],
            ];
            if ($service === '24') {
                $rules['inquiryRequestId'] = ['required', 'string', 'max:64'];
            } else {
                $rules += [
                    'trxId' => ['required', 'string', 'max:19'], 'paymentRequestId' => ['required', 'string', 'max:64'],
                    'paidAmount.value' => ['required', 'string', 'regex:/^\d{1,12}\.\d{2}$/D'],
                    'paidAmount.currency' => ['required', 'in:IDR'],
                    'totalAmount.value' => ['required', 'string', 'regex:/^\d{1,12}\.\d{2}$/D'],
                    'totalAmount.currency' => ['required', 'in:IDR'], 'trxDateTime' => ['required', 'string', 'max:25'],
                    'referenceNo' => ['sometimes', 'nullable', 'string', 'max:64'],
                    'channelCode' => ['sometimes', 'nullable', 'regex:/^\d{4}$/D'],
                    'flagAdvise' => ['sometimes', 'string', 'in:N,Y'],
                ];
            }
            $validator = Validator::make($payload, $rules);
            if ($validator->fails()) {
                $missing = collect($validator->failed())->contains(fn ($rules) => isset($rules['Required']));
                throw new BtnSnapException($missing ? 'Missing Mandatory Field' : 'Invalid Field Format', '400'.$service.($missing ? '02' : '01'));
            }

            return response()->json($callback($payload));
        } catch (BtnSnapException $e) {
            $code = $e->responseCode === '5000000' ? '500'.$service.'00' : $e->responseCode;

            return response()->json(['responseCode' => $code, 'responseMessage' => $e->getMessage()], (int) substr($code, 0, 3));
        } catch (\JsonException) {
            return response()->json(['responseCode' => '400'.$service.'00', 'responseMessage' => 'Parsing Error'], 400);
        } catch (\Throwable $e) {
            Log::error('SNAP BTN gagal diproses.', ['service' => $service, 'exception' => $e::class]);

            return response()->json(['responseCode' => '500'.$service.'00', 'responseMessage' => 'Internal Server Error'], 500);
        }
    }

    private function authenticate(Request $request, string $service): void
    {
        if (! $this->config->get('enabled', false) || ! in_array($this->config->mode(), ['sandbox', 'production'], true)
            || $this->config->get('inbound_auth', 'disabled') !== 'rsa'
            || blank($this->config->get('bank_public_key')) || blank($this->config->get('inbound_partner_id'))
            || blank($this->config->get('partner_service_id'))) {
            throw new BtnSnapException('SNAP inbound belum dikonfigurasi.', '503'.$service.'00');
        }
        if (! $request->isJson() || strlen($request->getContent()) > 65536) {
            throw new BtnSnapException('Invalid Field Format', '400'.$service.'01');
        }
        $timestamp = (string) $request->header('X-TIMESTAMP');
        $date = $this->date($timestamp, $service);
        if (! str_ends_with($timestamp, '+07:00') || abs(now()->timestamp - $date->timestamp) > 120
            || ! hash_equals($this->config->get('inbound_partner_id'), (string) $request->header('X-PARTNER-ID'))
            || ! preg_match('/^[A-Za-z0-9]{1,16}$/D', (string) $request->header('X-EXTERNAL-ID'))
            || ! preg_match('/^\d{5}$/D', (string) $request->header('CHANNEL-ID'))) {
            throw new BtnSnapException('Unauthorized', '401'.$service.'00');
        }
        $value = $this->signature->stringToSign($request->method(), $request->getRequestUri(), $request->getContent(), $timestamp);
        if (! $this->signature->verify($value, (string) $request->header('X-SIGNATURE'), $this->config->get('bank_public_key'))) {
            throw new BtnSnapException('Unauthorized Signature', '401'.$service.'00');
        }
    }

    private function date(string $value, string $service): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/D', $value)) {
            throw new BtnSnapException('Invalid Field Format', '400'.$service.'01');
        }
        try {
            $date = Carbon::parse($value);
            if ($date->format('Y-m-d\TH:i:sP') !== str_replace('Z', '+00:00', $value)) {
                throw new \RuntimeException;
            }

            return $date;
        } catch (\Throwable) {
            throw new BtnSnapException('Invalid Field Format', '400'.$service.'01');
        }
    }

    private function invoice(array $payload, string $service): TagihanJasa
    {
        $tagihan = TagihanJasa::where('nomor_va', trim($payload['virtualAccountNo']))->lockForUpdate()->first();
        $data = $tagihan?->btn_va_data;
        if (! $tagihan || ! $data || ($data['environment'] ?? '') !== $this->config->fingerprint()
            || trim($payload['partnerServiceId']) !== trim($this->config->partnerServiceId())
            || ($data['request']['customerNo'] ?? '') !== $payload['customerNo']
            || trim($data['request']['virtualAccountNo'] ?? '') !== trim($payload['virtualAccountNo'])
            || ($service === '25' && ($data['request']['trxId'] ?? '') !== $payload['trxId'])) {
            throw new BtnSnapException('Invalid Bill/Virtual Account', '404'.$service.'12');
        }

        return $tagihan;
    }

    private function assertPayable(TagihanJasa $tagihan, string $service, bool $checkExpiry = true): void
    {
        if (in_array($tagihan->btn_va_data['state'] ?? '', ['creating', 'updating', 'deleting'], true)) {
            throw new BtnSnapException('VA operation pending; retry later', '500'.$service.'00');
        }
        if ($tagihan->status === 'LUNAS' || $tagihan->status_pembayaran === 'lunas') {
            throw new BtnSnapException('Paid Bill', '404'.$service.'14');
        }
        if ($tagihan->status !== 'PUBLISHED' || ($tagihan->btn_va_data['state'] ?? '') !== 'active') {
            throw new BtnSnapException('Invalid Transaction Status', '404'.$service.'00');
        }
        if ($checkExpiry && Carbon::parse($tagihan->btn_va_data['request']['expiredDate'])->isPast()) {
            throw new BtnSnapException('Transaction Expired', '403'.$service.'00');
        }
    }

    private function outstanding(TagihanJasa $tagihan): int
    {
        // Use the bank's confirmed bill snapshot; a local penalty is not yet a bank-side VA update.
        return max(0, BtnSnapMoney::cents($tagihan->btn_va_data['request']['totalAmount']['value'])
            - BtnSnapMoney::cents((string) $tagihan->jumlah_dibayar));
    }
}
