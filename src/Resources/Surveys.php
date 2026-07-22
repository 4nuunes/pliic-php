<?php

declare(strict_types=1);

namespace Pliic\Resources;

class Surveys extends AbstractResource
{
    /**
     * @param  array{page?: int}  $params
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->client->request('GET', '/surveys', $params);
    }

    /**
     * @return array<string, mixed>
     */
    public function results(int $id): array
    {
        return $this->client->request('GET', "/surveys/{$id}/results");
    }
}
