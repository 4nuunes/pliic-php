<?php

declare(strict_types=1);

namespace Pliic\Tests;

use Pliic\HttpClient\ApiResponse;
use Pliic\HttpClient\HttpClientInterface;

final class FakeHttpClient implements HttpClientInterface
{
    /** @var array<int, array{method: string, url: string, headers: array<string, string>, body: ?string}> */
    public array $requests = [];

    public function __construct(
        private readonly int $status = 200,
        private readonly string $body = '{"data":[]}',
    ) {}

    public function request(string $method, string $url, array $headers, ?string $body = null): ApiResponse
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

        return new ApiResponse($this->status, $this->body);
    }

    /**
     * @return array{method: string, url: string, headers: array<string, string>, body: ?string}
     */
    public function lastRequest(): array
    {
        return $this->requests[count($this->requests) - 1];
    }
}
