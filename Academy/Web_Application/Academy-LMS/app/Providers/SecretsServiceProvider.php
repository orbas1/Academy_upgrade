<?php

namespace App\Providers;

use App\Support\Secrets\Drivers\AwsSecretsManagerDriver;
use App\Support\Secrets\Drivers\EnvSecretDriver;
use App\Support\Secrets\Drivers\HashicorpVaultDriver;
use App\Support\Secrets\SecretManager;
use Aws\SecretsManager\SecretsManagerClient;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class SecretsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SecretManager::class, function (Container $app) {
            $config = $app['config']->get('secrets', []);

            return new SecretManager(
                container: $app,
                cache: $app->make(CacheRepository::class),
                logger: $app->make(LoggerInterface::class),
                config: $config,
            );
        });

        $this->registerDrivers();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }

    public function provides(): array
    {
        return [SecretManager::class];
    }

    private function registerDrivers(): void
    {
        $this->app->bind(AwsSecretsManagerDriver::class, function (Container $app, array $parameters) {
            $config = array_merge($this->driverConfig('aws'), $parameters);
            $client = $config['client'] ?? new SecretsManagerClient([
                'version' => 'latest',
                'region' => $config['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1'),
                'credentials' => $config['credentials'] ?? null,
                'endpoint' => $config['endpoint'] ?? null,
            ]);

            return new AwsSecretsManagerDriver(
                client: $client,
                logger: $app->make(LoggerInterface::class),
            );
        });

        $this->app->bind(HashicorpVaultDriver::class, function (Container $app, array $parameters) {
            $config = array_merge($this->driverConfig('vault'), $parameters);
            $baseUri = Arr::get($config, 'base_uri');
            $token = Arr::get($config, 'token');
            $mount = Arr::get($config, 'mount', 'secret');
            $namespace = Arr::get($config, 'namespace');

            if (! $baseUri || ! $token) {
                throw new \InvalidArgumentException('Vault driver requires a base_uri and token.');
            }

            return new HashicorpVaultDriver(
                http: $app->make(HttpFactory::class),
                logger: $app->make(LoggerInterface::class),
                baseUri: $baseUri,
                token: $token,
                mount: $mount,
                namespace: $namespace,
            );
        });

        $this->app->bind(EnvSecretDriver::class, function (Container $app, array $parameters) {
            $config = array_merge($this->driverConfig('env'), $parameters);

            return new EnvSecretDriver(prefix: Arr::get($config, 'prefix'));
        });
    }

    private function driverConfig(string $driver): array
    {
        return (array) data_get(config('secrets.drivers'), $driver, []);
    }
}
