<?php

declare(strict_types=1);

namespace Pliic\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pliic\UserToken;

final class UserTokenTest extends TestCase
{
    public function test_mint_produces_the_expected_token_for_a_known_vector(): void
    {
        $token = UserToken::mint(
            'sk_test_fixed_secret',
            ['id' => 'u_1', 'name' => 'Ana', 'email' => 'ana@example.com'],
            ttlSeconds: 3600,
            issuedAt: 1750000000,
        );

        $this->assertSame(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6InVfMSIsIm5hbWUiOiJBbmEiLCJlbWFpbCI6ImFuYUBleGFtcGxlLmNvbSIsImV4cCI6MTc1MDAwMzYwMH0.D6NAlQZ-f_rInSnvQvbP0neXpw-Q5cKzmh5sPdArHGU',
            $token,
        );
    }

    public function test_mint_sets_exp_from_current_time_by_default(): void
    {
        $token = UserToken::mint('sk_test_secret', ['id' => 'u_2']);

        [, $payloadB64] = explode('.', $token);
        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);

        $this->assertEqualsWithDelta(time() + UserToken::DEFAULT_TTL_SECONDS, $payload['exp'], 5);
        $this->assertSame('u_2', $payload['id']);
    }

    public function test_mint_requires_a_non_empty_id_claim(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserToken::mint('sk_test_secret', ['name' => 'No Id']);
    }

    public function test_mint_rejects_non_positive_ttl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserToken::mint('sk_test_secret', ['id' => 'u_3'], ttlSeconds: 0);
    }
}
