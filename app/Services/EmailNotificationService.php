<?php

namespace App\Services;

use App\Models\IntegrationLog;
use App\Models\IntegrationSetting;
use App\Models\ShortLink;
use App\Models\TagihanJasa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailNotificationService
{
    public function sendPublishedTagihan(TagihanJasa $tagihan, array $accountInfo = []): bool
    {
        if (! (bool) IntegrationSetting::getValue('email.enabled', true)) {
            $this->log('skipped', 'Notifikasi email tagihan dinonaktifkan.', $tagihan);
            return false;
        }

        $target = $accountInfo['notification_email']
            ?? ($tagihan->mitra?->email ?: ($accountInfo['email'] ?? null));
        if (! filter_var($target, FILTER_VALIDATE_EMAIL)) {
            $this->log('skipped', 'Email mitra tidak tersedia atau tidak valid.', $tagihan);
            return false;
        }

        $subject = $this->renderTemplate(
            (string) IntegrationSetting::getValue('email.invoice_subject', 'Pemberitahuan Tagihan PNBP Jasa {nomor_tagihan}'),
            $this->placeholders($tagihan, $accountInfo)
        );
        $body = $this->buildPublishedTagihanMessage($tagihan, $accountInfo);

        try {
            $this->applyMailConfig();

            Mail::html($this->toHtml($body), function ($message) use ($target, $subject) {
                $message->to($target)->subject($subject);
            });

            $this->log('success', 'Email tagihan berhasil diproses untuk ' . $target, $tagihan, [
                'target' => $target,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log('failed', $e->getMessage(), $tagihan, [
                'target' => $target,
                'subject' => $subject,
            ]);

            return false;
        }
    }

    public function sendTest(string $target, string $message): bool
    {
        if (! (bool) IntegrationSetting::getValue('email.enabled', true)) {
            $this->log('skipped', 'Test email dibatalkan karena integrasi email nonaktif.', null, [], 'send_test_email');
            return false;
        }

        try {
            $this->applyMailConfig();

            Mail::html($this->toHtml($message), function ($mail) use ($target) {
                $mail->to($target)->subject('Test Email SIKEREN-BLU');
            });

            $this->log('success', 'Test email berhasil diproses untuk ' . $target, null, [
                'target' => $target,
            ], 'send_test_email');

            return true;
        } catch (\Throwable $e) {
            $this->log('failed', $e->getMessage(), null, [
                'target' => $target,
            ], 'send_test_email');

            return false;
        }
    }

    public function sendNotification(string $target, string $subject, string $body, ?Model $reference = null, string $action = 'send_notification_email'): bool
    {
        if (! (bool) IntegrationSetting::getValue('email.enabled', true)) {
            $this->log('skipped', 'Notifikasi email dinonaktifkan.', $reference, [
                'target' => $target,
                'subject' => $subject,
            ], $action);

            return false;
        }

        if (! filter_var($target, FILTER_VALIDATE_EMAIL)) {
            $this->log('skipped', 'Alamat email tujuan tidak tersedia atau tidak valid.', $reference, [
                'target' => $target,
                'subject' => $subject,
            ], $action);

            return false;
        }

        try {
            $this->applyMailConfig();

            Mail::html($this->toHtml($body), function ($mail) use ($target, $subject) {
                $mail->to($target)->subject($subject);
            });

            $this->log('success', 'Email notifikasi berhasil diproses untuk ' . $target, $reference, [
                'target' => $target,
                'subject' => $subject,
            ], $action);

            return true;
        } catch (\Throwable $e) {
            $this->log('failed', $e->getMessage(), $reference, [
                'target' => $target,
                'subject' => $subject,
            ], $action);

            return false;
        }
    }

    public function buildPublishedTagihanMessage(TagihanJasa $tagihan, array $accountInfo = []): string
    {
        $template = (string) IntegrationSetting::getValue('email.invoice_template', '');
        $placeholders = $this->placeholders($tagihan, $accountInfo);

        if (filled($template)) {
            return $this->renderTemplate($template, $placeholders);
        }

        return $this->renderTemplate(
            "Yth. {mitra_nama},\n\n"
            . "Dengan hormat,\n\n"
            . "Kami informasikan bahwa tagihan PNBP Jasa telah diterbitkan melalui aplikasi SIKEREN-BLU dengan rincian sebagai berikut:\n\n"
            . "Nomor Tagihan : {nomor_tagihan}\n"
            . "Total Tagihan : {total}\n"
            . "Virtual Account BTN : {nomor_va}\n"
            . "Tanggal Publish : {tanggal_publish}\n"
            . "Jatuh Tempo : {jatuh_tempo}\n\n"
            . "Silakan melakukan pengecekan detail tagihan, surat pengantar, dan nota tagihan melalui tautan berikut:\n"
            . "{link_invoice}\n\n"
            . "Informasi akun portal mitra:\n"
            . "Email Login : {email_login}\n"
            . "Password : {password_login}\n\n"
            . "Apabila Anda sudah pernah menerima akun sebelumnya, silakan tetap menggunakan kata sandi yang telah dimiliki.\n\n"
            . "Demikian pemberitahuan ini disampaikan. Terima kasih atas perhatian dan kerja samanya.\n\n"
            . "Hormat kami,\n"
            . "SIKEREN-BLU",
            $placeholders
        );
    }

    private function placeholders(TagihanJasa $tagihan, array $accountInfo = []): array
    {
        $tagihan->loadMissing(['mitra', 'mitraLegacy']);
        $mitra = $tagihan->mitra ?? $tagihan->mitraLegacy;
        $linkInvoice = ShortLink::forTarget('tagihan_jasa', $tagihan->id)->publicUrl();

        return [
            '{mitra_nama}' => $mitra->nama_mitra ?? $mitra->nama_pihak ?? '-',
            '{nomor_tagihan}' => $tagihan->nomor_tagihan ?? '-',
            '{nomor_va}' => $tagihan->nomor_va ?? '-',
            '{total}' => 'Rp ' . number_format((float) $tagihan->total_tagihan, 0, ',', '.'),
            '{tanggal_publish}' => $tagihan->tanggal_publish ? $tagihan->tanggal_publish->format('d/m/Y') : now()->format('d/m/Y'),
            '{jatuh_tempo}' => $tagihan->tanggal_jatuh_tempo ? $tagihan->tanggal_jatuh_tempo->format('d/m/Y') : '-',
            '{link_invoice}' => $linkInvoice,
            '{email_login}' => $accountInfo['email'] ?? '-',
            '{password_login}' => $accountInfo['password'] ?? '(gunakan password yang sudah dimiliki)',
        ];
    }

    private function renderTemplate(string $template, array $placeholders): string
    {
        return strtr($template, $placeholders);
    }

    private function toHtml(string $body): string
    {
        $escaped = nl2br(e($body), false);

        return '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#0f172a;">'
            . $escaped
            . '</div>';
    }

    private function applyMailConfig(): void
    {
        $mailer = (string) IntegrationSetting::getValue('email.mailer', config('mail.default', 'log'));
        Config::set('mail.default', $mailer ?: config('mail.default', 'log'));

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', IntegrationSetting::getValue('email.smtp_host', config('mail.mailers.smtp.host')));
            Config::set('mail.mailers.smtp.port', (int) IntegrationSetting::getValue('email.smtp_port', config('mail.mailers.smtp.port')));
            Config::set('mail.mailers.smtp.encryption', IntegrationSetting::getValue('email.smtp_encryption', config('mail.mailers.smtp.encryption')));
            Config::set('mail.mailers.smtp.username', IntegrationSetting::getValue('email.smtp_username', config('mail.mailers.smtp.username')));
            Config::set('mail.mailers.smtp.password', IntegrationSetting::getValue('email.smtp_password', config('mail.mailers.smtp.password')));
        }

        $fromAddress = IntegrationSetting::getValue('email.from_address', config('mail.from.address'));
        $fromName = IntegrationSetting::getValue('email.from_name', config('mail.from.name'));

        if (filled($fromAddress)) {
            Config::set('mail.from.address', $fromAddress);
        }

        if (filled($fromName)) {
            Config::set('mail.from.name', $fromName);
        }
    }

    private function log(string $status, string $message, ?Model $reference = null, array $payload = [], string $action = 'send_invoice_email'): void
    {
        IntegrationLog::create([
            'provider' => 'email',
            'action' => $action,
            'direction' => 'outbound',
            'status' => $status,
            'endpoint' => (string) IntegrationSetting::getValue('email.mailer', config('mail.default', 'log')),
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'request_payload' => $payload,
            'response_payload' => null,
            'message' => Str::limit($message, 1000),
            'created_by' => auth()->id(),
        ]);
    }
}
