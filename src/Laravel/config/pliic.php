<?php

declare(strict_types=1);

use Pliic\PliicClient;

return [

    /*
    |--------------------------------------------------------------------------
    | Pliic API Key
    |--------------------------------------------------------------------------
    |
    | Your Pliic secret key (sk_live_... / sk_test_...). It authenticates the
    | PliicClient singleton bound by PliicServiceProvider. Find it in your
    | Pliic app settings, under Settings -> API Keys. Keep it server-side.
    |
    */

    'api_key' => env('PLIIC_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Pliic Base URL
    |--------------------------------------------------------------------------
    |
    | Override only for self-hosted or non-production Pliic instances.
    |
    */

    'base_url' => env('PLIIC_BASE_URL', PliicClient::DEFAULT_BASE_URL),

    /*
    |--------------------------------------------------------------------------
    | Pliic Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The signing secret (whsec_...) for the endpoint registered with
    | Pliic::webhooks(). Used to verify the X-Pliic-Signature header before
    | the Pliic\Laravel\Events\WebhookReceived event is dispatched.
    |
    */

    'webhook_secret' => env('PLIIC_WEBHOOK_SECRET'),

];
