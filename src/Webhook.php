<?php

declare(strict_types=1);

namespace Pliic;

use JsonException;
use Pliic\Exceptions\SignatureVerificationException;

/**
 * Verifies Pliic webhook signatures (X-Pliic-Signature: t=<unix>,v1=<hmac>).
 *
 * ```php
 * $event = Webhook::constructEvent(
 *     $request->getContent(),
 *     $request->header('X-Pliic-Signature'),
 *     $endpointSecret, // whsec_...
 * );
 *
 * match ($event->type) {
 *     'suggestion.created' => ...,
 *     default => null,
 * };
 * ```
 */
final class Webhook
{
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * @throws SignatureVerificationException when the signature is missing, invalid, or too old
     */
    public static function constructEvent(
        string $payload,
        ?string $signatureHeader,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): WebhookEvent {
        if ($signatureHeader === null || $signatureHeader === '') {
            throw new SignatureVerificationException('Missing X-Pliic-Signature header.');
        }

        [$timestamp, $signature] = self::parseHeader($signatureHeader);

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        if (! hash_equals($expected, $signature)) {
            throw new SignatureVerificationException('Webhook signature does not match the expected signature.');
        }

        if ($toleranceSeconds > 0 && abs(($now ?? time()) - $timestamp) > $toleranceSeconds) {
            throw new SignatureVerificationException('Webhook timestamp is outside the allowed tolerance.');
        }

        return self::eventFrom($payload);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private static function parseHeader(string $header): array
    {
        $timestamp = null;
        $signature = null;

        foreach (explode(',', $header) as $part) {
            $pieces = explode('=', trim($part), 2);

            if (count($pieces) !== 2) {
                continue;
            }

            if ($pieces[0] === 't' && ctype_digit($pieces[1])) {
                $timestamp = (int) $pieces[1];
            }

            if ($pieces[0] === 'v1') {
                $signature = $pieces[1];
            }
        }

        if ($timestamp === null || $signature === null || $signature === '') {
            throw new SignatureVerificationException('Malformed X-Pliic-Signature header. Expected "t=<unix>,v1=<hmac>".');
        }

        return [$timestamp, $signature];
    }

    private static function eventFrom(string $payload): WebhookEvent
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new SignatureVerificationException('Webhook payload is not valid JSON.');
        }

        if (! is_array($decoded)) {
            throw new SignatureVerificationException('Webhook payload is not a JSON object.');
        }

        return new WebhookEvent(
            id: is_string($decoded['id'] ?? null) ? $decoded['id'] : '',
            type: is_string($decoded['event'] ?? null) ? $decoded['event'] : '',
            createdAt: is_string($decoded['created_at'] ?? null) ? $decoded['created_at'] : '',
            data: is_array($decoded['data'] ?? null) ? $decoded['data'] : [],
            raw: $decoded,
        );
    }
}
