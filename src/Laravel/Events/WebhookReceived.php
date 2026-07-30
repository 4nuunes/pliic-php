<?php

declare(strict_types=1);

namespace Pliic\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Pliic\WebhookEvent;

/**
 * Dispatched by Pliic::webhooks() once the X-Pliic-Signature header has been
 * verified. Listen for it instead of parsing the raw webhook payload:
 *
 * ```php
 * Event::listen(WebhookReceived::class, function (WebhookReceived $received): void {
 *     match ($received->event->type) {
 *         'suggestion.created' => ...,
 *         default => null,
 *     };
 * });
 * ```
 */
final class WebhookReceived
{
    use Dispatchable;

    public function __construct(public readonly WebhookEvent $event) {}
}
