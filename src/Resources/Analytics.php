<?php

declare(strict_types=1);

namespace Pliic\Resources;

class Analytics extends AbstractResource
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return $this->client->request('GET', '/analytics');
    }
}
