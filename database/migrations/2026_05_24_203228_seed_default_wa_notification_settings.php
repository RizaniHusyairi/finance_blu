<?php

use App\Models\IntegrationSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Seed default settings untuk manajemen notifikasi WhatsApp:
 *  - Rentang hari sebelum jatuh tempo yang akan dikirimi reminder.
 *  - Template pesan reminder & lunas.
 *  - Toggle aktif/nonaktif tiap channel.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            // Reminder jatuh tempo
            ['whatsapp.reminder.enabled', '1', 'whatsapp', 'Aktifkan reminder jatuh tempo', 'boolean', false],
            ['whatsapp.reminder.days_before', '7,3,1,0', 'whatsapp', 'Hari sebelum jatuh tempo (CSV)', 'string', false],
            ['whatsapp.reminder.send_time', '08:00', 'whatsapp', 'Jam pengiriman reminder', 'string', false],
            ['whatsapp.reminder.template', $this->defaultReminderTemplate(), 'whatsapp', 'Template reminder', 'textarea', false],

            // Notifikasi lunas
            ['whatsapp.lunas.enabled', '1', 'whatsapp', 'Aktifkan notifikasi lunas', 'boolean', false],
            ['whatsapp.lunas.template', $this->defaultLunasTemplate(), 'whatsapp', 'Template lunas', 'textarea', false],
        ];

        foreach ($defaults as [$key, $value, $group, $label, $type, $isSecret]) {
            $existing = IntegrationSetting::where('key', $key)->first();
            if (! $existing) {
                IntegrationSetting::setValue($key, $value, $group, $label, $type, $isSecret);
            }
        }
    }

    public function down(): void
    {
        IntegrationSetting::whereIn('key', [
            'whatsapp.reminder.enabled',
            'whatsapp.reminder.days_before',
            'whatsapp.reminder.send_time',
            'whatsapp.reminder.template',
            'whatsapp.lunas.enabled',
            'whatsapp.lunas.template',
        ])->delete();
    }

    private function defaultReminderTemplate(): string
    {
        return <<<TXT
*PENGINGAT TAGIHAN PNBP*

Yth. {mitra_nama},

Tagihan {nomor_tagihan} senilai *{total}* akan jatuh tempo pada *{jatuh_tempo}* ({sisa_hari}).

Silakan lakukan pembayaran melalui Virtual Account Bank BTN:
No VA: *{nomor_va}*

Link Invoice: {link_invoice}

Terima kasih atas kerja sama Anda.
_Sistem Informasi Keuangan (SIKEREN)_
TXT;
    }

    private function defaultLunasTemplate(): string
    {
        return <<<TXT
*KONFIRMASI PEMBAYARAN LUNAS*

Yth. {mitra_nama},

Pembayaran tagihan {nomor_tagihan} senilai *{total}* telah kami terima dengan referensi *{referensi}*.

Status tagihan saat ini: *LUNAS*.

Detail: {link_invoice}

Terima kasih atas kerja sama Anda.
_Sistem Informasi Keuangan (SIKEREN)_
TXT;
    }
};
