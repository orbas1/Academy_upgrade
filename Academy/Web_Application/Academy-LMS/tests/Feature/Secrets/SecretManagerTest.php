<?php

namespace Tests\Feature\Secrets;

use App\Support\Secrets\Drivers\EnvSecretDriver;
use App\Support\Secrets\SecretManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Psr\Log\NullLogger;
use Tests\TestCase;

class SecretManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('secrets', [
            'default' => 'env',
            'cache_ttl' => 60,
            'drivers' => [
                'env' => [
                    'class' => EnvSecretDriver::class,
                    'prefix' => 'PHPUNIT',
                ],
            ],
        ]);

        $this->app->bind(CacheRepository::class, fn () => Cache::driver());
    }

    public function testItResolvesSecretsFromEnv(): void
    {
        putenv('PHPUNIT_SAMPLE_SECRET=secret-value');

        $manager = $this->makeManager();
        $secret = $manager->get('SAMPLE_SECRET');

        $this->assertSame('SAMPLE_SECRET', $secret->key);
        $this->assertSame('secret-value', $secret->value);
        $this->assertSame('env', $secret->version);
        $this->assertNotNull($secret->retrievedAt);
    }

    public function testItCachesSecrets(): void
    {
        putenv('PHPUNIT_CACHED_SECRET=initial');

        $manager = $this->makeManager();
        $first = $manager->get('CACHED_SECRET');
        $this->assertSame('initial', $first->value);

        putenv('PHPUNIT_CACHED_SECRET=rotated');

        $cached = $manager->get('CACHED_SECRET');
        $this->assertSame('initial', $cached->value);

        $manager->forget('CACHED_SECRET');
        $refreshed = $manager->get('CACHED_SECRET');
        $this->assertSame('rotated', $refreshed->value);
    }

    protected function makeManager(): SecretManager
    {
        return new SecretManager(
            container: $this->app,
            cache: $this->app->make(CacheRepository::class),
            logger: new NullLogger(),
            config: $this->app['config']->get('secrets'),
        );
    }
}
