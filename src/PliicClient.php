<?php

declare(strict_types=1);

namespace Pliic;

use InvalidArgumentException;
use JsonException;
use Pliic\Exceptions\ApiErrorException;
use Pliic\Exceptions\AuthenticationException;
use Pliic\Exceptions\NotFoundException;
use Pliic\Exceptions\PermissionException;
use Pliic\Exceptions\RateLimitException;
use Pliic\Exceptions\ValidationException;
use Pliic\HttpClient\CurlHttpClient;
use Pliic\HttpClient\HttpClientInterface;
use Pliic\Resources\Analytics;
use Pliic\Resources\Privacy;
use Pliic\Resources\Suggestions;
use Pliic\Resources\Surveys;
use Pliic\Resources\Tickets;

/**
 * Entry point for the Pliic API.
 *
 * ```php
 * $pliic = new PliicClient('sk_live_...');
 *
 * $pliic->suggestions->create([
 *     'user' => ['id' => 'u_123', 'name' => 'Ana', 'email' => 'ana@example.com'],
 *     'title' => 'Dark mode',
 * ]);
 * ```
 */
final class PliicClient
{
    public const VERSION = '0.1.0';

    public const DEFAULT_BASE_URL = 'https://pliic.com';

    public readonly Suggestions $suggestions;

    public readonly Tickets $tickets;

    public readonly Surveys $surveys;

    public readonly Analytics $analytics;

    public readonly Privacy $privacy;

    private readonly HttpClientInterface $httpClient;

    private readonly string $baseUrl;

    public function __construct(
        private readonly string $secretKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?HttpClientInterface $httpClient = null,
    ) {
        if (! str_starts_with($secretKey, 'sk_')) {
            throw new InvalidArgumentException('PliicClient expects a secret API key (sk_...). Find it in your app settings.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = $httpClient ?? new CurlHttpClient;

        $this->suggestions = new Suggestions($this);
        $this->tickets = new Tickets($this);
        $this->surveys = new Surveys($this);
        $this->analytics = new Analytics($this);
        $this->privacy = new Privacy($this);
    }

    /**
     * @param  array<string, string|int|null>  $query
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     *
     * @throws ApiErrorException
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $url = $this->baseUrl.'/api/v1'.$path;

        $filteredQuery = array_filter($query, fn ($value): bool => $value !== null && $value !== '');

        if ($filteredQuery !== []) {
            $url .= '?'.http_build_query($filteredQuery);
        }

        $headers = [
            'Authorization' => "Bearer {$this->secretKey}",
            'Accept' => 'application/json',
            'User-Agent' => 'pliic-php/'.self::VERSION,
        ];

        $encodedBody = null;

        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $encodedBody = json_encode($body, JSON_THROW_ON_ERROR);
        }

        $response = $this->httpClient->request($method, $url, $headers, $encodedBody);

        $decoded = $this->decode($response->body);

        if ($response->status >= 400) {
            throw $this->errorFor($response->status, $decoded);
        }

        return $decoded ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(string $body): ?array
    {
        if ($body === '') {
            return null;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function errorFor(int $status, ?array $body): ApiErrorException
    {
        $message = 'Pliic API error (HTTP '.$status.').';

        foreach (['message', 'error'] as $key) {
            if (isset($body[$key]) && is_string($body[$key]) && $body[$key] !== '') {
                $message = $body[$key];

                break;
            }
        }

        return match (true) {
            $status === 401 => new AuthenticationException($message, $status, $body),
            $status === 403 => new PermissionException($message, $status, $body),
            $status === 404 => new NotFoundException($message, $status, $body),
            $status === 422 => new ValidationException($message, $status, $body),
            $status === 429 => new RateLimitException($message, $status, $body),
            default => new ApiErrorException($message, $status, $body),
        };
    }
}
