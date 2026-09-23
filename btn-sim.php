<?php
/**
 * Simulator BTN untuk menguji endpoint masuk SNAP (B.3 Inquiry & Payment).
 * Skrip ini BERPERAN SEBAGAI BANK: memegang private key "BTN", menandatangani
 * request, lalu memanggil endpoint aplikasi lewat HTTP sungguhan.
 *
 * Jalankan dari root project:
 *   php btn-sim.php setup
 *   php btn-sim.php seed <tagihanId>
 *   php btn-sim.php inquiry <tagihanId>
 *   php btn-sim.php pay <tagihanId> [nominal] [paymentRequestId]
 *
 * URL aplikasi diambil dari env BTN_SIM_URL (default http://127.0.0.1:8321).
 */

use App\Models\IntegrationSetting;
use App\Models\TagihanJasa;
use App\Services\Btn\BtnSnapConfig;
use Illuminate\Support\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

const KEY_DIR = __DIR__ . '/storage/app/btn-sim';
const APP_URL = 'http://127.0.0.1:8321';
const INBOUND_PARTNER_ID = 'btn-sim-partner';
const PARTNER_SERVICE_ID = '93333';
const CHANNEL_ID = '00001';

$command = $argv[1] ?? 'help';
$baseUrl = rtrim(getenv('BTN_SIM_URL') ?: APP_URL, '/');

function keypair(): array
{
    if (! is_dir(KEY_DIR)) {
        mkdir(KEY_DIR, 0700, true);
    }
    $privatePath = KEY_DIR . '/bank-private.pem';
    if (! is_file($privatePath)) {
        // PHP di Windows kerap tidak menemukan openssl.cnf bawaan; pakai fixture
        // minimal yang sama dengan yang dipakai BtnSnapTest.
        $options = [
            'config' => __DIR__ . '/tests/Fixtures/btn-openssl.cnf',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $key = openssl_pkey_new($options);
        if (! $key) {
            fwrite(STDERR, "Gagal membuat key RSA.\n");
            while ($error = openssl_error_string()) {
                fwrite(STDERR, '  openssl: ' . $error . "\n");
            }
            exit(1);
        }
        openssl_pkey_export($key, $private, null, $options);
        file_put_contents($privatePath, $private);
        file_put_contents(KEY_DIR . '/bank-public.pem', openssl_pkey_get_details($key)['key']);
        echo 'Keypair simulator dibuat di ' . KEY_DIR . "\n";
    }

    return [file_get_contents($privatePath), file_get_contents(KEY_DIR . '/bank-public.pem')];
}

function wib(): string
{
    return Carbon::now('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
}

/** Kirim request tertanda ke endpoint aplikasi, persis seperti BTN akan melakukannya. */
function send(string $baseUrl, string $path, array $payload): void
{
    [$privateKey] = keypair();
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $timestamp = wib();
    // stringToSign tanpa token: METHOD:path:sha256(minify(body)):timestamp
    $stringToSign = 'POST:' . $path . ':' . hash('sha256', $body) . ':' . $timestamp;
    openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    $handle = curl_init($baseUrl . $path);
    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-TIMESTAMP: ' . $timestamp,
            'X-PARTNER-ID: ' . INBOUND_PARTNER_ID,
            'X-EXTERNAL-ID: ' . substr(strtoupper(bin2hex(random_bytes(8))), 0, 16),
            'CHANNEL-ID: ' . CHANNEL_ID,
            'X-SIGNATURE: ' . base64_encode($signature),
        ],
    ]);
    $response = curl_exec($handle);
    if ($response === false) {
        fwrite(STDERR, 'curl gagal: ' . curl_error($handle) . "\n");
        exit(1);
    }
    $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    echo "--> POST {$path}\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    echo "<-- HTTP {$status}\n";
    $decoded = json_decode($response, true);
    echo ($decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : substr($response, 0, 2000)) . "\n";
}

function invoice(string $id): TagihanJasa
{
    $tagihan = TagihanJasa::find($id);
    if (! $tagihan) {
        fwrite(STDERR, "Tagihan {$id} tidak ditemukan.\n");
        exit(1);
    }

    return $tagihan;
}

