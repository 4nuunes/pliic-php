<?php

declare(strict_types=1);

namespace Pliic\Laravel;

use Illuminate\Support\ServiceProvider;
use Pliic\PliicClient;

/**
 * Optional Laravel bridge. Auto-discovered via composer.json's
 * `extra.laravel.providers` — it only loads when the host app is a Laravel
 * application, so the framework-agnostic core is never affected.
 */
final class PliicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/pliic.php', 'pliic');

        $this->app->singleton(PliicClient::class, function (): PliicClient {
            $apiKey = config('pliic.api_key');
            $baseUrl = config('pliic.base_url');

            return new PliicClient(
                is_string($apiKey) ? $apiKey : '',
                is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : PliicClient::DEFAULT_BASE_URL,
            );
        });
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/pliic.php' => $this->app->configPath('pliic.php'),
        ], 'pliic-config');
    }
}
