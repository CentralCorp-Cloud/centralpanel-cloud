<?php

namespace App\Providers;

use App\Support\PanelUpdateCacheGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        Event::listen(CommandStarting::class, static function (CommandStarting $event): void {
            if (config('centralpanel.managed', false) && in_array($event->command, ['config:cache', 'optimize'], true)) {
                throw new RuntimeException('Le cache de configuration est désactivé en mode managé afin de ne pas persister les secrets.');
            }
        });

        if (!$this->app->runningInConsole()) {
            PanelUpdateCacheGuard::ensureFreshForCurrentVersion();
        }
    }
}