switch ($command) {
    case 'setup':
        [, $publicKey] = keypair();
        $existing = IntegrationSetting::getValue('btn.bank_public_key', '');
        if (filled($existing) && trim($existing) !== trim($publicKey)) {
            fwrite(STDERR, "btn.bank_public_key sudah berisi nilai lain. Dihentikan agar kredensial asli tidak tertimpa.\n");
            exit(1);
        }
        foreach ([
            'enabled' => [true, 'boolean'],
            'mode' => ['sandbox', 'text'],
            'base_url' => ['https://btn-sim.invalid', 'text'],
            'partner_id' => ['btn-sim-apikey', 'text'],
            'partner_service_id' => [PARTNER_SERVICE_ID, 'text'],
            'channel_id' => [CHANNEL_ID, 'text'],
            'origin' => ['localhost', 'text'],
            'trx_type' => ['F', 'text'],
            'inbound_auth' => ['rsa', 'text'],
            'inbound_partner_id' => [INBOUND_PARTNER_ID, 'text'],
            'bank_public_key' => [$publicKey, 'text'],
        ] as $key => [$value, $type]) {
            IntegrationSetting::setValue('btn.' . $key, $value, 'btn', null, $type, $key === 'bank_public_key');
        }
        echo 'Pengaturan simulator tersimpan. Fingerprint environment: ' . app(BtnSnapConfig::class)->fingerprint() . "\n";
        echo "base_url diarahkan ke host tidak valid supaya tidak ada panggilan keluar ke bank.\n";
        break;

    case 'seed':
        $tagihan = invoice($argv[2] ?? '');
        $config = app(BtnSnapConfig::class);
        $customer = str_pad((string) $tagihan->id, 14, '0', STR_PAD_LEFT);
        // Snapshot yang normalnya ditulis oleh Create VA sungguhan.
        $data = [
            'partnerServiceId' => str_pad(PARTNER_SERVICE_ID, 8, ' ', STR_PAD_LEFT),
            'customerNo' => $customer,
            'virtualAccountNo' => PARTNER_SERVICE_ID . $customer,
            'virtualAccountName' => substr((string) ($tagihan->mitra->nama_pihak ?? 'Mitra'), 0, 30),
            'trxId' => 'BTN' . str_pad((string) $tagihan->id, 16, '0', STR_PAD_LEFT),
            'totalAmount' => ['value' => number_format((float) $tagihan->total_dengan_denda, 2, '.', ''), 'currency' => 'IDR'],
            'virtualAccountTrxType' => 'F',
            'expiredDate' => Carbon::now('Asia/Jakarta')->addDays(30)->format('Y-m-d\TH:i:sP'),
        ];
        $tagihan->update([
            'nomor_va' => $data['virtualAccountNo'],
            'va_reference' => $data['trxId'],
            'va_provider' => 'btn',
            'va_expired_at' => $data['expiredDate'],
            'btn_va_data' => ['state' => 'active', 'environment' => $config->fingerprint(), 'request' => $data],
        ]);
        echo "Tagihan {$tagihan->id} ({$tagihan->nomor_tagihan}) disiapkan sebagai VA aktif.\n";
        echo "VA: {$data['virtualAccountNo']}  trxId: {$data['trxId']}  tagihan: {$data['totalAmount']['value']}\n";
        echo "status={$tagihan->status} status_pembayaran={$tagihan->status_pembayaran}\n";
        break;

    case 'inquiry':
        $tagihan = invoice($argv[2] ?? '');
        $stored = $tagihan->btn_va_data['request'] ?? null;
        if (! $stored) {
            fwrite(STDERR, "Tagihan belum punya btn_va_data. Jalankan: php btn-sim.php seed {$tagihan->id}\n");
            exit(1);
        }
        send($baseUrl, '/snap/v1/transfer-va/inquiry', [
            'partnerServiceId' => $stored['partnerServiceId'],
            'customerNo' => $stored['customerNo'],
            'virtualAccountNo' => $stored['virtualAccountNo'],
            'inquiryRequestId' => 'INQ' . Carbon::now()->format('YmdHis'),
        ]);
        break;

    case 'pay':
        $tagihan = invoice($argv[2] ?? '');
        $stored = $tagihan->btn_va_data['request'] ?? null;
        if (! $stored) {
            fwrite(STDERR, "Tagihan belum punya btn_va_data. Jalankan: php btn-sim.php seed {$tagihan->id}\n");
            exit(1);
        }
        // Sisa menurut snapshot bank, dalam sen, mengikuti perhitungan controller.
        $outstandingCents = (int) round(((float) $stored['totalAmount']['value'] - (float) $tagihan->jumlah_dibayar) * 100);
        $outstanding = number_format(max(0, $outstandingCents) / 100, 2, '.', '');
        $amount = $argv[3] ?? $outstanding;
        $requestId = $argv[4] ?? 'PAY' . Carbon::now()->format('YmdHis');
        echo "Sisa menurut snapshot bank: {$outstanding}\n\n";
        send($baseUrl, '/snap/v1/transfer-va/payment', [
            'partnerServiceId' => $stored['partnerServiceId'],
            'customerNo' => $stored['customerNo'],
            'virtualAccountNo' => $stored['virtualAccountNo'],
            'trxId' => $stored['trxId'],
            'paymentRequestId' => $requestId,
            'referenceNo' => 'REF' . $requestId,
            'channelCode' => '6017',
            'hashedSourceAccountNo' => '*************617679',
            'sourceBankCode' => '200',
            'paidAmount' => ['value' => $amount, 'currency' => 'IDR'],
            'totalAmount' => ['value' => $outstanding, 'currency' => 'IDR'],
            'trxDateTime' => wib(),
            'flagAdvise' => 'N',
            'billDetails' => [['billName' => substr($stored['virtualAccountName'], 0, 20)]],
            'freeTexts' => [],
        ]);
        $fresh = $tagihan->fresh();
        echo "\nSetelah callback: status={$fresh->status} status_pembayaran={$fresh->status_pembayaran} ";
        echo "dibayar={$fresh->jumlah_dibayar} sisa={$fresh->sisa_tagihan}\n";
        break;

    default:
        echo "Perintah: setup | seed <tagihanId> | inquiry <tagihanId> | pay <tagihanId> [nominal] [paymentRequestId]\n";
}
