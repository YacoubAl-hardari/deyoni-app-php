<?php

namespace App\Providers;

use App\Services\AppearanceService;
use Illuminate\Support\Facades\Schema;
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
        $this->app->booted(function (): void {
            if (! $this->app->runningUnitTests() && env('NATIVEPHP_PLATFORM') === 'android' && Schema::hasTable('app_settings')) {
                app(AppearanceService::class)->apply();
            }
        });
    }
}
