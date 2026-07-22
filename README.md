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
$pliic->tickets->get(7);                                 // includes the public message thread
$pliic->tickets->reply(7, ['user' => $user, 'body' => 'More detail here...']);
```

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
| 403 | `PermissionException` (missing scope or plan feature) |
| 404 | `NotFoundException` |
| 422 | `ValidationException` (`$e->errors()` has the field errors) |
| 429 | `RateLimitException` |

Network-level failures throw `Pliic\Exceptions\TransportException`.

## Testing your integration

The HTTP transport is injectable, so you can fake it:

```php
use Pliic\HttpClient\HttpClientInterface;

$pliic = new PliicClient('sk_live_test', 'https://pliic.com', $yourFakeClient);
```

## Versioning

Semantic versioning. Development happens in the private Pliic monorepo; [4nuunes/pliic-php](https://github.com/4nuunes/pliic-php) is the read-only distribution mirror. Report issues there — pull requests to the mirror cannot be merged.

Full guide: [docs.pliic.com/integrations/sdk-php](https://docs.pliic.com/integrations/sdk-php/).
