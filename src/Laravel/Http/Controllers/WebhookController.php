<?php

declare(strict_types=1);

namespace Pliic\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pliic\Exceptions\SignatureVerificationException;
use Pliic\Laravel\Events\WebhookReceived;
use Pliic\Webhook;

/**
 * Registered by Pliic::webhooks(). Verifies the X-Pliic-Signature header
 * and dispatches Pliic\Laravel\Events\WebhookReceived — the app never
 * touches the raw payload directly.
 */
final class WebhookController
{
    public function __invoke(Request $request): Response
    {
        $secret = config('pliic.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            logger()->warning('Pliic webhook secret is not configured.');

            return new Response('Pliic webhook secret is not configured.', 401);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('X-Pliic-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException $e) {
            return new Response($e->getMessage(), 400);
        }

        WebhookReceived::dispatch($event);

        return new Response('', 200);
    }
}
