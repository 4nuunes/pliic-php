<?php

declare(strict_types=1);

namespace Pliic\Tests\Testing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pliic\Exceptions\NotFoundException;
use Pliic\Exceptions\TransportException;
use Pliic\PliicClient;
use Pliic\Testing\FakeHttpClient;

final class FakeHttpClientTest extends TestCase
{
    private function client(FakeHttpClient $fake): PliicClient
    {
        return new PliicClient('sk_live_test', 'https://pliic.test', $fake);
    }

    public function test_lists_suggestions_with_a_realistic_default_without_setup(): void
    {
        $fake = new FakeHttpClient;

        $result = $this->client($fake)->suggestions->list();

        $this->assertNotEmpty($result['data']);
        $this->assertArrayHasKey('title', $result['data'][0]);
        $this->assertArrayHasKey('meta', $result);
    }

    public function test_gets_a_suggestion_with_a_realistic_default(): void
    {
        $fake = new FakeHttpClient;

        $result = $this->client($fake)->suggestions->get(1);

        $this->assertSame(1, $result['data']['id']);
        $this->assertArrayHasKey('status', $result['data']);
    }

    public function test_gets_a_ticket_detail_with_a_realistic_default_including_messages(): void
    {
        $fake = new FakeHttpClient;

        $result = $this->client($fake)->tickets->get(1);

        $this->assertArrayHasKey('messages', $result['data']);
        $this->assertNotEmpty($result['data']['messages']);
    }

    public function test_lists_tickets_without_messages_by_default(): void
    {
        $fake = new FakeHttpClient;

        $result = $this->client($fake)->tickets->list();

        $this->assertArrayNotHasKey('messages', $result['data'][0]);
    }

    public function test_gets_analytics_with_a_realistic_default(): void
    {
        $fake = new FakeHttpClient;

        $result = $this->client($fake)->analytics->get();

        $this->assertArrayHasKey('suggestions', $result['data']);
        $this->assertArrayHasKey('tickets', $result['data']);
    }

    public function test_gets_survey_results_with_a_realistic_default(): void
    {
        $fake = new FakeHttpClient;

        $result = $this->client($fake)->surveys->results(3);

        $this->assertArrayHasKey('completion_rate', $result['data']);
    }

    public function test_seed_suggestion_overrides_are_reflected_in_the_response(): void
    {
        $fake = new FakeHttpClient;
        $fake->seedSuggestion(['id' => 42, 'title' => 'Dark mode']);

        $result = $this->client($fake)->suggestions->get(42);

        $this->assertSame(42, $result['data']['id']);
        $this->assertSame('Dark mode', $result['data']['title']);
    }

    public function test_seed_suggestion_created_returns_a_201(): void
    {
        $fake = new FakeHttpClient;
        $fake->seedSuggestionCreated(['id' => 10, 'title' => 'Dark mode']);

        $result = $this->client($fake)->suggestions->create(['user' => ['id' => 'u_1'], 'title' => 'Dark mode']);

        $this->assertSame(10, $result['data']['id']);
    }

    public function test_seed_ticket_reply_returns_a_201(): void
    {
        $fake = new FakeHttpClient;
        $fake->seedTicketReply(['body' => 'More detail here']);

        $result = $this->client($fake)->tickets->reply(7, ['user' => ['id' => 'u_1'], 'body' => 'More detail here']);

        $this->assertSame('More detail here', $result['data']['body']);
    }

    public function test_seed_error_maps_to_the_matching_exception(): void
    {
        $fake = new FakeHttpClient;
        $fake->seedError(404, 'Not found');

        $this->expectException(NotFoundException::class);

        $this->client($fake)->suggestions->get(999);
    }

    public function test_owned_by_denies_reads_for_a_different_user_email(): void
    {
        $fake = (new FakeHttpClient)->ownedByEmail('owner@example.com');
        $fake->seedTicket();

        $this->expectException(NotFoundException::class);

        $this->client($fake)->tickets->get(7, ['user_email' => 'stranger@example.com']);
    }

    public function test_owned_by_allows_reads_for_the_matching_user_email(): void
    {
        $fake = (new FakeHttpClient)->ownedByEmail('owner@example.com');
        $fake->seedTicket(['id' => 7]);

        $result = $this->client($fake)->tickets->get(7, ['user_email' => 'owner@example.com']);

        $this->assertSame(7, $result['data']['id']);
    }

