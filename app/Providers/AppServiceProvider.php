<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        App::setLocale(config('app.locale', 'id'));
        Carbon::setLocale(config('app.locale', 'id'));

        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'Indonesian_indonesia.1252', 'Indonesian');

        Paginator::useBootstrapFive();

        // SEC-03: di produksi, paksa pembentukan URL https (signed URL TTE, aset,
        // redirect) agar konsisten dengan cookie Secure & situs HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Jejak audit autentikasi untuk Command Center (login/logout/login gagal).
        Event::listen(Login::class, function (Login $event): void {
            ActivityLogger::logAuth('login', $event->user, request());
        });
        Event::listen(Logout::class, function (Logout $event): void {
            ActivityLogger::logAuth('logout', $event->user, request());
        });
        Event::listen(Failed::class, function (Failed $event): void {
            ActivityLogger::logAuth('login_gagal', null, request(), $event->credentials['email'] ?? null);
        });
    }
}
