<?php

namespace App\Support\Secrets\Drivers;

use App\Support\Secrets\Contracts\SecretDriver;
use App\Support\Secrets\Exceptions\SecretNotFoundException;
use App\Support\Secrets\SecretRotationResult;
use App\Support\Secrets\SecretValue;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class HashicorpVaultDriver implements SecretDriver
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly LoggerInterface $logger,
        private readonly string $baseUri,
        private readonly string $token,
        private readonly string $mount,
        private readonly ?string $namespace = null,
    ) {
    }

    public function get(string $key, array $options = []): ?SecretValue
    {
        $version = Arr::get($options, 'version');
        $uri = $this->resolvePath($key, 'data');

        $response = $this->http()
            ->get($uri, $version ? ['version' => $version] : []);

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        $payload = $response->json();
        $data = Arr::get($payload, 'data.data');
        if (! is_array($data)) {
            throw SecretNotFoundException::forKey($key, 'vault');
        }

        $metadata = Arr::get($payload, 'data.metadata', []);
        $versionId = (string) Arr::get($metadata, 'version', '1');
        $createdTime = Arr::get($metadata, 'created_time');
        $updatedTime = Arr::get($metadata, 'updated_time', $createdTime);

        $value = Arr::get($data, 'value');
        if (! is_string($value)) {
            $value = json_encode($data, JSON_THROW_ON_ERROR);
        }

        return new SecretValue(
            key: $key,
            value: $value,
            version: $versionId,
            retrievedAt: CarbonImmutable::now(),
            rotatedAt: $updatedTime ? CarbonImmutable::parse($updatedTime) : null,
            metadata: $metadata,
        );
    }

    public function put(string $key, string $value, array $options = []): SecretRotationResult
    {
        $payload = ['data' => ['value' => $value]];
        $metadata = Arr::get($options, 'metadata');
        if (is_array($metadata)) {
            $payload['options'] = ['cas' => Arr::get($metadata, 'cas')];
        }

        $response = $this->http()->post($this->resolvePath($key, 'data'), $payload);
        $response->throw();

        $version = (string) Arr::get($response->json(), 'data.version', Str::uuid()->toString());

        return new SecretRotationResult(
            key: $key,
            version: $version,
            rotatedAt: CarbonImmutable::now(),
            metadata: ['source' => 'vault'],
        );
    }

    public function rotate(string $key, array $options = []): SecretRotationResult
    {
        $rotationValue = Arr::get($options, 'value');
        if (! is_string($rotationValue)) {
            $rotationValue = (string) Str::uuid();
        }

        $result = $this->put($key, $rotationValue, $options);

        $this->logger->info('Vault secret rotated', ['secret' => $key]);

        return $result;
    }

    private function resolvePath(string $key, string $action): string
    {
        return sprintf('v1/%s/%s/%s', trim($this->mount, '/'), $action, ltrim($key, '/'));
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $request = $this->http->baseUrl($this->baseUri)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(10);

        if ($this->namespace) {
            $request->withHeaders(['X-Vault-Namespace' => $this->namespace]);
        }

        return $request;
    }
}
