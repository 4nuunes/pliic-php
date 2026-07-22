<?php

declare(strict_types=1);

namespace Pliic\Resources;

class Privacy extends AbstractResource
{
    /**
     * GDPR/LGPD data export for an app user (internal id).
     *
     * @return array<string, mixed>
     */
    public function export(int $appUserId): array
    {
        return $this->client->request('GET', "/privacy/export/{$appUserId}");
    }

    /**
     * GDPR/LGPD erasure for an app user (internal id).
     *
     * @return array<string, mixed>
     */
    public function erase(int $appUserId): array
    {
        return $this->client->request('DELETE', "/privacy/{$appUserId}");
    }
}
