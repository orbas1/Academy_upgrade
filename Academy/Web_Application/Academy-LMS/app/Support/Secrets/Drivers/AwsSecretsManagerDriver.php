<?php

namespace App\Support\Secrets\Drivers;

use App\Support\Secrets\Contracts\SecretDriver;
use App\Support\Secrets\Exceptions\SecretNotFoundException;
use App\Support\Secrets\SecretRotationResult;
use App\Support\Secrets\SecretValue;
use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class AwsSecretsManagerDriver implements SecretDriver
{
    public function __construct(
        private readonly SecretsManagerClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function get(string $key, array $options = []): ?SecretValue
    {
        $request = [
            'SecretId' => $key,
        ];

        if ($stage = Arr::get($options, 'stage')) {
            $request['VersionStage'] = $stage;
        }

        try {
            $result = $this->client->getSecretValue($request);
        } catch (AwsException $exception) {
            if ($exception->getAwsErrorCode() === 'ResourceNotFoundException') {
                return null;
            }

            $this->logger->error('Failed to fetch AWS secret', [
                'secret' => $key,
                'message' => $exception->getMessage(),
                'code' => $exception->getAwsErrorCode(),
            ]);

            throw $exception;
        }

        $secretString = $result['SecretString'] ?? null;
        if ($secretString === null && isset($result['SecretBinary'])) {
            $secretString = base64_decode($result['SecretBinary']);
        }

        if ($secretString === null) {
            throw SecretNotFoundException::forKey($key, 'aws');
        }

        $createdDate = $result['CreatedDate'] ?? null;
        $rotatedDate = $result['LastChangedDate'] ?? $createdDate;

        return new SecretValue(
            key: $key,
            value: (string) $secretString,
            version: (string) ($result['VersionId'] ?? Str::uuid()->toString()),
            retrievedAt: CarbonImmutable::now(),
            rotatedAt: $rotatedDate ? CarbonImmutable::instance($rotatedDate) : null,
            metadata: [
                'arn' => $result['ARN'] ?? null,
                'version_stage' => $result['VersionStages'] ?? [],
            ],
        );
    }

    public function put(string $key, string $value, array $options = []): SecretRotationResult
    {
        $request = [
            'SecretId' => $key,
            'SecretString' => $value,
        ];

        if ($kmsKey = Arr::get($options, 'kms_key_id')) {
            $request['KmsKeyId'] = $kmsKey;
        }

        $this->client->putSecretValue($request);

        $rotatedAt = CarbonImmutable::now();

        return new SecretRotationResult(
            key: $key,
            version: (string) Arr::get($request, 'ClientRequestToken', Str::uuid()->toString()),
            rotatedAt: $rotatedAt,
            metadata: ['source' => 'aws'],
        );
    }

    public function rotate(string $key, array $options = []): SecretRotationResult
    {
        $request = [
            'SecretId' => $key,
        ];

        if ($lambda = Arr::get($options, 'rotation_lambda')) {
            $request['RotationLambdaARN'] = $lambda;
        }

        if ($rotationRules = Arr::get($options, 'rotation_rules')) {
            $request['RotationRules'] = $rotationRules;
        }

        $this->client->rotateSecret($request);

        $this->logger->info('Triggered AWS secret rotation', ['secret' => $key]);

        return new SecretRotationResult(
            key: $key,
            version: (string) Arr::get($request, 'ClientRequestToken', Str::uuid()->toString()),
            rotatedAt: CarbonImmutable::now(),
            metadata: ['source' => 'aws'],
        );
    }
}
