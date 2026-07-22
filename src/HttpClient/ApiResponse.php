<?php

declare(strict_types=1);

namespace Pliic\HttpClient;

final class ApiResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body,
    ) {}
}
