<?php

declare(strict_types=1);

namespace Pliic\Tests\Laravel;

use Pliic\Laravel\PliicServiceProvider;
use Pliic\PliicClient;

final class PliicServiceProviderTest extends TestCase
{
    public function test_it_registers_the_client_as_a_singleton_built_from_config(): void
    {
        $client = $this->app->make(PliicClient::class);

        $this->assertInstanceOf(PliicClient::class, $client);
        $this->assertSame($client, $this->app->make(PliicClient::class));
    }

    public function test_the_config_file_is_publishable_under_the_pliic_config_tag(): void
    {
        $paths = PliicServiceProvider::pathsToPublish(PliicServiceProvider::class, 'pliic-config');

        $this->assertNotEmpty($paths);

        $source = array_key_first($paths);
        $destination = $paths[$source];

        $this->assertFileExists($source);
        $this->assertStringEndsWith('config/pliic.php', $destination);
    }

    public function test_a_missing_api_key_surfaces_as_the_clients_own_validation_error(): void
    {
        config(['pliic.api_key' => null]);
        $this->app->forgetInstance(PliicClient::class);

        $this->expectException(\InvalidArgumentException::class);

        $this->app->make(PliicClient::class);
    }
}
