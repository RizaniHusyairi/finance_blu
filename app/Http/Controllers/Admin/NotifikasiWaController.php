<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Models\WhatsappNotificationLog;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

/**
 * Manajemen notifikasi WhatsApp untuk Super Admin:
 *  - Atur on/off reminder & lunas.
 *  - Atur rentang hari sebelum jatuh tempo (CSV) dan jam pengiriman.
 *  - Edit template pesan reminder & lunas dengan placeholder dinamis.
 *  - Test kirim manual.
 */
class NotifikasiWaController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin');
    }

    public function index()
    {
        $settings = $this->collect();
        $logs = WhatsappNotificationLog::latest()->limit(15)->get();

        return view('admin.notifikasi_wa.index', compact('settings', 'logs'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'reminder_enabled' => ['nullable', 'boolean'],
            'reminder_days_before' => ['required', 'string', 'max:100', 'regex:/^\s*\d+(\s*,\s*\d+)*\s*$/'],
            'reminder_send_time' => ['required', 'date_format:H:i'],
            'reminder_template' => ['required', 'string', 'max:4000'],
            'lunas_enabled' => ['nullable', 'boolean'],
            'lunas_template' => ['required', 'string', 'max:4000'],
            'pengajuan_tagihan_enabled' => ['nullable', 'boolean'],
        ], [
            'reminder_days_before.regex' => 'Rentang hari harus berupa angka dipisah koma, contoh: 7,3,1,0',
        ]);

        // Normalisasi CSV: hilangkan spasi, deduplikasi, urutkan desc.
        $offsets = collect(explode(',', $validated['reminder_days_before']))
            ->map(fn ($v) => (int) trim($v))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        IntegrationSetting::setValue('whatsapp.reminder.enabled', $request->boolean('reminder_enabled'), 'whatsapp', 'Aktifkan reminder', 'boolean');
        IntegrationSetting::setValue('whatsapp.reminder.days_before', implode(',', $offsets), 'whatsapp', 'Hari sebelum jatuh tempo');
        IntegrationSetting::setValue('whatsapp.reminder.send_time', $validated['reminder_send_time'], 'whatsapp', 'Jam pengiriman reminder');
        IntegrationSetting::setValue('whatsapp.reminder.template', $validated['reminder_template'], 'whatsapp', 'Template reminder', 'textarea');
        IntegrationSetting::setValue('whatsapp.lunas.enabled', $request->boolean('lunas_enabled'), 'whatsapp', 'Aktifkan notifikasi lunas', 'boolean');
        IntegrationSetting::setValue('whatsapp.lunas.template', $validated['lunas_template'], 'whatsapp', 'Template lunas', 'textarea');
        IntegrationSetting::setValue('whatsapp.pengajuan_tagihan.enabled', $request->boolean('pengajuan_tagihan_enabled'), 'whatsapp', 'Aktifkan notifikasi pengajuan tagihan kontrak', 'boolean');

        return back()->with('success', 'Pengaturan notifikasi WhatsApp berhasil disimpan.');
    }

    public function test(Request $request, WhatsappService $whatsappService)
    {
        $validated = $request->validate([
            'target' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $sent = $whatsappService->sendMessage($validated['target'], $validated['message']);

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'Pesan test WhatsApp berhasil diproses.'
            : 'Pesan test gagal — periksa konfigurasi gateway.');
    }

    public function runReminderNow()
    {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('wa:reminder-due-date', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        return back()->with($exitCode === 0 ? 'success' : 'error', "Reminder dijalankan manual.\n\n" . trim($output));
    }

    private function collect(): array
    {
        return [
            'reminder_enabled' => (bool) IntegrationSetting::getValue('whatsapp.reminder.enabled', true),
            'reminder_days_before' => (string) IntegrationSetting::getValue('whatsapp.reminder.days_before', '7,3,1,0'),
            'reminder_send_time' => (string) IntegrationSetting::getValue('whatsapp.reminder.send_time', '08:00'),
            'reminder_template' => (string) IntegrationSetting::getValue('whatsapp.reminder.template', ''),
            'lunas_enabled' => (bool) IntegrationSetting::getValue('whatsapp.lunas.enabled', true),
            'lunas_template' => (string) IntegrationSetting::getValue('whatsapp.lunas.template', ''),
            'pengajuan_tagihan_enabled' => (bool) IntegrationSetting::getValue('whatsapp.pengajuan_tagihan.enabled', true),
        ];
    }
}
