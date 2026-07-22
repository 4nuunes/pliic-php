<?php

declare(strict_types=1);

namespace Pliic;

final class WebhookEvent
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $createdAt,
        public readonly array $data,
        public readonly array $raw,
    ) {}
}
