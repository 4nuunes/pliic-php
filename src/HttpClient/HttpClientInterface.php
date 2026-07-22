<?php

declare(strict_types=1);

namespace Pliic\HttpClient;

use Pliic\Exceptions\TransportException;

interface HttpClientInterface
{
    /**
     * @param  array<string, string>  $headers
     *
     * @throws TransportException on network-level failure
     */
    public function request(string $method, string $url, array $headers, ?string $body = null): ApiResponse;
}
