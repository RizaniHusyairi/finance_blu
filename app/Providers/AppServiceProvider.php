<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
    }
}
