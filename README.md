# pliic/pliic-php

Official PHP SDK for the [Pliic](https://pliic.com) feedback and support platform.

Use it to integrate Pliic natively into your backend instead of embedding the widget: create suggestions and tickets on behalf of your users, let them vote and comment from your own UI, mint widget SSO tokens, and verify webhook signatures.

## Install

```bash
composer require pliic/pliic-php
```

Requires PHP 8.2+ with `ext-curl` and `ext-json`. No other dependencies.

## Quickstart

```php
use Pliic\PliicClient;

$pliic = new PliicClient('sk_live_...'); // secret key, from your app settings

// Acting on behalf of one of YOUR users: pass a `user` identity and Pliic
// creates or reuses the matching app user (email-first identity rule).
$user = ['id' => 'u_123', 'name' => 'Ana', 'email' => 'ana@example.com'];

$suggestion = $pliic->suggestions->create([
    'user' => $user,
    'title' => 'Dark mode',
    'description' => 'It would be easier on the eyes.',
]);

$pliic->suggestions->vote($suggestion['data']['id'], ['user' => $user]);
```

The secret key must stay server-side. Never ship it to a browser or mobile app.

## Suggestions

```php
$pliic->suggestions->list(['status' => 'planned', 'search' => 'dark', 'user_id' => 'u_123']);
$pliic->suggestions->get(42, ['user_id' => 'u_123']);   // adds user_has_voted
$pliic->suggestions->create(['user' => $user, 'title' => '...']);
$pliic->suggestions->vote(42, ['user' => $user]);        // toggles
$pliic->suggestions->comments(42, ['page' => 1]);
$pliic->suggestions->addComment(42, ['user' => $user, 'body' => 'Great idea!']);
```

Passing `user_id` (your external id) or `user_email` on reads adds `user_has_voted` to each suggestion, so you can render a native board with vote state.

## Tickets

```php
$pliic->tickets->list(['user_id' => 'u_123']);           // that user's tickets
$pliic->tickets->create(['user' => $user, 'subject' => 'Checkout error', 'body' => '...', 'type' => 'bug']);
$pliic->tickets->get(7, ['user_id' => 'u_123']);         // 404s if the ticket isn't u_123's
$pliic->tickets->reply(7, ['user' => $user, 'body' => 'More detail here...']);
```

Passing `user_id`/`user_email` to `tickets->get()` scopes the lookup to that author instead of trusting the caller to check `data.author` themselves — no need to paginate `tickets->list()` first just to confirm ownership.

## Surveys, analytics, privacy

```php
$pliic->surveys->list();
$pliic->surveys->results(3);
$pliic->analytics->get();
$pliic->privacy->export($appUserId);  // GDPR/LGPD export
$pliic->privacy->erase($appUserId);   // GDPR/LGPD erasure
```

## Widget SSO tokens

If you also embed the widget, mint the end-user token server-side:

```php
use Pliic\UserToken;

$token = UserToken::mint($secretKey, [
    'id' => 'u_123',
    'name' => 'Ana',
    'email' => 'ana@example.com',
], ttlSeconds: 3600);
```

Hand `$token` to your frontend as the widget's `userToken`.

## Webhooks

Verify the `X-Pliic-Signature` header (`t=<unix>,v1=<hmac>`) before trusting a payload:

```php
use Pliic\Webhook;
use Pliic\Exceptions\SignatureVerificationException;

try {
    $event = Webhook::constructEvent(
        $request->getContent(),          // raw body, not the parsed array
        $request->header('X-Pliic-Signature'),
        $endpointSecret,                 // whsec_..., from the endpoint settings
    );
} catch (SignatureVerificationException $e) {
    abort(400);
}

match ($event->type) {
    'suggestion.created' => handleNewSuggestion($event->data),
    'ticket.created' => handleNewTicket($event->data),
    default => null,
};
```

Signatures older than 5 minutes are rejected by default (`toleranceSeconds`). Use `$event->id` as an idempotency key if you process events asynchronously: redeliveries of the same event carry the same id.

## Errors

API failures throw typed exceptions, all extending `Pliic\Exceptions\ApiErrorException`:

| Status | Exception |
| --- | --- |
| 401 | `AuthenticationException` |
| 403 + `error: insufficient_scope` | `InsufficientScopeException` (extends `PermissionException`) |
| 403 | `PermissionException` (plan feature not available, and any other refusal) |
| 404 | `NotFoundException` |
| 422 | `ValidationException` (`$e->errors()` has the field errors) |
| 429 | `RateLimitException` |

Mapping is driven by the API's stable `error` code, never by the message text, so wording changes never break your `catch` blocks. Network-level failures throw `Pliic\Exceptions\TransportException`.

### Missing scope (the usual first-write surprise)

**A newly created Pliic key is read-only.** It carries `suggestions:read` and `tickets:read` and nothing else, so your first `create()` fails until someone enables the write scope — this is not a bug in your payload.

`InsufficientScopeException` tells you exactly what is missing and where to fix it:

```php
use Pliic\Exceptions\InsufficientScopeException;

try {
    $pliic->tickets->create(['user' => $user, 'subject' => 'Cannot log in']);
} catch (InsufficientScopeException $e) {
    $e->requiredScope();    // 'tickets:write'
    $e->grantedScopes();    // ['suggestions:read', 'tickets:read']
    $e->manageScopesUrl();  // link straight to Settings → API Keys → Scopes
    $e->docsUrl();          // https://docs.pliic.com/integrations/api-keys/
    $e->getMessage();       // already a full, actionable sentence
}
```

It extends `PermissionException`, so existing `catch (PermissionException)` code keeps catching it — narrow to `InsufficientScopeException` only where you want to tell "wrong key permissions" apart from "plan does not include this".

Enable the scope in Pliic under **Settings → API Keys → Scopes** for the app the key belongs to.

## Testing your integration

The HTTP transport is injectable, so you can fake it. `Pliic\Testing\FakeHttpClient` ships with the package and answers every endpoint with a realistic payload out of the box — no setup needed for the common case:

```php
use Pliic\PliicClient;
use Pliic\Testing\FakeHttpClient;

$fake = new FakeHttpClient();
$pliic = new PliicClient('sk_test_fake', 'https://pliic.com', $fake);

$pliic->suggestions->list(); // realistic default list, zero configuration
```

Seed a specific payload when a test cares about particular data:

```php
$fake->seedSuggestion(['id' => 42, 'title' => 'Dark mode', 'vote_count' => 12]);
$pliic->suggestions->get(42); // returns the seeded suggestion

$fake->seedError(422, 'Invalid', ['title' => ['Título já existe.']]);
$pliic->suggestions->create(['user' => $user, 'title' => 'Duplicada']); // throws ValidationException

$fake->seedInsufficientScope('tickets:write');
$pliic->tickets->create(['user' => $user, 'subject' => 'Oi']); // throws InsufficientScopeException
```

`ownedByEmail()`/`ownedByUserId()` mirror the API's ownership scoping (a ticket read for a different `user_email`/`user_id` 404s). Note that once either is set, the fake denies **every** request that doesn't carry that exact query param — including one that, against the real API, wouldn't have been scoped at all: the real endpoints only enforce ownership when `user_id`/`user_email` is actually sent, so an unscoped read (neither param given) always succeeds there but 404s here. Reset `new FakeHttpClient()` between scenarios that need both behaviours in the same test.

`failNextWithTransportError()` simulates a network failure on the next call only. Assert on what was sent:

```php
$fake->assertRequested('POST', '/suggestions/42/vote');
$fake->assertRequestCount(2);

expect($fake->lastRequestBody())->toBe(['user' => $user, 'title' => 'Dark mode']);
```

`$fake->requests` holds every call made (`method`, `url`, `headers`, `body`), and `Pliic\Testing\Fixtures` exposes every canned payload directly (`Fixtures::suggestion()`, `Fixtures::ticket()`, …) if you need one outside the fake — e.g. to assert against in a controller test. These fixtures are checked against the real API's OpenAPI spec in CI, so they don't drift silently.

## Laravel

The bridge is optional and auto-discovered — it only loads when the host app is a Laravel application. It never adds `illuminate/support` to the SDK's own `require`, so plain-PHP consumers are unaffected.

**1. Install** (already done if you followed [Install](#install) above):

```bash
composer require pliic/pliic-php
```

**2. Publish the config and set your environment variables:**

```bash
php artisan vendor:publish --tag=pliic-config
```

```env
PLIIC_API_KEY=sk_live_...
PLIIC_BASE_URL=https://pliic.com
PLIIC_WEBHOOK_SECRET=whsec_...
```

`Pliic\PliicClient` is now bound as a singleton — resolve it anywhere via the container:

```php
use Pliic\PliicClient;

$pliic = app(PliicClient::class);
```

**3. Register the webhook route and listen for the event:**

```php
use Pliic\Laravel\Pliic;

Pliic::webhooks('/webhooks/pliic'); // POST /webhooks/pliic
```

The route verifies `X-Pliic-Signature` for you and dispatches `Pliic\Laravel\Events\WebhookReceived` — your app never touches the raw payload or the signature check.

```php
use Illuminate\Support\Facades\Event;
use Pliic\Laravel\Events\WebhookReceived;

Event::listen(WebhookReceived::class, function (WebhookReceived $received): void {
    match ($received->event->type) {
        'suggestion.created' => notifyTeamOfNewSuggestion($received->event->data),
        default => null,
    };
});
```

Pliic retries failed deliveries with backoff, so the same `event->id` can arrive more than once. Dedupe by `event->id` (a cache entry or a unique constraint) before acting on it if the handler isn't naturally idempotent.

### CSRF

The webhook route authenticates itself via `X-Pliic-Signature`, not a session, so it must run **outside** the `web` middleware group's CSRF check. Either:

- register it in `routes/api.php` (no CSRF there by default), or
- keep it in `routes/web.php` and add its URI to `VerifyCsrfToken::$except`:

```php
protected $except = [
    'webhooks/pliic',
];
```

### The `author` vs `sender` fields (avoiding self-notifications)

Every webhook payload's `data` carries both an `author` (who the record belongs to — e.g. the suggestion's or ticket's original creator) and a `sender` (who actually triggered *this* event — could be the same person, a teammate, or nobody in particular). Compare their `external_id` before notifying anyone, so a user doesn't get pinged for their own comment or their own status change:

```php
use Illuminate\Support\Facades\Event;
use Pliic\Laravel\Events\WebhookReceived;

Event::listen(WebhookReceived::class, function (WebhookReceived $received): void {
    if ($received->event->type !== 'suggestion.commented') {
        return;
    }

    $author = $received->event->data['author']; // ['external_id' => ..., 'name' => ...]
    $sender = $received->event->data['sender']; // ['type' => 'app_user'|'member', 'external_id' => ..., 'name' => ...]

    if ($sender['external_id'] === $author['external_id']) {
        return; // the author commented on their own suggestion — nothing to notify
    }

    notify($author, "{$sender['name']} commented on your suggestion.");
});
```

`sender['type']` is `'member'` when a teammate acted on your dashboard (its `external_id` is always `null` — members aren't app users), and `'app_user'` when the end user themselves triggered the event.

## Versioning

Semantic versioning. Development happens in the private Pliic monorepo; [4nuunes/pliic-php](https://github.com/4nuunes/pliic-php) is the read-only distribution mirror. Report issues there — pull requests to the mirror cannot be merged.

Full guide: [docs.pliic.com/integrations/sdk-php](https://docs.pliic.com/integrations/sdk-php/).
