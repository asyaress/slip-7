<?php

namespace App\Providers;

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
        \Carbon\Carbon::setLocale('id');

        // Hanya paksa APP_URL di production (subfolder VPS). Di local biarkan
        // mengikuti host request (127.0.0.1 vs localhost).
        if (! $this->app->environment('local') && ($rootUrl = config('app.url'))) {
            URL::forceRootUrl($rootUrl);
        }
    }
}
