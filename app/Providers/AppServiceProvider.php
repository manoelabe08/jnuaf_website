<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.admin');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.admin');

        // Les paramètres saisis dans Admin > Paramètres priment sur config/jnuaf.php et le .env.
        Setting::applyToConfig();

        // Limite les envois de formulaires publics (anti-spam) : 6 par minute et par IP.
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));


        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
