<?php

declare(strict_types=1);

namespace Pliic;

use InvalidArgumentException;

/**
 * Mints the end-user token (JWT HS256) the widget and the Widget API accept
 * for SSO. Sign it server-side with your app secret key (sk_...), never in
 * the browser.
 *
 * ```php
 * $token = UserToken::mint($secretKey, [
 *     'id' => 'u_123',
 *     'name' => 'Ana',
 *     'email' => 'ana@example.com',
 * ]);
 * ```
 */
final class UserToken
{
    public const DEFAULT_TTL_SECONDS = 3600;

    /**
     * @param  array{id: string, name?: string, email?: string, avatar_url?: string, metadata?: array<string, mixed>}  $claims
     */
    public static function mint(
        string $secretKey,
        array $claims,
        int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
        ?int $issuedAt = null,
    ): string {
        if (! isset($claims['id']) || ! is_string($claims['id']) || $claims['id'] === '') {
            throw new InvalidArgumentException('UserToken claims must include a non-empty string "id".');
        }

        if ($ttlSeconds < 1) {
            throw new InvalidArgumentException('UserToken ttlSeconds must be positive.');
        }

        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));

        $claims['exp'] = ($issuedAt ?? time()) + $ttlSeconds;

        $payload = self::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $secretKey, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
