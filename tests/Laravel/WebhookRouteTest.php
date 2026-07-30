<?php

declare(strict_types=1);

namespace Pliic\Tests\Laravel;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Pliic\Laravel\Events\WebhookReceived;

final class WebhookRouteTest extends TestCase
{
    private const PAYLOAD = '{"id":"evt_1","event":"suggestion.created","created_at":"2026-07-19T12:00:00Z","data":{"title":"Dark mode"}}';

    public function test_it_rejects_a_request_with_no_signature_header(): void
    {
        Event::fake();

        $response = $this->postJson('/webhooks/pliic', json_decode(self::PAYLOAD, true));

        $response->assertStatus(400);
        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_it_rejects_a_request_with_a_tampered_signature(): void
    {
        Event::fake();

        $timestamp = time();
        $badSignature = "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.tampered", 'wrong_secret');

        $response = $this->call('POST', '/webhooks/pliic', server: [
            'HTTP_X-Pliic-Signature' => $badSignature,
        ], content: self::PAYLOAD);

        $response->assertStatus(400);
        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_it_rejects_when_the_webhook_secret_is_not_configured(): void
    {
        config(['pliic.webhook_secret' => null]);
        Event::fake();
        Log::spy();

        $timestamp = time();
        $signature = "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.".self::PAYLOAD, self::WEBHOOK_SECRET);

        $response = $this->call('POST', '/webhooks/pliic', server: [
            'HTTP_X-Pliic-Signature' => $signature,
        ], content: self::PAYLOAD);

        $response->assertStatus(401);
        Event::assertNotDispatched(WebhookReceived::class);
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Pliic webhook secret is not configured.');
    }

    public function test_it_dispatches_webhook_received_with_the_typed_event_for_a_valid_signature(): void
    {
        Event::fake();

        $timestamp = time();
        $signature = $this->signatureFor(self::PAYLOAD, $timestamp);

        $response = $this->call('POST', '/webhooks/pliic', server: [
            'HTTP_X-Pliic-Signature' => $signature,
        ], content: self::PAYLOAD);

        $response->assertStatus(200);

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $received): bool {
            return $received->event->id === 'evt_1'
                && $received->event->type === 'suggestion.created'
                && $received->event->data === ['title' => 'Dark mode'];
        });
    }
}
