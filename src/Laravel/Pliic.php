<?php

declare(strict_types=1);

namespace Pliic\Laravel;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/**
 * Registers the ready-made webhook route.
 *
 * ```php
 * use Pliic\Laravel\Pliic;
 *
 * Pliic::webhooks('/webhooks/pliic');
 * ```
 *
 * The route has no CSRF protection needs of its own (Pliic authenticates it
 * via the X-Pliic-Signature header), and it explicitly opts out of the
 * `web` middleware group's CSRF check below, so it works even when
 * registered from routes/web.php. See the README's Laravel section for
 * the rest of the setup.
 */
final class Pliic
{
    public const DEFAULT_WEBHOOK_URI = '/webhooks/pliic';

    public const WEBHOOK_ROUTE_NAME = 'pliic.webhooks';

    public static function webhooks(string $uri = self::DEFAULT_WEBHOOK_URI): void
    {
        Route::post($uri, Http\Controllers\WebhookController::class)
            ->name(self::WEBHOOK_ROUTE_NAME)
            ->withoutMiddleware(ValidateCsrfToken::class);
    }
}
