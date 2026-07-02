<?php

namespace App\Providers;

use App\Support\PanelUpdateCacheGuard;
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
        if (!$this->app->runningInConsole()) {
            PanelUpdateCacheGuard::ensureFreshForCurrentVersion();
        }
    }
}
