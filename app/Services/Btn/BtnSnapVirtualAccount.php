<?php

namespace App\Services\Btn;

use App\Models\TagihanJasa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BtnSnapVirtualAccount
{
    public function __construct(private BtnSnapConfig $config, private BtnSnapClient $client) {}

    public function create(TagihanJasa $tagihan): array
    {
        $this->config->assertOutbound();

        return Cache::lock('btn:va:'.$tagihan->id, 90)->block(5, function () use ($tagihan) {
            $tagihan->refresh();
            $stored = $tagihan->btn_va_data;
            if (! $stored || $stored['state'] === 'create_rejected') {
                // Check token/key before reserving a mutation that has not reached the bank.
                $this->client->token();
            }
            if ($stored) {
                $this->assertEnvironment($stored);
                if ($stored['state'] === 'active') {
                    if (BtnSnapMoney::cents($stored['request']['totalAmount']['value'])
                        !== BtnSnapMoney::cents(number_format($tagihan->total_dengan_denda, 2, '.', ''))
                        || Carbon::parse($stored['request']['expiredDate'])->isPast()) {
                        throw new BtnSnapException('Nominal/masa berlaku VA perlu diperbarui ke BTN sebelum publish.');
                    }

                    return $this->result($stored);
                }
                // A prior create may have succeeded despite a lost HTTP response.
                if (! in_array($stored['state'], ['creating', 'create_rejected'], true)) {
                    throw new BtnSnapException('VA memiliki operasi belum selesai atau sudah dihapus. Periksa melalui btn:va inquiry.');
                }
                if ($stored['state'] === 'create_rejected') {
                    $stored['request'] = $this->build($tagihan, $this->identity($stored['request']));
                    $stored['state'] = 'creating';
                    $tagihan->update(['btn_va_data' => $stored]);
                    $response = $this->sendMutation($tagihan, 'create-va', $stored['request'], $stored);
                } else {
                    $response = $this->client->request('inquiry-va', $this->identity($stored['request']));
                }
            } else {
                if ($tagihan->nomor_va || $tagihan->va_reference || (float) $tagihan->jumlah_dibayar > 0) {
                    throw new BtnSnapException('Tagihan memiliki VA manual/mock atau pembayaran lama. Pemetaan ke SNAP harus diverifikasi terlebih dahulu.');
                }
                $request = $this->build($tagihan);
                $stored = ['state' => 'creating', 'environment' => $this->config->fingerprint(), 'request' => $request];
                // Commit the identity before contacting BTN, including when create times out.
                $tagihan->update(['btn_va_data' => $stored]);
                $response = $this->sendMutation($tagihan, 'create-va', $request, $stored);
            }
            $this->verify($response, $stored['request']);
            $stored['state'] = 'active';
            $this->persist($tagihan, $stored);

            return $this->result($stored);
        });
    }

    public function inquiry(TagihanJasa $tagihan): array
    {
        $stored = $this->stored($tagihan);

        return $this->client->request('inquiry-va', $this->identity($stored['request']));
    }

    public function recover(TagihanJasa $tagihan): array
    {
        return Cache::lock('btn:va:'.$tagihan->id, 90)->block(5, function () use ($tagihan) {
            $tagihan->refresh();
            $stored = $this->stored($tagihan);
            if (! in_array($stored['state'], ['creating', 'updating'], true)) {
                throw new BtnSnapException('Recover hanya untuk create/update yang belum mendapat kepastian BTN.');
            }
            $request = $stored['pending_request'] ?? $stored['request'];
            $response = $this->client->request('inquiry-va', $this->identity($request));
            $this->verify($response, $request);
            $stored['request'] = $request;
            $stored['state'] = 'active';
            unset($stored['pending_request']);
            $this->persist($tagihan, $stored);

            return $response;
        });
    }

    public function update(TagihanJasa $tagihan): array
    {
        return $this->mutate($tagihan, 'update-va');
    }

    public function delete(TagihanJasa $tagihan): array
    {
        return $this->mutate($tagihan, 'delete-va');
    }

    private function mutate(TagihanJasa $tagihan, string $action): array
    {
        $this->config->assertOutbound();

        return Cache::lock('btn:va:'.$tagihan->id, 90)->block(5, function () use ($tagihan, $action) {
            $stored = DB::transaction(function () use ($tagihan, $action) {
                $locked = TagihanJasa::whereKey($tagihan->id)->lockForUpdate()->firstOrFail();
                $stored = $this->stored($locked);
                if ($stored['state'] !== 'active' || (float) $locked->jumlah_dibayar > 0 || $locked->status === 'LUNAS') {
                    throw new BtnSnapException('Update/delete hanya tersedia untuk VA aktif yang belum menerima pembayaran.');
                }
                $stored['state'] = $action === 'update-va' ? 'updating' : 'deleting';
                if ($action === 'update-va') {
                    $stored['pending_request'] = $this->build($locked, $stored['request']);
                }
                $locked->update(['btn_va_data' => $stored]);

                return $stored;
            });
            $request = $action === 'update-va' ? $stored['pending_request'] : $this->identity($stored['request']);
            $response = $this->sendMutation($tagihan, $action, $request, $stored);
            $this->verify($response, $request, $action !== 'delete-va');
            $stored['state'] = $action === 'update-va' ? 'active' : 'deleted';
            if ($action === 'update-va') {
                $stored['request'] = $request;
                unset($stored['pending_request']);
            }
            $this->persist($tagihan, $stored);

            return $response;
        });
    }

    public function report(string $start, string $end): array
    {
        return $this->client->request('report', [
            'partnerServiceId' => $this->config->partnerServiceId(), 'startDate' => $start, 'endDate' => $end,
        ]);
    }

    private function sendMutation(TagihanJasa $tagihan, string $action, array $request, array $stored): array
    {
        try {
            return $this->client->request($action, $request);
        } catch (BtnSnapException $e) {
            // A definitive validation/authentication rejection can be retried after correction.
            // Conflicts, server errors and transport failures remain uncertain.
            if (in_array(substr($e->responseCode, 0, 3), ['400', '401', '403', '404'], true)) {
                $stored['state'] = $action === 'create-va' ? 'create_rejected' : 'active';
                unset($stored['pending_request']);
                $tagihan->update(['btn_va_data' => $stored]);
            }
            throw $e;
        }
    }

    public function status(TagihanJasa $tagihan, string $inquiryId, ?string $paymentId): array
    {
        $request = $this->identity($this->stored($tagihan)['request']);
        unset($request['trxId']);

        return $this->client->request('status', $request + array_filter([
            'inquiryRequestId' => $inquiryId, 'paymentRequestId' => $paymentId,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function stored(TagihanJasa $tagihan): array
    {
        $stored = $tagihan->btn_va_data;
        if (! $stored || empty($stored['request'])) {
            throw new BtnSnapException('Tagihan belum memiliki identitas VA SNAP.');
        }
        $this->assertEnvironment($stored);

        return $stored;
    }

    private function assertEnvironment(array $stored): void
    {
        if (($stored['environment'] ?? '') !== $this->config->fingerprint()) {
            throw new BtnSnapException('Lingkungan atau institusi BTN berubah. VA sandbox/production tidak boleh dipertukarkan.');
        }
    }

    private function build(TagihanJasa $tagihan, ?array $previous = null): array
    {
        $type = $this->config->get('trx_type', 'F');
        if (strlen($type) !== 1 || ! ctype_alnum($type)) {
            throw new BtnSnapException('Isi kode jenis transaksi VA satu karakter sesuai kesepakatan BTN.');
        }
        $customerNo = str_pad((string) $tagihan->id, 14, '0', STR_PAD_LEFT);
        if (strlen($customerNo) !== 14) {
            throw new BtnSnapException('ID tagihan melebihi panjang customerNo BTN.');
        }
        $amount = number_format($tagihan->total_dengan_denda, 2, '.', '');
        if (BtnSnapMoney::cents($amount) <= 0) {
            throw new BtnSnapException('Tagihan VA harus lebih besar dari nol.');
        }
        $name = mb_substr(trim($tagihan->mitra?->nama_mitra ?? $tagihan->mitraLegacy?->nama_pihak ?? ''), 0, 30);
        if ($name === '') {
            throw new BtnSnapException('Nama mitra diperlukan untuk membuat VA BTN.');
        }

        return ($previous ? $this->identity($previous) : [
            'partnerServiceId' => $this->config->partnerServiceId(),
            'customerNo' => $customerNo,
            'virtualAccountNo' => trim($this->config->partnerServiceId()).$customerNo,
            'trxId' => 'BTN'.str_pad((string) $tagihan->id, 16, '0', STR_PAD_LEFT),
        ]) + [
            'virtualAccountName' => $name,
            'totalAmount' => ['value' => $amount, 'currency' => 'IDR'],
            'virtualAccountTrxType' => $previous['virtualAccountTrxType'] ?? $type,
            'expiredDate' => now('Asia/Jakarta')->addDays(max(1, (int) $this->config->get('va_expiry_days', 30)))->endOfDay()->format('Y-m-d\TH:i:sP'),
            'additionalInfo' => array_filter([
                'description' => mb_substr($tagihan->nomor_tagihan, 0, 60),
                'currentAccountNo' => $this->config->get('current_account_no'),
            ], fn ($v) => $v !== ''),
        ];
    }

    private function identity(array $request): array
    {
        return array_intersect_key($request, array_flip(['partnerServiceId', 'customerNo', 'virtualAccountNo', 'trxId']));
    }

    private function verify(array $response, array $request, bool $amount = true): void
    {
        $data = $response['virtualAccountData'] ?? [];
        foreach ($this->identity($request) as $key => $value) {
            if (! is_string($data[$key] ?? null) || trim($data[$key]) !== trim($value)) {
                throw new BtnSnapException('Identitas VA pada respons BTN tidak cocok: '.$key.'.');
            }
        }
        if ($amount && (data_get($data, 'totalAmount.currency') !== 'IDR'
            || BtnSnapMoney::cents(data_get($data, 'totalAmount.value')) !== BtnSnapMoney::cents($request['totalAmount']['value']))) {
            throw new BtnSnapException('Nominal VA pada respons BTN tidak cocok.');
        }
        if ($amount && (($data['virtualAccountTrxType'] ?? null) !== $request['virtualAccountTrxType']
            || blank($data['expiredDate'] ?? null)
            || ! Carbon::parse($data['expiredDate'])->equalTo(Carbon::parse($request['expiredDate'])))) {
            throw new BtnSnapException('Jenis transaksi atau masa berlaku pada respons BTN tidak cocok.');
        }
    }

    private function persist(TagihanJasa $tagihan, array $stored): void
    {
        $tagihan->update([
            'btn_va_data' => $stored, 'nomor_va' => trim($stored['request']['virtualAccountNo']),
            'va_provider' => 'btn', 'va_reference' => $stored['request']['trxId'],
            'va_expired_at' => Carbon::parse($stored['request']['expiredDate']),
        ]);
    }

    private function result(array $stored): array
    {
        return [
            'provider' => 'btn', 'number' => trim($stored['request']['virtualAccountNo']),
            'reference' => $stored['request']['trxId'], 'expired_at' => Carbon::parse($stored['request']['expiredDate']),
            'mode' => $this->config->mode(),
        ];
    }
}
