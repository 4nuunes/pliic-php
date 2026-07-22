<?php

declare(strict_types=1);

namespace Pliic\Exceptions;

class ApiErrorException extends PliicException
{
    /**
     * @param  array<string, mixed>|null  $body  Decoded JSON error body, when the API returned one
     */
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly ?array $body = null,
    ) {
        parent::__construct($message, $status);
    }

    /**
     * Validation errors keyed by field, as returned by the API on 422 responses.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        $errors = $this->body['errors'] ?? [];

        return is_array($errors) ? $errors : [];
    }
}
