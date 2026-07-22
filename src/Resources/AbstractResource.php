<?php

declare(strict_types=1);

namespace Pliic\Resources;

use Pliic\PliicClient;

abstract class AbstractResource
{
    public function __construct(protected readonly PliicClient $client) {}
}
