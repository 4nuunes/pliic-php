<?php

declare(strict_types=1);

namespace Pliic\Testing;

use Pliic\Exceptions\TransportException;
use Pliic\HttpClient\ApiResponse;
use Pliic\HttpClient\HttpClientInterface;
use RuntimeException;

/**
 * Official test double for `Pliic\HttpClient\HttpClientInterface`.
 *
 * Responds with realistic payloads out of the box (see `Fixtures`), so most
 * tests need zero setup. Queue an exact response with `queue()`/`seed*()`
 * when a test cares about a specific payload, and inspect what was sent
 * through `requests`, `lastRequest()`, or the `assert*()` helpers.
 *
 * ```php
 * use Pliic\PliicClient;
 * use Pliic\Testing\FakeHttpClient;
 *
 * $fake = new FakeHttpClient();
 * $pliic = new PliicClient('sk_test_fake', 'https://pliic.test', $fake);
 *
 * $pliic->suggestions->list(); // realistic default list, no setup needed
 *
 * $fake->seedSuggestion(['title' => 'Dark mode']);
 * $pliic->suggestions->get(1);
 *
 * $fake->assertRequested('GET', '/suggestions/1');
 * ```
 */
final class FakeHttpClient implements HttpClientInterface
{
    /** @var list<array{method: string, url: string, headers: array<string, string>, body: ?string}> */
    public array $requests = [];

    /** @var list<ApiResponse> */
    private array $queue = [];

    private ?TransportException $nextTransportFailure = null;

    private ?string $ownerEmail = null;

    private ?string $ownerId = null;

    /**
     * Passing `$defaultStatus`/`$defaultBody` pins every request (until the
     * queue is used) to that literal response, overriding the realistic
     * per-endpoint defaults. Leave both null for zero-config behaviour.
     */
    public function __construct(
        private readonly ?int $defaultStatus = null,
        private readonly ?string $defaultBody = null,
    ) {}

    /**
     * Queue an exact response for the next request. Responses are consumed
     * in FIFO order; once drained, realistic per-endpoint defaults resume.
     *
     * @param  array<string, mixed>  $payload
     */
    public function queue(int $status, array $payload): self
    {
        $this->queue[] = new ApiResponse($status, json_encode($payload, JSON_THROW_ON_ERROR));

        return $this;
    }

    public function failNextWithTransportError(string $message = 'Connection refused'): self
    {
        $this->nextTransportFailure = new TransportException($message);

        return $this;
    }

    /**
     * Mirrors the API's ownership scoping: a read carrying a different
     * `user_email` than this one answers 404 instead of the queued/default
     * payload.
     */
    public function ownedByEmail(string $email): self
    {
        $this->ownerEmail = $email;

        return $this;
    }

