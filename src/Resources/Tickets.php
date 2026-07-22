<?php

declare(strict_types=1);

namespace Pliic\Resources;

class Tickets extends AbstractResource
{
    /**
     * @param  array{page?: int, user_id?: string, user_email?: string}  $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/tickets', $params);
    }

    /**
     * Ticket detail including the public message thread.
     *
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        return $this->client->request('GET', "/tickets/{$id}");
    }

    /**
     * @param  array{user?: array{id: string, name?: string, email?: string}, app_user_id?: int, subject: string, body: string, type?: string, priority?: string, tags?: array<int, string>}  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->request('POST', '/tickets', [], $payload);
    }

    /**
     * Replies as the ticket author. The user must match the ticket's author.
     *
     * @param  array{user?: array{id: string, name?: string, email?: string}, app_user_id?: int, body: string}  $payload
     * @return array<string, mixed>
     */
    public function reply(int $id, array $payload): array
    {
        return $this->client->request('POST', "/tickets/{$id}/replies", [], $payload);
    }
}
