<?php

declare(strict_types=1);

namespace Pliic\Tests;

use PHPUnit\Framework\TestCase;
use Pliic\Exceptions\SignatureVerificationException;
use Pliic\Webhook;

final class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private const PAYLOAD = '{"id":"evt_1","event":"suggestion.created","created_at":"2026-07-19T12:00:00Z","data":{"title":"Dark mode"}}';

    private function signatureFor(string $payload, int $timestamp, string $secret = self::SECRET): string
    {
        return "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
    }

    public function test_construct_event_returns_the_parsed_event_for_a_valid_signature(): void
    {
        $timestamp = 1750000000;

        $event = Webhook::constructEvent(
            self::PAYLOAD,
            $this->signatureFor(self::PAYLOAD, $timestamp),
            self::SECRET,
            now: $timestamp + 10,
        );

        $this->assertSame('evt_1', $event->id);
        $this->assertSame('suggestion.created', $event->type);
        $this->assertSame('2026-07-19T12:00:00Z', $event->createdAt);
        $this->assertSame(['title' => 'Dark mode'], $event->data);
    }

    public function test_construct_event_rejects_a_tampered_payload(): void
    {
        $timestamp = 1750000000;
        $signature = $this->signatureFor(self::PAYLOAD, $timestamp);

        $this->expectException(SignatureVerificationException::class);

        Webhook::constructEvent('{"id":"evt_1","event":"tampered"}', $signature, self::SECRET, now: $timestamp);
    }

    public function test_construct_event_rejects_a_wrong_secret(): void
    {
        $timestamp = 1750000000;

        $this->expectException(SignatureVerificationException::class);

        Webhook::constructEvent(
            self::PAYLOAD,
            $this->signatureFor(self::PAYLOAD, $timestamp, 'whsec_other'),
            self::SECRET,
            now: $timestamp,
        );
    }

    public function test_construct_event_rejects_a_timestamp_outside_the_tolerance(): void
    {
        $timestamp = 1750000000;

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('tolerance');

        Webhook::constructEvent(
            self::PAYLOAD,
            $this->signatureFor(self::PAYLOAD, $timestamp),
            self::SECRET,
            now: $timestamp + 301,
        );
    }

    public function test_construct_event_accepts_old_timestamps_when_tolerance_is_disabled(): void
    {
        $timestamp = 1750000000;

        $event = Webhook::constructEvent(
            self::PAYLOAD,
            $this->signatureFor(self::PAYLOAD, $timestamp),
            self::SECRET,
            toleranceSeconds: 0,
            now: $timestamp + 999999,
        );

        $this->assertSame('evt_1', $event->id);
    }

    public function test_construct_event_rejects_a_malformed_header(): void
    {
        $this->expectException(SignatureVerificationException::class);

        Webhook::constructEvent(self::PAYLOAD, 'sha256=deadbeef', self::SECRET);
    }

    public function test_construct_event_rejects_a_missing_header(): void
    {
        $this->expectException(SignatureVerificationException::class);

        Webhook::constructEvent(self::PAYLOAD, null, self::SECRET);
    }
}
