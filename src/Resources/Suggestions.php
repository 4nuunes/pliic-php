<?php

declare(strict_types=1);

namespace Pliic\Resources;

class Suggestions extends AbstractResource
{
    /**
     * @param  array{page?: int, status?: string, search?: string, user_id?: string, user_email?: string}  $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/suggestions', $params);
    }

    /**
     * @param  array{user_id?: string, user_email?: string}  $params
     * @return array<string, mixed>
     */
    public function get(int $id, array $params = []): array
    {
        return $this->client->request('GET', "/suggestions/{$id}", $params);
    }

    /**
     * @param  array{user?: array{id: string, name?: string, email?: string}, app_user_id?: int, title: string, description?: string}  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->request('POST', '/suggestions', [], $payload);
    }

    /**
     * Toggles the vote: voting twice with the same user removes it.
     *
     * @param  array{user?: array{id: string, name?: string, email?: string}, app_user_id?: int}  $payload
     * @return array<string, mixed>
     */
    public function vote(int $id, array $payload): array
    {
        return $this->client->request('POST', "/suggestions/{$id}/vote", [], $payload);
    }

    /**
     * @param  array{page?: int}  $params
     * @return array<string, mixed>
     */
    public function comments(int $id, array $params = []): array
    {
        return $this->client->request('GET', "/suggestions/{$id}/comments", $params);
    }

    /**
     * @param  array{user?: array{id: string, name?: string, email?: string}, app_user_id?: int, body: string, parent_id?: int}  $payload
     * @return array<string, mixed>
     */
    public function addComment(int $id, array $payload): array
    {
        return $this->client->request('POST', "/suggestions/{$id}/comments", [], $payload);
    }
}
