<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Reminder WhatsApp tagihan mendekati jatuh tempo.
// Dijalankan tiap jam — command sendiri yang memutuskan apakah jam sekarang
// cocok dengan setting whatsapp.reminder.send_time.
Schedule::command('wa:reminder-due-date')
    ->hourly()
    ->withoutOverlapping(10)
    ->onOneServer();

// Nonaktifkan akun PLT/PLH setelah masa aktif berakhir.
Schedule::command('users:disable-expired-temporary')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->onOneServer();

// Ingatkan mitra yang belum melaporkan konsesi/PAX PJP2U bulan lalu.
// Command sendiri memeriksa toggle jasa.reminder_pelaporan.enabled.
Schedule::command('jasa:reminder-pelaporan')
    ->monthlyOn(3, '08:00')
    ->withoutOverlapping()
    ->onOneServer();

// BR-01 — backup database harian (mysqldump → gzip) + retensi 14 berkas.
// Direktori backup (DB_BACKUP_PATH) WAJIB disinkronkan off-site & terenkripsi.
// Lihat docs/RUNBOOK-BACKUP-RESTORE.md.
Schedule::command('db:backup')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->onOneServer();

// MON-01 — pantau kesehatan sistem (failed jobs, kesegaran backup) tiap jam;
// anomali → log critical (Slack/Sentry bila aktif) + alert WA (bila dikonfigurasi).
Schedule::command('monitor:health --quiet-ok')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
