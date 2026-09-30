<?php

declare(strict_types=1);

namespace Tests\Unit;

use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connectors\PredisConnector;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Env;
use Predis\Client;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * The Redis client is a dependency choice, not a preference.
 *
 * Laravel ships a connector for each client it knows, so `class_exists()` on a
 * connector proves nothing on its own: `PhpRedisConnector` is always present in
 * the framework and only fails later, at connect time, because the client it
 * drives is the `ext-redis` extension, which this project neither declares in
 * composer.json nor has loaded. `predis/predis` is a Composer package, so its
 * connector works only because that package is installed. A configured client
 * whose driver is absent is a configuration that cannot work at all, so these
 * tests hold the configured client to the driver this project actually ships.
 */
class RedisClientTest extends TestCase
{
    public function test_configured_client_is_the_driver_the_project_installs(): void
    {
        $require = $this->requiredComposerPackages();

        // The driver package is installed; the extension is not declared, so
        // nothing here can be relying on the extension-backed client.
        $this->assertArrayHasKey('predis/predis', $require);
        $this->assertArrayNotHasKey('ext-redis', $require);

        $this->assertSame(
            'predis',
            $this->defaultRedisClient(),
            'The configured Redis client must be the one whose driver package this project installs.'
        );
    }

    public function test_configured_client_resolves_to_a_usable_connector(): void
    {
        config(['database.redis.client' => $this->defaultRedisClient()]);

        // Asked of the framework rather than restated here: RedisManager builds
        // a connector per client name and returns null for one it does not know.
        $connector = $this->connectorForConfiguredClient();

        $this->assertInstanceOf(Connector::class, $connector);
        $this->assertInstanceOf(PredisConnector::class, $connector);
        $this->assertTrue(class_exists(Client::class));
    }

    /**
     * The client config/database.php falls back to when nothing in the
     * environment answers REDIS_CLIENT.
     *
     * The fallback is the value under test, and a developer's own .env is not
     * this project's default and not the suite's to edit: with the key present
     * the fallback is never reached, so the file is evaluated here against an
     * empty environment repository instead of the booted one.
     *
     * @return mixed
     */
    private function defaultRedisClient()
    {
        $repository = new ReflectionProperty(Env::class, 'repository');

        $booted = $repository->getValue();
        $repository->setValue(RepositoryBuilder::createWithNoAdapters()->immutable()->make());

        try {
            $config = require base_path('config/database.php');
        } finally {
            $repository->setValue($booted);
        }

        return $config['redis']['client'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function requiredComposerPackages(): array
    {
        $composer = json_decode(
            (string) file_get_contents(base_path('composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        /** @var array<string, string> $require */
        $require = $composer['require'] ?? [];

        return $require;
    }

    private function connectorForConfiguredClient(): ?Connector
    {
        $manager = $this->app->make(RedisManager::class);

        $connector = (new ReflectionMethod($manager, 'connector'))->invoke($manager);

        return $connector instanceof Connector ? $connector : null;
    }
}
