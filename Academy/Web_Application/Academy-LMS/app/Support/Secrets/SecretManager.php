<?php

namespace App\Support\Secrets;

use App\Support\Secrets\Contracts\SecretDriver;
use App\Support\Secrets\Exceptions\SecretNotFoundException;
use App\Support\Secrets\Exceptions\SecretRotationNotSupported;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class SecretManager
{
    /** @var array<string, SecretDriver> */
    private array $drivers = [];

    public function __construct(
        private readonly Container $container,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
        private readonly array $config,
    ) {
    }

    public function get(string $key, ?string $driver = null, array $options = []): SecretValue
    {
        $driverName = $driver ?: $this->config['default'];
        $cacheKey = $this->cacheKey($driverName, $key, $options);
        $ttl = (int) ($this->config['cache_ttl'] ?? 300);

        if ($ttl > 0) {
            $value = $this->cache->remember($cacheKey, $ttl, fn () => $this->resolveSecret($driverName, $key, $options));
        } else {
            $value = $this->resolveSecret($driverName, $key, $options);
        }

        if (! $value) {
            throw SecretNotFoundException::forKey($key, $driverName);
        }

        return $value;
    }

    public function forget(string $key, ?string $driver = null, array $options = []): void
    {
        $driverName = $driver ?: $this->config['default'];
        $this->cache->forget($this->cacheKey($driverName, $key, $options));
    }

    public function put(string $key, string $value, ?string $driver = null, array $options = []): SecretRotationResult
    {
        $driverName = $driver ?: $this->config['default'];
        $result = $this->driver($driverName)->put($key, $value, $options);

        $this->forget($key, $driverName, $options);

        return $result;
    }

    public function rotate(string $key, ?string $driver = null, array $options = []): SecretRotationResult
    {
        $driverName = $driver ?: $this->config['default'];
        $driverInstance = $this->driver($driverName);

        if (! method_exists($driverInstance, 'rotate')) {
            throw SecretRotationNotSupported::forDriver($driverName);
        }

        $result = $driverInstance->rotate($key, $options);

        $this->forget($key, $driverName, $options);

        return $result;
    }

    public function driver(string $driver): SecretDriver
    {
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        $config = Arr::get($this->config, "drivers.{$driver}");
        if (! is_array($config) || ! isset($config['class'])) {
            throw new \InvalidArgumentException("Secret driver [{$driver}] is not configured.");
        }

        $class = $config['class'];
        unset($config['class']);

        $instance = $this->container->make($class, $config);
        if (! $instance instanceof SecretDriver) {
            throw new \InvalidArgumentException("Secret driver [{$driver}] must implement " . SecretDriver::class);
        }

        return $this->drivers[$driver] = $instance;
    }

    private function resolveSecret(string $driver, string $key, array $options): ?SecretValue
    {
        try {
            return $this->driver($driver)->get($key, $options);
        } catch (SecretNotFoundException $exception) {
            $this->logger->warning('Secret missing', [
                'driver' => $driver,
                'key' => $key,
            ]);

            throw $exception;
        }
    }

    private function cacheKey(string $driver, string $key, array $options = []): string
    {
        $suffix = '';
        if ($options !== []) {
            $suffix = ':' . md5(json_encode($options, JSON_THROW_ON_ERROR));
        }

        return sprintf('secrets.%s.%s%s', $driver, Str::slug($key), $suffix);
    }
}
