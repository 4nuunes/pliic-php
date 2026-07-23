<?php

declare(strict_types=1);

namespace Pliic\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Pliic\Exceptions\ApiErrorException;
use Pliic\Exceptions\AuthenticationException;
use Pliic\Exceptions\NotFoundException;
use Pliic\Exceptions\PermissionException;
use Pliic\Exceptions\RateLimitException;
use Pliic\Exceptions\ValidationException;
use Pliic\PliicClient;

final class PliicClientTest extends TestCase
{
    private function client(FakeHttpClient $http): PliicClient
    {
        return new PliicClient('sk_live_test', 'https://pliic.test', $http);
    }

    public function test_rejects_a_non_secret_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PliicClient('pk_live_public');
    }

    public function test_sends_bearer_auth_and_json_headers(): void
    {
        $http = new FakeHttpClient;

        $this->client($http)->suggestions->list();

        $request = $http->lastRequest();

        $this->assertSame('GET', $request['method']);
        $this->assertSame('https://pliic.test/api/v1/suggestions', $request['url']);
        $this->assertSame('Bearer sk_live_test', $request['headers']['Authorization']);
        $this->assertSame('application/json', $request['headers']['Accept']);
    }

    public function test_builds_query_strings_and_drops_empty_params(): void
    {
        $http = new FakeHttpClient;

        $this->client($http)->suggestions->list(['status' => 'planned', 'search' => '', 'page' => 2]);

        $this->assertSame(
            'https://pliic.test/api/v1/suggestions?status=planned&page=2',
            $http->lastRequest()['url'],
        );
    }

    public function test_encodes_post_bodies_as_json(): void
    {
        $http = new FakeHttpClient(201, '{"data":{"id":1}}');

        $result = $this->client($http)->suggestions->create([
            'user' => ['id' => 'u_1'],
            'title' => 'Dark mode',
        ]);

        $request = $http->lastRequest();

        $this->assertSame('POST', $request['method']);
        $this->assertSame('application/json', $request['headers']['Content-Type']);
        $this->assertSame(['user' => ['id' => 'u_1'], 'title' => 'Dark mode'], json_decode((string) $request['body'], true));
        $this->assertSame(['data' => ['id' => 1]], $result);
    }

    public function test_resource_methods_hit_the_expected_endpoints(): void
    {
        $http = new FakeHttpClient;
        $client = $this->client($http);

        $client->suggestions->vote(5, ['user' => ['id' => 'u_1']]);
        $this->assertSame('https://pliic.test/api/v1/suggestions/5/vote', $http->lastRequest()['url']);

        $client->suggestions->addComment(5, ['user' => ['id' => 'u_1'], 'body' => 'Nice']);
        $this->assertSame('https://pliic.test/api/v1/suggestions/5/comments', $http->lastRequest()['url']);

        $client->tickets->reply(9, ['user' => ['id' => 'u_1'], 'body' => 'More info']);
        $this->assertSame('https://pliic.test/api/v1/tickets/9/replies', $http->lastRequest()['url']);

        $client->tickets->get(9);
        $this->assertSame('https://pliic.test/api/v1/tickets/9', $http->lastRequest()['url']);

        $client->tickets->get(9, ['user_id' => 'u_1']);
        $this->assertSame('https://pliic.test/api/v1/tickets/9?user_id=u_1', $http->lastRequest()['url']);

        $client->tickets->get(9, ['user_email' => 'ana@example.com']);
        $this->assertSame('https://pliic.test/api/v1/tickets/9?user_email=ana%40example.com', $http->lastRequest()['url']);

        $client->surveys->results(3);
        $this->assertSame('https://pliic.test/api/v1/surveys/3/results', $http->lastRequest()['url']);

        $client->analytics->get();
        $this->assertSame('https://pliic.test/api/v1/analytics', $http->lastRequest()['url']);

        $client->privacy->erase(11);
        $this->assertSame('DELETE', $http->lastRequest()['method']);
        $this->assertSame('https://pliic.test/api/v1/privacy/11', $http->lastRequest()['url']);
    }

    /**
     * @return array<string, array{0: int, 1: class-string<ApiErrorException>}>
     */
    public static function errorStatuses(): array
    {
        return [
            '401' => [401, AuthenticationException::class],
            '403' => [403, PermissionException::class],
            '404' => [404, NotFoundException::class],
            '422' => [422, ValidationException::class],
            '429' => [429, RateLimitException::class],
            '500' => [500, ApiErrorException::class],
        ];
    }

    /**
     * @param  class-string<ApiErrorException>  $exceptionClass
     */
    #[DataProvider('errorStatuses')]
    public function test_maps_error_statuses_to_typed_exceptions(int $status, string $exceptionClass): void
    {
        $http = new FakeHttpClient($status, '{"message":"Something failed"}');

        try {
            $this->client($http)->suggestions->list();
            $this->fail('Expected an ApiErrorException.');
        } catch (ApiErrorException $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertSame($status, $exception->status);
            $this->assertSame('Something failed', $exception->getMessage());
        }
    }

    public function test_validation_exception_exposes_field_errors(): void
    {
        $http = new FakeHttpClient(422, '{"message":"Invalid","errors":{"title":["Required"]}}');

        try {
            $this->client($http)->suggestions->create(['user' => ['id' => 'u_1'], 'title' => '']);
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame(['title' => ['Required']], $exception->errors());
        }
    }
}
