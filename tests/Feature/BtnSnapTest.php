<?php

namespace Tests\Feature;

use App\Models\BukuKasUmum;
use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\PaymentTransaction;
use App\Models\RekeningBank;
use App\Models\TagihanJasa;
use App\Models\TransaksiPenerimaan;
use App\Models\User;
use App\Services\Btn\BtnSnapClient;
use App\Services\Btn\BtnSnapConfig;
use App\Services\Btn\BtnSnapException;
use App\Services\Btn\BtnSnapSettings;
use App\Services\Btn\BtnSnapSignature;
use App\Services\Btn\BtnSnapVirtualAccount;
use App\Services\BtnVirtualAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BtnSnapTest extends TestCase
{
    use RefreshDatabase;

    private static string $privateKey;

    private static string $publicKey;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(10, 0));
        if (! isset(self::$privateKey)) {
            $options = ['config' => base_path('tests/Fixtures/btn-openssl.cnf'), 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
            $key = openssl_pkey_new($options);
            openssl_pkey_export($key, $privateKey, null, $options);
            self::$privateKey = $privateKey;
            self::$publicKey = openssl_pkey_get_details($key)['key'];
        }
        foreach ([
            'enabled' => true, 'mode' => 'sandbox', 'base_url' => 'https://btn.test',
            'client_id' => 'oauth-test', 'partner_id' => 'api-test', 'client_secret' => 'secret-test',
            'private_key' => self::$privateKey, 'bank_public_key' => self::$publicKey,
            'partner_service_id' => '93333', 'channel_id' => '00001', 'origin' => 'app.test',
            'inbound_partner_id' => 'bank-test', 'inbound_auth' => 'rsa', 'trx_type' => 'F',
        ] as $key => $value) {
            IntegrationSetting::setValue('btn.'.$key, $value, 'btn', null, is_bool($value) ? 'boolean' : 'text');
        }
        IntegrationSetting::setValue('whatsapp.lunas.enabled', false, 'whatsapp', null, 'boolean');
        IntegrationSetting::setValue('email.lunas.enabled', false, 'email', null, 'boolean');
    }

    private function invoice(bool $active = true, string $type = 'F'): TagihanJasa
    {
        $user = User::factory()->create();
        $pihak = MasterPihak::create(['kategori' => 'PENERIMAAN', 'nama_pihak' => 'Mitra BTN']);
        $tagihan = TagihanJasa::create([
            'mitra_id' => $pihak->id, 'created_by' => $user->id, 'nomor_tagihan' => 'TEST/'.$pihak->id,
            'tanggal_tagihan' => now(), 'tanggal_jatuh_tempo' => now()->addDays(30),
            'total_tagihan' => '100000.00', 'jumlah_dibayar' => '0.00', 'sisa_tagihan' => '100000.00',
            'status' => $active ? 'PUBLISHED' : 'DRAFT', 'status_pembayaran' => 'belum_dibayar',
        ]);
        if ($active) {
            $customer = str_pad((string) $tagihan->id, 14, '0', STR_PAD_LEFT);
            $data = [
                'partnerServiceId' => '   93333', 'customerNo' => $customer, 'virtualAccountNo' => '93333'.$customer,
                'virtualAccountName' => 'Mitra BTN', 'trxId' => 'BTN'.str_pad((string) $tagihan->id, 16, '0', STR_PAD_LEFT),
                'totalAmount' => ['value' => '100000.00', 'currency' => 'IDR'], 'virtualAccountTrxType' => $type,
                'expiredDate' => now('Asia/Jakarta')->addDays(30)->format('Y-m-d\TH:i:sP'),
            ];
            $tagihan->update([
                'nomor_va' => $data['virtualAccountNo'], 'va_reference' => $data['trxId'], 'va_provider' => 'btn',
                'va_expired_at' => $data['expiredDate'],
                'btn_va_data' => ['state' => 'active', 'environment' => app(BtnSnapConfig::class)->fingerprint(), 'request' => $data],
            ]);
        }

        return $tagihan;
    }

    private function bookkeeping(): void
    {
        MasterCoa::create(['nama_akun' => 'PENERIMAAN PNBP', 'kd_akun' => '424312']);
        RekeningBank::create([
            'pemilik_type' => User::class, 'pemilik_id' => 1, 'nama_bank' => 'BTN', 'nomor_rekening' => '12345678',
            'nama_rekening' => 'Penerimaan', 'jenis_rekening' => 'PENERIMAAN', 'saldo_awal' => 0,
            'is_default' => true, 'status_aktif' => true,
        ]);
    }

    private function payment(TagihanJasa $tagihan, string $amount = '100000.00', string $id = 'PAY001', string $total = '100000.00'): array
    {
        return array_intersect_key($tagihan->btn_va_data['request'], array_flip(['partnerServiceId', 'customerNo', 'virtualAccountNo', 'trxId'])) + [
            'paymentRequestId' => $id, 'referenceNo' => 'REF'.$id, 'channelCode' => '6017',
            'paidAmount' => ['value' => $amount, 'currency' => 'IDR'],
            'totalAmount' => ['value' => $total, 'currency' => 'IDR'],
            'trxDateTime' => now('Asia/Jakarta')->format('Y-m-d\TH:i:sP'), 'flagAdvise' => 'N',
        ];
    }

    private function signed(string $path, array $payload, array $headers = [])
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $time = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $value = 'POST:'.$path.':'.hash('sha256', $body).':'.$time;
        openssl_sign($value, $signature, self::$privateKey, OPENSSL_ALGO_SHA256);

        return $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars($headers + [
            'Content-Type' => 'application/json', 'X-PARTNER-ID' => 'bank-test', 'X-TIMESTAMP' => $time,
            'X-SIGNATURE' => base64_encode($signature), 'X-EXTERNAL-ID' => 'REQ0000000000001', 'CHANNEL-ID' => '00001',
        ]), $body);
    }

    private function fakeBank(?callable $callback = null): void
    {
        Http::fake(function ($request) use ($callback) {
            if (str_ends_with($request->url(), '/access-token/b2b')) {
                return Http::response(['responseCode' => '2007300', 'accessToken' => 'token-test', 'tokenType' => 'Bearer', 'expiresIn' => '900']);
            }
            if ($callback) {
                return $callback($request);
            }

            return Http::response(['responseCode' => '2002700', 'virtualAccountData' => $request->data()]);
        });
    }

    public function test_create_uses_snap_headers_signatures_and_caches_token_without_logging_secrets(): void
    {
        $this->fakeBank();
        $tagihan = $this->invoice(false);
        $result = app(BtnVirtualAccountService::class)->createVirtualAccount($tagihan);
        $this->assertSame('sandbox', $result['mode']);
        $this->assertSame(19, strlen($result['number']));
        $this->assertSame('active', $tagihan->fresh()->btn_va_data['state']);
        app(BtnVirtualAccountService::class)->createVirtualAccount($tagihan);
        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            if (! str_ends_with($request->url(), 'create-va')) {
                return false;
            }
            $time = $request->header('X-TIMESTAMP')[0];
            $expected = base64_encode(hash_hmac('sha512', 'POST:/snap/v1/transfer-va/create-va:token-test:'.hash('sha256', $request->body()).':'.$time, 'secret-test', true));

            return $request->header('X-SIGNATURE')[0] === $expected
                && $request->header('X-PARTNER-ID')[0] === 'api-test'
                && preg_match('/^[A-Z0-9]{16}$/D', $request->header('X-EXTERNAL-ID')[0])
                && $request['partnerServiceId'] === '   93333' && $request['totalAmount']['value'] === '100000.00';
        });
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/b2b')
            && openssl_verify('oauth-test|'.$request->header('X-TIMESTAMP')[0], base64_decode($request->header('X-SIGNATURE')[0]), self::$publicKey, OPENSSL_ALGO_SHA256) === 1);
        app(BtnSnapClient::class)->token();
        Http::assertSentCount(2);
        $logs = IntegrationLog::all()->toJson();
        foreach (['token-test', 'secret-test', 'PRIVATE KEY', 'Mitra BTN'] as $secret) {
            $this->assertStringNotContainsString($secret, $logs);
        }
    }

    public function test_production_missing_credentials_never_falls_back_to_mock(): void
    {
        IntegrationSetting::setValue('btn.mode', 'production', 'btn');
        IntegrationSetting::setValue('btn.private_key', null, 'btn');
        $tagihan = $this->invoice(false);
        try {
            app(BtnVirtualAccountService::class)->createVirtualAccount($tagihan);
            $this->fail('Expected configuration error');
        } catch (BtnSnapException $e) {
            $this->assertStringContainsString('private_key', $e->getMessage());
        }
        Http::assertNothingSent();
        $this->assertNull($tagihan->fresh()->nomor_va);
    }

    public function test_timeout_preserves_identity_and_next_attempt_inquires_without_duplicate_create(): void
    {
        $tagihan = $this->invoice(false);
        $this->fakeBank(function ($request) use ($tagihan) {
            if (str_ends_with($request->url(), 'create-va')) {
                throw new ConnectionException('timeout');
            }

            return Http::response(['responseCode' => '2003000', 'virtualAccountData' => $tagihan->fresh()->btn_va_data['request']]);
        });
        try {
            app(BtnSnapVirtualAccount::class)->create($tagihan);
            $this->fail('Expected timeout');
        } catch (BtnSnapException) {
            $this->assertSame('creating', $tagihan->fresh()->btn_va_data['state']);
        }
        $stored = $tagihan->fresh()->btn_va_data['request'];
        app(BtnSnapVirtualAccount::class)->create($tagihan);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), 'inquiry-va') && $request['trxId'] === $stored['trxId']);
        $this->assertSame('active', $tagihan->fresh()->btn_va_data['state']);
    }

    public function test_wrong_bank_response_is_not_published_as_valid_va(): void
    {
        $this->fakeBank(fn ($request) => Http::response(['responseCode' => '2002700', 'virtualAccountData' => array_merge($request->data(), ['virtualAccountNo' => '999'])]));
        $tagihan = $this->invoice(false);
        try {
            app(BtnSnapVirtualAccount::class)->create($tagihan);
            $this->fail('Expected mismatch');
        } catch (BtnSnapException) {
            $this->assertNull($tagihan->fresh()->nomor_va);
        }
    }

    public function test_inquiry_returns_bill_and_rejects_unknown_customer(): void
    {
        $tagihan = $this->invoice();
        $payload = $this->payment($tagihan) + ['inquiryRequestId' => 'INQ001'];
        $this->signed('/snap/v1/transfer-va/inquiry', $payload)->assertOk()->assertJsonPath('responseCode', '2002400')
            ->assertJsonPath('virtualAccountData.totalAmount.value', '100000.00');
        $payload['customerNo'] = '999';
        $this->signed('/snap/v1/transfer-va/inquiry', $payload)->assertStatus(404)->assertJsonPath('responseCode', '4042412');
    }

    public function test_payment_and_duplicate_credit_invoice_and_bookkeeping_exactly_once(): void
    {
        $this->bookkeeping();
        $tagihan = $this->invoice();
        $payload = $this->payment($tagihan);
        $first = $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk()->assertJsonPath('responseCode', '2002500');
        $second = $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk();
        $this->assertEquals($first->json(), $second->json());
        $this->assertSame('LUNAS', $tagihan->fresh()->status);
        $this->assertSame('100000.00', $tagihan->fresh()->jumlah_dibayar);
        $this->assertSame(1, PaymentTransaction::count());
        $this->assertSame(1, BukuKasUmum::count());
        $this->assertEquals(100000, BukuKasUmum::first()->nominal);
    }

    public function test_partial_retries_are_idempotent_and_piutang_and_final_bku_use_cumulative_amount(): void
    {
        $this->bookkeeping();
        $tagihan = $this->invoice(true, 'P');
        $first = $this->payment($tagihan, '30000.00');
        $this->signed('/snap/v1/transfer-va/payment', $first)->assertOk();
        $this->signed('/snap/v1/transfer-va/payment', $first)->assertOk();
        $this->signed('/snap/v1/transfer-va/payment', $this->payment($tagihan, '20000.00', 'PAY002', '70000.00'))->assertOk();
        $this->assertSame('50000.00', $tagihan->fresh()->jumlah_dibayar);
        $this->assertEquals(50000, TransaksiPenerimaan::first()->total_dibayar);
        $this->signed('/snap/v1/transfer-va/payment', $this->payment($tagihan, '50000.00', 'PAY003', '50000.00'))->assertOk();
        $this->assertEquals(100000, BukuKasUmum::first()->nominal);
        $this->assertEquals(100000, TransaksiPenerimaan::first()->total_dibayar);
        $this->assertSame(3, PaymentTransaction::count());
    }

    public function test_bookkeeping_failure_rolls_back_credit_and_allows_retry(): void
    {
        $tagihan = $this->invoice();
        $payload = $this->payment($tagihan);
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertStatus(500)->assertJsonPath('responseCode', '5002500');
        $this->assertSame('PUBLISHED', $tagihan->fresh()->status);
        $this->assertSame(0, PaymentTransaction::count());
        $this->bookkeeping();
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk();
        $this->assertSame(1, BukuKasUmum::count());
    }

    public function test_forged_signature_stale_timestamp_and_wrong_partner_do_not_credit(): void
    {
        $payload = $this->payment($this->invoice());
        foreach ([['X-SIGNATURE' => 'invalid'], ['X-PARTNER-ID' => 'wrong'], ['X-TIMESTAMP' => now('Asia/Jakarta')->subMinutes(3)->format('Y-m-d\TH:i:sP')]] as $headers) {
            $this->signed('/snap/v1/transfer-va/payment', $payload, $headers)->assertStatus(401);
        }
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_invalid_currency_negative_missing_amount_and_underpaid_full_va_are_rejected(): void
    {
        $payload = $this->payment($this->invoice());
        $bad = $payload;
        $bad['paidAmount']['currency'] = 'USD';
        $this->signed('/snap/v1/transfer-va/payment', $bad)->assertStatus(400);
        $bad['paidAmount'] = ['value' => '-1.00', 'currency' => 'IDR'];
        $this->signed('/snap/v1/transfer-va/payment', $bad)->assertStatus(400);
        unset($bad['paidAmount']);
        $this->signed('/snap/v1/transfer-va/payment', $bad)->assertStatus(400)->assertJsonPath('responseCode', '4002502');
        $payload['paidAmount']['value'] = '50000.00';
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertStatus(404)->assertJsonPath('responseCode', '4042513');
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_changed_duplicate_does_not_overwrite_payment(): void
    {
        $this->bookkeeping();
        $payload = $this->payment($this->invoice(true, 'P'), '30000.00');
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk();
        $payload['paidAmount']['value'] = '20000.00';
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertStatus(409);
        $this->assertSame('30000.00', PaymentTransaction::first()->amount);
    }

    public function test_legacy_callback_cannot_bypass_snap_in_sandbox(): void
    {
        IntegrationSetting::setValue('btn.callback_secret', 'legacy-secret', 'btn');
        $this->postJson('/integrations/btn/virtual-account/callback', ['callback_secret' => 'legacy-secret'])->assertForbidden();
    }

    public function test_disabled_inbound_and_environment_changes_are_rejected(): void
    {
        $payload = $this->payment($this->invoice());
        IntegrationSetting::setValue('btn.mode', 'production', 'btn');
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertStatus(404);
        IntegrationSetting::setValue('btn.inbound_auth', 'disabled', 'btn');
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertStatus(503);
    }

    public function test_keys_are_encrypted_and_not_returned_to_settings_form(): void
    {
        BtnSnapSettings::save(['btn_private_key' => self::$privateKey]);
        $this->assertStringNotContainsString('PRIVATE KEY', IntegrationSetting::where('key', 'btn.private_key')->value('value'));
        $this->assertTrue(BtnSnapSettings::values()['btn_private_key']);
        BtnSnapSettings::save(['btn_private_key' => '']);
        $this->assertSame(self::$privateKey, IntegrationSetting::getValue('btn.private_key'));
    }

    public function test_minification_preserves_whitespace_inside_strings_and_numeric_lexemes(): void
    {
        $json = "{ \n \"name\" : \"A B\", \"number\":1.00, \"path\":\"a\\/b\" }";
        $this->assertSame('{"name":"A B","number":1.00,"path":"a\\/b"}', app(BtnSnapSignature::class)->minify($json));
    }

    public function test_update_and_delete_need_explicit_execute_flag(): void
    {
        $tagihan = $this->invoice();
        $this->artisan('btn:va', ['action' => 'delete', '--tagihan' => $tagihan->id])->assertFailed();
        Http::assertNothingSent();
    }

    public function test_bank_business_error_with_http_200_is_not_success(): void
    {
        $this->fakeBank(fn () => Http::response(['responseCode' => '4002701', 'responseMessage' => 'Invalid Field Format']));
        $tagihan = $this->invoice(false);
        try {
            app(BtnSnapVirtualAccount::class)->create($tagihan);
            $this->fail('Expected business rejection');
        } catch (BtnSnapException $e) {
            $this->assertSame('4002701', $e->responseCode);
            $this->assertSame('create_rejected', $tagihan->fresh()->btn_va_data['state']);
            $this->assertNull($tagihan->fresh()->nomor_va);
        }
    }

    public function test_token_is_refreshed_before_expiration(): void
    {
        $this->fakeBank();
        app(BtnSnapClient::class)->token();
        $this->travel(13)->minutes();
        app(BtnSnapClient::class)->token();
        Http::assertSentCount(1);
        $this->travel(2)->minutes();
        app(BtnSnapClient::class)->token();
        Http::assertSentCount(2);
    }

    public function test_update_and_delete_use_original_identity_and_block_further_payment(): void
    {
        $this->fakeBank(fn ($request) => Http::response([
            'responseCode' => str_ends_with($request->url(), 'update-va') ? '2002800' : '2003100',
            'virtualAccountData' => $request->data(),
        ]));
        $tagihan = $this->invoice();
        $original = $tagihan->va_reference;
        $tagihan->update(['total_tagihan' => '150000.00']);
        app(BtnSnapVirtualAccount::class)->update($tagihan);
        $this->assertSame('150000.00', $tagihan->fresh()->btn_va_data['request']['totalAmount']['value']);
        $this->assertSame($original, $tagihan->fresh()->va_reference);
        app(BtnSnapVirtualAccount::class)->delete($tagihan);
        $this->assertSame('deleted', $tagihan->fresh()->btn_va_data['state']);
        $this->signed('/snap/v1/transfer-va/payment', $this->payment($tagihan, '150000.00', 'PAY001', '150000.00'))->assertStatus(404);
    }

    public function test_partial_va_cannot_be_updated_or_deleted_without_confirmed_bank_rules(): void
    {
        $tagihan = $this->invoice(true, 'P');
        $tagihan->update(['jumlah_dibayar' => '100.00']);
        $this->expectException(BtnSnapException::class);
        app(BtnSnapVirtualAccount::class)->update($tagihan);
    }

    public function test_report_is_read_only_and_uses_report_service_code(): void
    {
        $tagihan = $this->invoice();
        $this->fakeBank(fn () => Http::response(['responseCode' => '2003500', 'virtualAccountData' => [
            ['virtualAccountNo' => $tagihan->nomor_va, 'paidBills' => '100000', 'trxDateTime' => '20260907T030000Z'],
        ]]));
        $report = app(BtnSnapVirtualAccount::class)->report('2026-09-01', '2026-09-07');
        $this->assertSame('100000', $report['virtualAccountData'][0]['paidBills']);
        $this->assertSame(0, PaymentTransaction::count());
        $this->assertSame('PUBLISHED', $tagihan->fresh()->status);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/report') && $request['startDate'] === '2026-09-01');
    }

    public function test_payment_of_bank_snapshot_does_not_erase_new_local_penalty(): void
    {
        $this->bookkeeping();
        $tagihan = $this->invoice();
        $tagihan->update(['tanggal_jatuh_tempo' => now()->subDays(1)]);
        $this->signed('/snap/v1/transfer-va/payment', $this->payment($tagihan))->assertOk();
        $this->assertSame('PUBLISHED', $tagihan->fresh()->status);
        $this->assertSame('2000.00', $tagihan->fresh()->sisa_tagihan);
        $this->assertSame('partial', PaymentTransaction::first()->status);
    }

    public function test_expired_inquiry_is_rejected_but_delayed_payment_before_expiry_is_accepted(): void
    {
        $this->bookkeeping();
        $tagihan = $this->invoice();
        $stored = $tagihan->btn_va_data;
        $stored['request']['expiredDate'] = now('Asia/Jakarta')->subMinute()->format('Y-m-d\TH:i:sP');
        $tagihan->update(['btn_va_data' => $stored]);
        $payload = $this->payment($tagihan);
        $this->signed('/snap/v1/transfer-va/inquiry', $payload + ['inquiryRequestId' => 'INQ001'])->assertStatus(403);
        $payload['trxDateTime'] = now('Asia/Jakarta')->subMinutes(2)->format('Y-m-d\TH:i:sP');
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk();
    }

    public function test_settings_page_renders_without_exposing_keys_and_validation_does_not_flash_them(): void
    {
        Role::findOrCreate('Super Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $this->actingAs($user)->get('/jasa/integrasi')->assertOk()->assertSee('SNAP BTN v2.04')
            ->assertDontSee(self::$privateKey)->assertDontSee(self::$publicKey);
        $this->actingAs($user)->from('/jasa/integrasi')->put('/jasa/integrasi', [
            'btn_private_key' => self::$privateKey, 'btn_client_secret' => 'never-flash-this',
        ])->assertSessionHasErrors()->assertSessionMissing('_old_input.btn_private_key')->assertSessionMissing('_old_input.btn_client_secret');
    }

    public function test_pending_update_returns_retryable_error_without_credit(): void
    {
        $tagihan = $this->invoice();
        $stored = $tagihan->btn_va_data;
        $stored['state'] = 'updating';
        $tagihan->update(['btn_va_data' => $stored]);
        $this->signed('/snap/v1/transfer-va/payment', $this->payment($tagihan))->assertStatus(500)->assertJsonPath('responseCode', '5002500');
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_duplicate_with_equivalent_timestamp_and_json_key_order_remains_idempotent(): void
    {
        $this->bookkeeping();
        $payload = $this->payment($this->invoice());
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk();
        $payload['totalAmount'] = ['currency' => 'IDR', 'value' => '100000.00'];
        $payload['trxDateTime'] = now('UTC')->format('Y-m-d\TH:i:s\Z');
        $this->signed('/snap/v1/transfer-va/payment', $payload)->assertOk();
        $this->assertSame(1, PaymentTransaction::count());
    }
}
