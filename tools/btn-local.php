<?php

// Local-only Postman harness. It never loads the main .env or database.
use App\Models\BukuKasUmum;
use App\Models\IntegrationSetting;
use App\Models\MasterCoa;
use App\Models\MasterPihak;
use App\Models\PaymentTransaction;
use App\Models\RekeningBank;
use App\Models\TagihanJasa;
use App\Models\User;
use App\Services\Btn\BtnSnapConfig;
use App\Services\Btn\BtnSnapSignature;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
$server = PHP_SAPI === 'cli-server';
if (PHP_SAPI !== 'cli' && ! $server) {
    http_response_code(404);
    exit;
}
if ($server && (! in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || ($_SERVER['HTTP_HOST'] ?? '') !== '127.0.0.1:8091'
    || ! in_array(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), ['/snap/v1/transfer-va/inquiry', '/snap/v1/transfer-va/payment'], true))) {
    http_response_code(404);
    exit('Local BTN API only.');
}
$root = dirname(__DIR__);
$dir = $root.'/storage/app/btn-local';
$action = $server ? 'http' : ($argv[1] ?? 'help');
if ($action === 'help') {
    echo "php tools/btn-local.php init\nphp tools/btn-local.php new\nphp tools/btn-local.php request inquiry|payment|invalid\nphp tools/btn-local.php state\n";
    exit;
}
if ($action === 'init' && ! is_dir($dir)) {
    mkdir($dir, 0700, true);
}
if (! is_dir($dir)) {
    exit("Jalankan php tools/btn-local.php init terlebih dahulu.\n");
}
if ($action === 'init' && ! is_file($dir.'/app-key')) {
    file_put_contents($dir.'/app-key', 'base64:'.base64_encode(random_bytes(32)));
    file_put_contents($dir.'/testing.env', "# Isolated local BTN testing only\n");
    touch($dir.'/database.sqlite');
}
if (! is_file($dir.'/app-key') || ! is_file($dir.'/database.sqlite')) {
    exit("Database/key lokal belum tersedia. Jalankan init.\n");
}
if (is_file($dir.'/unused-config-cache.php')) {
    exit("Cache konfigurasi helper tidak boleh dibuat.\n");
}
foreach ([
    'APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8091',
    'APP_KEY' => trim(file_get_contents($dir.'/app-key')),
    'APP_CONFIG_CACHE' => $dir.'/unused-config-cache.php',
    'APP_ROUTES_CACHE' => $dir.'/unused-routes-cache.php',
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $dir.'/database.sqlite', 'DB_URL' => '',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array', 'LOG_CHANNEL' => 'single', 'VIEW_COMPILED_PATH' => $dir.'/views',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
if (! is_dir($dir.'/views')) {
    mkdir($dir.'/views', 0700);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->useEnvironmentPath($dir)->loadEnvironmentFrom('testing.env');
$app->make($server ? Illuminate\Contracts\Http\Kernel::class : Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['logging.channels.single.path' => $dir.'/testing.log']);
// The real HTTP client must not contact any bank or notification gateway here.
Http::preventStrayRequests();
if ($server) {
    $app->handleRequest(Illuminate\Http\Request::capture());
    exit;
}
try {
    if ($action === 'init') {
        if (Artisan::call('migrate', ['--force' => true]) !== 0) {
            throw new RuntimeException(Artisan::output());
        }
        if (! is_file($dir.'/bank-private.pem')) {
            $options = ['config' => $root.'/tests/Fixtures/btn-openssl.cnf', 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
            $key = openssl_pkey_new($options);
            if (! $key || ! openssl_pkey_export($key, $private, null, $options)) {
                throw new RuntimeException('Gagal membuat key RSA pengujian.');
            }
            file_put_contents($dir.'/bank-private.pem', $private);
        }
        $private = file_get_contents($dir.'/bank-private.pem');
        $public = openssl_pkey_get_details(openssl_pkey_get_private($private))['key'];
        foreach ([
            'btn.enabled' => true, 'btn.mode' => 'sandbox', 'btn.base_url' => 'https://btn.invalid',
            'btn.partner_id' => 'local-application', 'btn.partner_service_id' => '93333',
            'btn.inbound_auth' => 'rsa', 'btn.inbound_partner_id' => 'local-bank', 'btn.bank_public_key' => $public,
            'whatsapp.lunas.enabled' => false, 'email.lunas.enabled' => false,
        ] as $key => $value) {
            IntegrationSetting::setValue($key, $value, 'btn-local-test', null, is_bool($value) ? 'boolean' : 'text');
        }
        echo "Database terpisah siap: {$dir}/database.sqlite\n";
        $action = is_file($dir.'/fixture.json') ? 'state' : 'new';
    }
    if ($action === 'new') {
        $fixture = DB::transaction(function () {
            $user = User::where('email', 'btn-local@example.invalid')->first()
                ?? User::factory()->create(['email' => 'btn-local@example.invalid', 'password' => bcrypt(bin2hex(random_bytes(16)))]);
            $pihak = MasterPihak::firstOrCreate(['nama_pihak' => 'MITRA TES LOKAL BTN'], ['kategori' => 'PENERIMAAN']);
            MasterCoa::firstOrCreate(['kd_akun' => '424312'], ['nama_akun' => 'PENERIMAAN PNBP']);
            RekeningBank::firstOrCreate(['nomor_rekening' => '0000000000000001'], [
                'pemilik_type' => User::class, 'pemilik_id' => $user->id, 'nama_bank' => 'BTN TEST',
                'nama_rekening' => 'REKENING TES', 'jenis_rekening' => 'PENERIMAAN', 'saldo_awal' => 0,
                'is_default' => true, 'status_aktif' => true,
            ]);
            $invoice = TagihanJasa::create([
                'mitra_id' => $pihak->id, 'created_by' => $user->id, 'nomor_tagihan' => 'BTN-LOCAL-'.bin2hex(random_bytes(6)),
                'tanggal_tagihan' => now(), 'tanggal_jatuh_tempo' => now()->addDays(30),
                'total_tagihan' => '100000.00', 'jumlah_dibayar' => '0.00', 'sisa_tagihan' => '100000.00',
                'status' => 'PUBLISHED', 'status_pembayaran' => 'belum_dibayar',
            ]);
            $customer = str_pad((string) $invoice->id, 14, '0', STR_PAD_LEFT);
            $request = [
                'partnerServiceId' => '   93333', 'customerNo' => $customer, 'virtualAccountNo' => '93333'.$customer,
                'virtualAccountName' => 'MITRA TES LOKAL BTN', 'trxId' => 'BTN'.str_pad((string) $invoice->id, 16, '0', STR_PAD_LEFT),
                'totalAmount' => ['value' => '100000.00', 'currency' => 'IDR'], 'virtualAccountTrxType' => 'F',
                'expiredDate' => now('Asia/Jakarta')->addDays(30)->format('Y-m-d\TH:i:sP'),
            ];
            $invoice->update([
                'nomor_va' => $request['virtualAccountNo'], 'va_reference' => $request['trxId'], 'va_provider' => 'btn',
                'va_expired_at' => $request['expiredDate'],
                'btn_va_data' => ['state' => 'active', 'environment' => app(BtnSnapConfig::class)->fingerprint(), 'request' => $request],
            ]);

            return ['id' => $invoice->id, 'paid_at' => now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')];
        });
        file_put_contents($dir.'/fixture.json', json_encode($fixture, JSON_THROW_ON_ERROR));
        $action = 'state';
    }
    $fixture = json_decode(file_get_contents($dir.'/fixture.json'), true, 512, JSON_THROW_ON_ERROR);
    $invoice = TagihanJasa::findOrFail($fixture['id']);
    if ($action === 'state') {
        echo json_encode([
            'database' => 'ISOLATED BTN LOCAL TEST', 'tagihan' => $invoice->nomor_tagihan, 'va' => $invoice->nomor_va,
            'status' => $invoice->status, 'jumlah_dibayar' => $invoice->jumlah_dibayar, 'sisa_tagihan' => $invoice->sisa_tagihan,
            'jumlah_transaksi_tagihan' => PaymentTransaction::where('tagihan_jasa_id', $invoice->id)->count(),
            'jumlah_bku_tagihan' => BukuKasUmum::whereHas('referensiPenerimaan', fn ($q) => $q->where('nomor_invoice', $invoice->nomor_tagihan))->count(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
    } elseif ($action === 'request') {
        $kind = $argv[2] ?? 'inquiry';
        if (! in_array($kind, ['inquiry', 'payment', 'invalid'], true)) {
            throw new RuntimeException('Pilih inquiry, payment, atau invalid.');
        }
        $path = '/snap/v1/transfer-va/'.($kind === 'inquiry' ? 'inquiry' : 'payment');
        $payload = array_intersect_key($invoice->btn_va_data['request'], array_flip(['partnerServiceId', 'customerNo', 'virtualAccountNo', 'trxId']));
        $payload += $kind === 'inquiry' ? ['inquiryRequestId' => 'LOCALINQ'.$invoice->id] : [
            'paymentRequestId' => 'LOCALPAY'.$invoice->id, 'referenceNo' => 'LOCALREF'.$invoice->id,
            'paidAmount' => ['value' => '100000.00', 'currency' => 'IDR'],
            'totalAmount' => ['value' => '100000.00', 'currency' => 'IDR'],
            'trxDateTime' => $fixture['paid_at'], 'channelCode' => '6017', 'flagAdvise' => 'N',
        ];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $time = now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $signer = app(BtnSnapSignature::class);
        $signature = $signer->rsa($signer->stringToSign('POST', $path, $body, $time), file_get_contents($dir.'/bank-private.pem'));
        $headers = [
            'Content-Type' => 'application/json', 'X-TIMESTAMP' => $time, 'X-PARTNER-ID' => 'local-bank',
            'X-EXTERNAL-ID' => strtoupper(bin2hex(random_bytes(8))), 'CHANNEL-ID' => '00001',
            'X-SIGNATURE' => $kind === 'invalid' ? base64_encode('invalid-signature') : $signature,
        ];
        $curl = "curl --request POST 'http://127.0.0.1:8091{$path}'";
        foreach ($headers as $key => $value) {
            $curl .= " --header '{$key}: {$value}'";
        }
        $curl .= " --data-raw '{$body}'";
        // cURL text is for Postman import, not execution by PowerShell's curl alias.
        echo $curl."\n";
    } else {
        throw new RuntimeException('Perintah tidak dikenal. Gunakan init, new, request, atau state.');
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
