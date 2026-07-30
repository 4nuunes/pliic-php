<?php

declare(strict_types=1);

namespace Pliic\Tests\Laravel;

use Illuminate\Support\Facades\Event;
use Pliic\Laravel\Events\WebhookReceived;
use Pliic\Laravel\Pliic;

/**
 * The shared TestCase registers Pliic::webhooks() outside the `web`
 * middleware group (Orchestra's defineRoutes() hook), so CSRF never enters
 * the picture there. This test registers it the way a real app copying the
 * README into routes/web.php would: inside the `web` group, with CSRF
 * verification actually active, to prove Pliic::webhooks() opts itself out.
 */
final class WebhookCsrfTest extends TestCase
{
    private const PAYLOAD = '{"id":"evt_1","event":"suggestion.created","created_at":"2026-07-19T12:00:00Z","data":{"title":"Dark mode"}}';

    protected function defineRoutes($router): void
    {
        // Intentionally empty: this test needs the route inside defineWebRoutes()
        // instead, so it inherits the `web` group's CSRF middleware.
    }

    protected function defineWebRoutes($router): void
    {
        Pliic::webhooks('/webhooks/pliic');
    }

    public function test_a_validly_signed_request_succeeds_even_inside_the_web_middleware_group(): void
    {
        // ValidateCsrfToken skips verification whenever the app reports
        // env=testing (Application::runningUnitTests()), which would make
        // this test pass even without ->withoutMiddleware() and prove
        // nothing. Force env=local so the middleware actually runs its
        // token check, the way it would in a real request.
        $this->app['env'] = 'local';

        Event::fake();

        $timestamp = time();
        $signature = $this->signatureFor(self::PAYLOAD, $timestamp);

        $response = $this->call('POST', '/webhooks/pliic', server: [
            'HTTP_X-Pliic-Signature' => $signature,
        ], content: self::PAYLOAD);

        $response->assertStatus(200);
        Event::assertDispatched(WebhookReceived::class);
    }
}