    public function test_owned_by_user_id_denies_reads_for_a_different_id(): void
    {
        $fake = (new FakeHttpClient)->ownedByUserId('u_owner');
        $fake->seedTicket();

        $this->expectException(NotFoundException::class);

        $this->client($fake)->tickets->get(7, ['user_id' => 'u_stranger']);
    }

    public function test_fail_next_with_transport_error_throws_on_the_next_call_only(): void
    {
        $fake = new FakeHttpClient;
        $fake->failNextWithTransportError();

        try {
            $this->client($fake)->suggestions->list();
            $this->fail('Expected a TransportException.');
        } catch (TransportException) {
            // expected
        }

        $result = $this->client($fake)->suggestions->list();
        $this->assertNotEmpty($result['data']);
    }

    public function test_records_every_request_made(): void
    {
        $fake = new FakeHttpClient;
        $client = $this->client($fake);

        $client->suggestions->list();
        $client->tickets->list();

        $this->assertSame(2, $fake->requestCount());
        $this->assertSame('GET', $fake->requests[0]['method']);
    }

    public function test_last_request_body_decodes_the_json_payload(): void
    {
        $fake = new FakeHttpClient;

        $this->client($fake)->suggestions->create(['user' => ['id' => 'u_1'], 'title' => 'Dark mode']);

        $this->assertSame('Dark mode', $fake->lastRequestBody()['title']);
    }

    public function test_assert_requested_passes_for_a_matching_request(): void
    {
        $fake = new FakeHttpClient;
        $this->client($fake)->suggestions->vote(5, ['user' => ['id' => 'u_1']]);

        $fake->assertRequested('POST', '/suggestions/5/vote');

        $this->addToAssertionCount(1);
    }

    public function test_assert_requested_throws_for_a_missing_request(): void
    {
        $fake = new FakeHttpClient;

        $this->expectException(\RuntimeException::class);

        $fake->assertRequested('POST', '/suggestions/5/vote');
    }

    public function test_assert_not_requested_throws_when_the_request_did_happen(): void
    {
        $fake = new FakeHttpClient;
        $this->client($fake)->suggestions->list();

        $this->expectException(\RuntimeException::class);

        $fake->assertNotRequested('GET', '/suggestions');
    }

    public function test_assert_request_count_throws_on_mismatch(): void
    {
        $fake = new FakeHttpClient;
        $this->client($fake)->suggestions->list();

        $this->expectException(\RuntimeException::class);

        $fake->assertRequestCount(2);
    }

    public function test_explicit_default_response_overrides_the_realistic_default(): void
    {
        $fake = new FakeHttpClient(200, '{"data":[]}');

        $result = $this->client($fake)->suggestions->list();

        $this->assertSame([], $result['data']);
    }

    public function test_queue_takes_priority_over_the_realistic_default(): void
    {
        $fake = new FakeHttpClient;
        $fake->queue(200, ['data' => ['id' => 999, 'title' => 'Queued suggestion']]);

        $result = $this->client($fake)->suggestions->get(1);

        $this->assertSame(999, $result['data']['id']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    public static function creationEndpoints(): array
    {
        return [
            'create suggestion' => ['POST', '/suggestions', 201],
            'add suggestion comment' => ['POST', '/suggestions/5/comments', 201],
            'vote on a suggestion' => ['POST', '/suggestions/5/vote', 200],
            'create ticket' => ['POST', '/tickets', 201],
            'reply to a ticket' => ['POST', '/tickets/7/replies', 201],
            'list suggestions' => ['GET', '/suggestions', 200],
            'list tickets' => ['GET', '/tickets', 200],
        ];
    }

    #[DataProvider('creationEndpoints')]
    public function test_realistic_defaults_use_the_status_code_documented_by_the_openapi_spec(string $method, string $path, int $expectedStatus): void
    {
        $fake = new FakeHttpClient;

        $response = $fake->request($method, "https://pliic.test/api/v1{$path}", []);

        $this->assertSame($expectedStatus, $response->status);
    }

    public function test_route_matching_is_anchored_and_does_not_match_a_longer_path_ending_the_same_way(): void
    {
        $fake = new FakeHttpClient;

        $response = $fake->request('GET', 'https://pliic.test/api/v1/tickets/5/replies/suggestions', []);

        $this->assertSame('{"data":[]}', $response->body);
    }
}
