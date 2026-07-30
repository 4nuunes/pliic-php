<?php

declare(strict_types=1);

namespace Pliic\Tests\Laravel;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Pliic\Laravel\Pliic;
use Pliic\Laravel\PliicServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected const WEBHOOK_SECRET = 'whsec_test_secret';

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PliicServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function ($config): void {
            // Needed for WebhookCsrfTest, the one test that boots the full `web`
            // middleware group (EncryptCookies, StartSession) instead of running
            // the route bare like the rest of this suite does.
            $config->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

            $config->set('pliic.api_key', 'sk_test_123');
            $config->set('pliic.base_url', 'https://pliic.test');
            $config->set('pliic.webhook_secret', self::WEBHOOK_SECRET);
        });
    }

    protected function defineRoutes($router): void
    {
        Pliic::webhooks('/webhooks/pliic');
    }

    protected function signatureFor(string $payload, int $timestamp, string $secret = self::WEBHOOK_SECRET): string
    {
        return "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    }
}
