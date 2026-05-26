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