    /**
     * Same as `ownedByEmail()`, scoped by `user_id` instead of `user_email`.
     */
    public function ownedByUserId(string $id): self
    {
        $this->ownerId = $id;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded suggestion
     */
    public function seedSuggestion(array $overrides = []): array
    {
        $suggestion = Fixtures::suggestion($overrides);
        $this->queue(200, ['data' => $suggestion]);

        return $suggestion;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded suggestion
     */
    public function seedSuggestionCreated(array $overrides = []): array
    {
        $suggestion = Fixtures::suggestion($overrides);
        $this->queue(201, ['data' => $suggestion]);

        return $suggestion;
    }

    /**
     * @param  list<array<string, mixed>>  $suggestions
     */
    public function seedSuggestionList(array $suggestions = []): self
    {
        return $this->queue(200, Fixtures::suggestionsListResponse($suggestions));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded vote result
     */
    public function seedVote(array $overrides = []): array
    {
        $vote = Fixtures::voteResult($overrides);
        $this->queue(200, ['data' => $vote]);

        return $vote;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded comment
     */
    public function seedSuggestionComment(array $overrides = []): array
    {
        $comment = Fixtures::suggestionComment($overrides);
        $this->queue(201, ['data' => $comment]);

        return $comment;
    }

    /**
     * @param  list<array<string, mixed>>  $comments
     */
    public function seedSuggestionComments(array $comments = []): self
    {
        return $this->queue(200, Fixtures::suggestionCommentsResponse($comments));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded ticket
     */
    public function seedTicket(array $overrides = []): array
    {
        $ticket = Fixtures::ticket($overrides);
        $this->queue(200, ['data' => $ticket]);

        return $ticket;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<array<string, mixed>>  $messages
     * @return array<string, mixed> the seeded ticket, thread included
     */
    public function seedTicketDetail(array $overrides = [], array $messages = []): array
    {
        $ticket = Fixtures::ticket($overrides, $messages);
        $this->queue(200, ['data' => $ticket]);

        return $ticket;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded ticket
     */
    public function seedTicketCreated(array $overrides = []): array
    {
        $ticket = Fixtures::ticket($overrides);
        $this->queue(201, ['data' => $ticket]);

        return $ticket;
    }

    /**
     * @param  list<array<string, mixed>>  $tickets
     */
    public function seedTicketList(array $tickets = []): self
    {
        return $this->queue(200, Fixtures::ticketsListResponse($tickets));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded reply
     */
    public function seedTicketReply(array $overrides = []): array
    {
        $message = Fixtures::ticketMessage($overrides);
        $this->queue(201, ['data' => $message]);

        return $message;
    }

    /**
     * @param  list<array<string, mixed>>  $surveys
     */
    public function seedSurveyList(array $surveys = []): self
    {
        return $this->queue(200, Fixtures::surveysListResponse($surveys));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded results
     */
    public function seedSurveyResults(array $overrides = []): array
    {
        $results = Fixtures::surveyResults($overrides);
        $this->queue(200, ['data' => $results]);

        return $results;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded metrics
     */
    public function seedAnalytics(array $overrides = []): array
    {
        $analytics = Fixtures::analytics($overrides);
        $this->queue(200, ['data' => $analytics]);

        return $analytics;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed> the seeded app user export
     */
    public function seedAppUserExport(array $overrides = []): array
    {
        $appUser = Fixtures::appUser($overrides);
        $this->queue(200, ['data' => $appUser]);

        return $appUser;
    }

    public function seedErasure(string $message = 'User data erased.'): self
    {
        return $this->queue(200, Fixtures::erasureResponse($message));
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public function seedError(int $status, string $message, array $errors = []): self
    {
        return $this->queue($status, Fixtures::error($message, $errors));
    }

    /**
     * Queue the 403 a read-only key gets on a write, so an integration can
     * cover its `catch (InsufficientScopeException)` branch.
     *
     * @param  list<string>  $grantedScopes
     */
    public function seedInsufficientScope(
        string $requiredScope = 'suggestions:write',
        array $grantedScopes = ['suggestions:read', 'tickets:read'],
    ): self {
        return $this->queue(403, Fixtures::insufficientScopeError($requiredScope, $grantedScopes));
    }

    public function request(string $method, string $url, array $headers, ?string $body = null): ApiResponse
    {
        if ($this->nextTransportFailure !== null) {
            $failure = $this->nextTransportFailure;
            $this->nextTransportFailure = null;

            throw $failure;
        }

        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

        if ($this->deniedByOwnership($url)) {
            return new ApiResponse(404, json_encode(['message' => 'Not found'], JSON_THROW_ON_ERROR));
        }

        if ($this->queue !== []) {
            return array_shift($this->queue);
        }

        if ($this->defaultStatus !== null) {
            return new ApiResponse($this->defaultStatus, $this->defaultBody ?? '{"data":[]}');
        }

        return $this->defaultResponseFor($method, $url);
    }

    private function deniedByOwnership(string $url): bool
    {
        if ($this->ownerEmail !== null && ! str_contains($url, 'user_email='.urlencode($this->ownerEmail))) {
            return true;
        }

        return $this->ownerId !== null && ! str_contains($url, 'user_id='.urlencode($this->ownerId));
    }

    /**
     * Endpoint-aware realistic default, used once the queue is drained and
     * no fixed default response was configured in the constructor. Status
     * codes mirror the OpenAPI spec (`app/Support/OpenApiSpec.php`): 201 for
     * the endpoints that create something, 200 everywhere else.
     */
    private function defaultResponseFor(string $method, string $url): ApiResponse
    {
        // `PliicClient::request()` always builds URLs as `{baseUrl}/api/v1{path}`;
        // strip that fixed prefix so route patterns can anchor on the resource path itself.
        $path = preg_replace('#^/api/v1#', '', (string) parse_url($url, PHP_URL_PATH)) ?? '';
        $method = strtoupper($method);

        /** @var list<array{0: string, 1: string, 2: int, 3: callable(): array<string, mixed>}> $routes */
        $routes = [
            ['GET', '#^/suggestions/\d+/comments$#', 200, fn (): array => Fixtures::suggestionCommentsResponse()],
            ['POST', '#^/suggestions/\d+/comments$#', 201, fn (): array => Fixtures::suggestionCommentResponse()],
            ['POST', '#^/suggestions/\d+/vote$#', 200, fn (): array => Fixtures::voteResultResponse()],
            ['GET', '#^/suggestions/\d+$#', 200, fn (): array => Fixtures::suggestionResponse()],
            ['POST', '#^/suggestions$#', 201, fn (): array => Fixtures::suggestionResponse()],
            ['GET', '#^/suggestions$#', 200, fn (): array => Fixtures::suggestionsListResponse()],
            ['POST', '#^/tickets/\d+/replies$#', 201, fn (): array => Fixtures::ticketMessageResponse()],
            ['GET', '#^/tickets/\d+$#', 200, fn (): array => Fixtures::ticketDetailResponse()],
            ['POST', '#^/tickets$#', 201, fn (): array => Fixtures::ticketResponse()],
            ['GET', '#^/tickets$#', 200, fn (): array => Fixtures::ticketsListResponse()],
            ['GET', '#^/analytics$#', 200, fn (): array => Fixtures::analyticsResponse()],
            ['GET', '#^/privacy/export/\d+$#', 200, fn (): array => Fixtures::appUserResponse()],
            ['DELETE', '#^/privacy/\d+$#', 200, fn (): array => Fixtures::erasureResponse()],
            ['GET', '#^/surveys/\d+/results$#', 200, fn (): array => Fixtures::surveyResultsResponse()],
            ['GET', '#^/surveys$#', 200, fn (): array => Fixtures::surveysListResponse()],
        ];

        foreach ($routes as [$routeMethod, $pattern, $status, $payload]) {
            if ($method === $routeMethod && preg_match($pattern, $path) === 1) {
                return new ApiResponse($status, json_encode($payload(), JSON_THROW_ON_ERROR));
            }
        }

        return new ApiResponse(200, '{"data":[]}');
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: ?string}|null
     */
    public function lastRequest(): ?array
    {
        if ($this->requests === []) {
            return null;
        }

        return $this->requests[count($this->requests) - 1];
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    /**
     * The JSON-decoded body of the last request, or an empty array when
     * there was none.
     *
     * @return array<string, mixed>
     */
    public function lastRequestBody(): array
    {
        $body = $this->lastRequest()['body'] ?? null;

        if ($body === null) {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function wasRequested(string $method, string $pathContains): bool
    {
        foreach ($this->requests as $request) {
            if (strcasecmp($request['method'], $method) === 0 && str_contains($request['url'], $pathContains)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws RuntimeException when no matching request was made
     */
    public function assertRequested(string $method, string $pathContains): self
    {
        if (! $this->wasRequested($method, $pathContains)) {
            throw new RuntimeException("Expected a {$method} request containing \"{$pathContains}\", none was made. Requests: {$this->describeRequests()}");
        }

        return $this;
    }

    /**
     * @throws RuntimeException when a matching request was made
     */
    public function assertNotRequested(string $method, string $pathContains): self
    {
        if ($this->wasRequested($method, $pathContains)) {
            throw new RuntimeException("Expected no {$method} request containing \"{$pathContains}\", but one was made.");
        }

        return $this;
    }

    /**
     * @throws RuntimeException when the request count doesn't match
     */
    public function assertRequestCount(int $expected): self
    {
        $actual = $this->requestCount();

        if ($actual !== $expected) {
            throw new RuntimeException("Expected {$expected} request(s), {$actual} were made. Requests: {$this->describeRequests()}");
        }

        return $this;
    }

    private function describeRequests(): string
    {
        if ($this->requests === []) {
            return '(none)';
        }

        return implode(', ', array_map(
            static fn (array $request): string => "{$request['method']} {$request['url']}",
            $this->requests,
        ));
    }
}
