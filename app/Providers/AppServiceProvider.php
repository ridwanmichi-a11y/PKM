<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Zona waktu & bahasa Indonesia untuk seluruh aplikasi.
        $tz = config('sipz.timezone', 'Asia/Jakarta');
        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz);
        Carbon::setLocale('id');
        app()->setLocale('id');
    }
}
