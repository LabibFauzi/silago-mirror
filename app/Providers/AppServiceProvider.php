<?php

namespace App\Providers;

use App\Services\AuditLogService;
use App\Services\ContentScanner;
use App\Services\DealService;
use App\Services\NotificationService;
use App\Services\OfferService;
use App\Services\SettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Daftarkan services sebagai singleton agar tidak dibuat ulang per request
        $this->app->singleton(SettingService::class);
        $this->app->singleton(AuditLogService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ContentScanner::class);

        // OfferService dan DealService butuh injeksi service lain
        $this->app->singleton(OfferService::class, function ($app) {
            return new OfferService($app->make(NotificationService::class));
        });

        $this->app->singleton(DealService::class, function ($app) {
            return new DealService(
                $app->make(NotificationService::class),
                $app->make(SettingService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
          if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        // Pagination menggunakan Tailwind CSS untuk panel admin
        \Illuminate\Pagination\Paginator::useTailwind();

        // Locale Carbon ke Bahasa Indonesia
        \Illuminate\Support\Carbon::setLocale('id');

        // Rate limiter untuk API login (rules A9: 5 per menit)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter untuk resend verifikasi email
        RateLimiter::for('verify-email', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Rate limiter umum untuk API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });

        \Illuminate\Pagination\Paginator::useTailwind();
    }
}
