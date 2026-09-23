<?php

namespace App\Services;

use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Models\PaymentTransaction;
use App\Models\TagihanJasa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BtnVirtualAccountService
{
    public function createVirtualAccount(TagihanJasa $tagihan): array
    {
        $mode = IntegrationSetting::getValue('btn.mode', 'mock');
        $prefix = IntegrationSetting::getValue('btn.va_prefix', '88');
        $expiryDays = (int) IntegrationSetting::getValue('btn.va_expiry_days', 30);
        $isActive = (bool) IntegrationSetting::getValue('btn.enabled', false);

        if (! $isActive) {
            throw new \RuntimeException('VA otomatis BTN tidak aktif. Gunakan nomor VA manual.');
        }
        if ($mode === 'mock') {
            if ($tagihan->btn_va_data) {
                throw new \RuntimeException('VA SNAP tidak dapat digunakan sebagai VA simulasi.');
            }
            return $this->mockVirtualAccount($tagihan, $prefix, $expiryDays, 'Mode simulasi BTN aktif.');
        }

        return app(\App\Services\Btn\BtnSnapVirtualAccount::class)->create($tagihan);
    }

    public function handlePaymentCallback(array $payload): array
    {
        $virtualAccount = $this->firstFilled($payload, [
            'virtual_account',
            'va_number',
            'nomor_va',
            'data.virtual_account',
            'data.va_number',
        ]);
        $amount = (float) ($this->firstFilled($payload, [
            'amount',
            'paid_amount',
            'data.amount',
            'data.paid_amount',
        ]) ?: 0);
        $paidAt = $this->firstFilled($payload, [
            'paid_at',
            'payment_time',
            'data.paid_at',
            'data.payment_time',
        ]);
        $channel = $this->firstFilled($payload, [
            'channel',
            'payment_channel',
            'data.channel',
            'data.payment_channel',
        ]);

        // INT-02: kunci idempotensi diambil dari reference provider. Bila kosong,
        // TURUNKAN secara DETERMINISTIK dari field stabil (VA + nominal + waktu bayar)
        // — JANGAN memakai UUID acak yang membuat setiap retry/replay menjadi
        // transaksi baru sehingga terjadi double credit.
        $externalReference = $this->firstFilled($payload, [
            'reference',
            'payment_reference',
            'transaction_id',
            'data.reference',
            'data.transaction_id',
        ]);
        if (blank($externalReference)) {
            $externalReference = 'BTN-' . sha1(($virtualAccount ?? '') . '|' . $amount . '|' . ($paidAt ?? ''));
        }

        $tagihan = TagihanJasa::where('nomor_va', $virtualAccount)
            ->orWhere('va_reference', $externalReference)
            ->first();

        if ($tagihan?->btn_va_data) {
            throw new \RuntimeException('Pembayaran VA SNAP hanya dapat diproses melalui endpoint SNAP.');
        }

        $callbackResult = DB::transaction(function () use ($tagihan, $payload, $virtualAccount, $externalReference, $amount, $paidAt, $channel) {
            // INT-02: kunci baris transaksi yang sudah ada untuk (provider, external_reference)
            // agar callback yang sama tidak diproses paralel/berulang (race condition).
            $existing = PaymentTransaction::where('provider', 'btn')
                ->where('external_reference', $externalReference)
                ->lockForUpdate()
                ->first();

            // Bila callback ini sudah pernah dituntaskan (paid), JANGAN proses ulang —
            // cegah double credit, re-settlement, dan notifikasi ganda saat retry/replay.
            if ($existing && $existing->status === 'paid') {
                return ['transaction' => $existing, 'alreadyProcessed' => true];
            }

            $transaction = PaymentTransaction::updateOrCreate(
                [
                    'provider' => 'btn',
                    'external_reference' => $externalReference,
                ],
                [
                    'tagihan_jasa_id' => $tagihan?->id,
                    'virtual_account' => $virtualAccount,
                    'amount' => $amount,
                    'status' => $tagihan ? 'partial' : 'unmatched',
                    'payment_channel' => $channel,
                    'paid_at' => $paidAt ? Carbon::parse($paidAt) : now(),
                    'payload' => $payload,
                ]
            );

            if (! $tagihan) {
                return ['transaction' => $transaction, 'alreadyProcessed' => false];
            }

            $totalTagihanBerjalan = (float) $tagihan->total_dengan_denda;

            // INT-02 / DI-01: AKUMULASI pembayaran = jumlah seluruh transaksi BTN sukses
            // untuk tagihan ini (bukan menimpa dengan nominal callback tunggal), supaya
            // pembayaran parsial berulang menambah, bukan menggantikan, dan tidak terjadi
            // double credit maupun tagihan yang "tak pernah lunas".
            $totalDibayar = (float) PaymentTransaction::where('provider', 'btn')
                ->where('tagihan_jasa_id', $tagihan->id)
                ->whereIn('status', ['paid', 'partial'])
                ->sum('amount');

            $isFullPayment = $totalTagihanBerjalan > 0 && $totalDibayar >= $totalTagihanBerjalan;
            $transaction->update(['status' => $isFullPayment ? 'paid' : 'partial']);

            if ($isFullPayment) {
                $tagihan->update([
                    'status' => 'LUNAS',
                    'status_pembayaran' => 'lunas',
                    'jumlah_dibayar' => $totalDibayar,
                    'sisa_tagihan' => 0,
                    'tanggal_lunas' => now()->toDateString(),
                    'paid_at' => $transaction->paid_at,
                    'payment_reference' => $externalReference,
                    'payment_channel' => $channel,
                    'last_payment_sync_at' => now(),
                ]);
            } else {
                $tagihan->update([
                    'jumlah_dibayar' => $totalDibayar,
                    'sisa_tagihan' => max(0, $totalTagihanBerjalan - $totalDibayar),
                    'payment_reference' => $externalReference,
                    'payment_channel' => $channel,
                    'last_payment_sync_at' => now(),
                ]);
            }

            return ['transaction' => $transaction, 'alreadyProcessed' => false];
        });

        $transaction = $callbackResult['transaction'];
        $alreadyProcessed = $callbackResult['alreadyProcessed'];

        IntegrationLog::create([
            'provider' => 'btn',
            'action' => 'payment_callback',
            'direction' => 'inbound',
            'status' => $tagihan ? 'success' : 'unmatched',
            'reference_type' => TagihanJasa::class,
            'reference_id' => $tagihan?->id,
            'request_payload' => $payload,
            'response_payload' => ['payment_transaction_id' => $transaction->id],
            'message' => $tagihan ? 'Callback pembayaran BTN diproses.' : 'Callback diterima tetapi tagihan tidak ditemukan.',
        ]);

        if ($tagihan && ! $alreadyProcessed && $transaction->status === 'partial') {
            $freshTagihan = $tagihan->fresh(['mitra', 'mitraLegacy', 'details']);

            try {
                app(\App\Services\Pembukuan\PiutangSyncService::class)->syncFromPartial(
                    $freshTagihan,
                    [
                        'amount' => (float) $transaction->amount,
                        'paid_at' => $transaction->paid_at,
                    ]
                );
            } catch (\Throwable $e) {
                \Log::error('PiutangSync partial gagal di callback BTN: ' . $e->getMessage());
            }
        }

        if ($tagihan && ! $alreadyProcessed && $transaction->status === 'paid') {
            $freshTagihan = $tagihan->fresh(['mitra', 'mitraLegacy', 'details']);

            // Sync ke piutang LUNAS + catat BKU
            try {
                app(\App\Services\Pembukuan\PiutangSyncService::class)->syncFromLunas(
                    $freshTagihan,
                    [
                        'amount' => (float) $transaction->amount,
                        'paid_at' => $transaction->paid_at,
                        'reference' => $transaction->external_reference ?: ('CB/' . $transaction->id),
                    ]
                );
            } catch (\Throwable $e) {
                \Log::error('PiutangSync gagal di callback BTN: ' . $e->getMessage());
            }

            if ($transaction->wasRecentlyCreated || $transaction->wasChanged('status')) {
                $this->sendPaymentReceipt($freshTagihan, $transaction);
            }
        }

        return [
            'matched' => (bool) $tagihan,
            'tagihan' => $tagihan,
            'transaction' => $transaction,
        ];
    }

    private function mockVirtualAccount(TagihanJasa $tagihan, string $prefix, int $expiryDays, string $message): array
    {
        $number = $tagihan->nomor_va ?: $prefix . str_pad((string) $tagihan->id, 10, '0', STR_PAD_LEFT);
        $reference = $tagihan->va_reference ?: 'MOCK-BTN-' . now()->format('YmdHis') . '-' . $tagihan->id;
        $expiresAt = now()->addDays(max(1, $expiryDays))->endOfDay();

        IntegrationLog::create([
            'provider' => 'btn',
            'action' => 'create_virtual_account',
            'direction' => 'outbound',
            'status' => 'mock',
            'reference_type' => TagihanJasa::class,
            'reference_id' => $tagihan->id,
            'request_payload' => [
                'nomor_tagihan' => $tagihan->nomor_tagihan,
                'amount' => $tagihan->total_tagihan,
            ],
            'response_payload' => [
                'virtual_account' => $number,
                'reference' => $reference,
                'expired_at' => $expiresAt->toDateTimeString(),
            ],
            'message' => $message,
            'created_by' => auth()->id(),
        ]);

        return [
            'provider' => 'btn',
            'number' => $number,
            'reference' => $reference,
            'expired_at' => $expiresAt,
            'mode' => 'mock',
        ];
    }

    private function firstFilled(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function sendPaymentReceipt(TagihanJasa $tagihan, PaymentTransaction $transaction): void
    {
        $this->sendLunasNotification($tagihan, [
            'amount'    => (float) $transaction->amount,
            'paid_at'   => $transaction->paid_at,
            'reference' => $transaction->external_reference ?: '-',
        ]);
    }

    /**
     * Kirim notifikasi lunas berdasarkan template di IntegrationSetting.
     * Bisa dipanggil dari mana saja (callback BTN, manual mark-lunas, dll)
     * dengan payload pembayaran ringkas.
     *
     * @param array{amount: float, paid_at?: \Carbon\Carbon|null, reference?: string|null} $payment
     */
    public function sendLunasNotification(TagihanJasa $tagihan, array $payment): void
    {
        $template = (string) \App\Models\IntegrationSetting::getValue('whatsapp.lunas.template', '');
        $linkInvoice = \App\Models\ShortLink::forTarget('tagihan_jasa', $tagihan->id)->publicUrl();

        $amount = (float) ($payment['amount'] ?? $tagihan->total_tagihan);
        $paidAt = $payment['paid_at'] ?? now();
        if (! $paidAt instanceof \Carbon\Carbon && $paidAt) {
            $paidAt = \Carbon\Carbon::parse($paidAt);
        }
        $reference = (string) ($payment['reference'] ?? ('BKU-MASUK/' . $tagihan->nomor_tagihan));

        $placeholders = [
            '{mitra_nama}'    => $tagihan->mitra->nama_mitra ?? $tagihan->mitraLegacy?->nama_pihak ?? '-',
            '{nomor_tagihan}' => $tagihan->nomor_tagihan,
            '{nomor_va}'      => $tagihan->nomor_va ?? '-',
            '{total}'         => 'Rp ' . number_format($amount, 0, ',', '.'),
            '{waktu_bayar}'   => $paidAt ? $paidAt->format('d/m/Y H:i') : '-',
            '{referensi}'     => $reference,
            '{link_invoice}'  => $linkInvoice,
        ];

        if (filled($template)) {
            $message = strtr($template, $placeholders);
        } else {
            // Fallback default kalau template kosong.
            $message = "*STRUK PEMBAYARAN PNBP JASA*\n\n";
            $message .= "Yth. " . $placeholders['{mitra_nama}'] . ",\n\n";
            $message .= "Pembayaran tagihan Anda telah diterima.\n";
            $message .= "No Tagihan: *{$tagihan->nomor_tagihan}*\n";
            $message .= "No VA: *" . ($tagihan->nomor_va ?? '-') . "*\n";
            $message .= "Nominal Bayar: *Rp " . number_format($amount, 0, ',', '.') . "*\n";
            $message .= "Waktu Bayar: *" . ($paidAt ? $paidAt->format('d/m/Y H:i') : '-') . "*\n";
            $message .= "Referensi: " . $reference . "\n\n";
            $message .= "Status tagihan sekarang: *LUNAS*.\n";
            $message .= "Detail: " . $linkInvoice . "\n\n";
            $message .= "_Sistem Informasi Keuangan (SIKEREN)_";
        }

        $waEnabled = (bool) \App\Models\IntegrationSetting::getValue('whatsapp.lunas.enabled', true);
        $waTarget = $tagihan->mitra?->no_telepon;

        if ($waEnabled && filled($waTarget)) {
            app(WhatsappService::class)->queueMessage($waTarget, $message, $tagihan);
        }

        $emailEnabled = (bool) \App\Models\IntegrationSetting::getValue('email.lunas.enabled', true);
        $emailTarget = $tagihan->mitra?->email ?? $tagihan->mitraLegacy?->email ?? null;

        if ($emailEnabled) {
            $emailBody = "Yth. {$placeholders['{mitra_nama}']},\n\n"
                . "Dengan hormat,\n\n"
                . "Kami informasikan bahwa pembayaran tagihan PNBP Jasa telah diterima dan status tagihan telah diperbarui menjadi LUNAS.\n\n"
                . "Nomor Tagihan : {$placeholders['{nomor_tagihan}']}\n"
                . "Virtual Account BTN : {$placeholders['{nomor_va}']}\n"
                . "Nominal Pembayaran : {$placeholders['{total}']}\n"
                . "Waktu Pembayaran : {$placeholders['{waktu_bayar}']}\n"
                . "Referensi Pembayaran : {$placeholders['{referensi}']}\n"
                . "Status : LUNAS\n\n"
                . "Detail tagihan dan bukti status pembayaran dapat dilihat melalui tautan berikut:\n"
                . "{$placeholders['{link_invoice}']}\n\n"
                . "Demikian konfirmasi pembayaran ini disampaikan. Terima kasih atas perhatian dan kerja samanya.\n\n"
                . "Hormat kami,\n"
                . "SIKEREN-BLU";

            app(EmailNotificationService::class)->sendNotification(
                (string) ($emailTarget ?? ''),
                'Konfirmasi Pembayaran Lunas Tagihan PNBP Jasa ' . $tagihan->nomor_tagihan,
                $emailBody,
                $tagihan,
                'send_paid_invoice_email'
            );
        }
    }
}
