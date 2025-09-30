<?php

namespace App\Services\Storage;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LifecycleManager
{
    /**
     * @var callable|null
     */
    private $clientFactory;

    public function __construct(private readonly Config $config, ?callable $clientFactory = null)
    {
        $this->clientFactory = $clientFactory;
    }

    public function apply(string $profileKey): array
    {
        $profile = $this->getProfile($profileKey);

        $client = $this->makeClient($profile);
        $configuration = $this->formatLifecycleConfiguration($profile);

        try {
            $client->putBucketLifecycleConfiguration([
                'Bucket' => $profile['bucket'],
                'LifecycleConfiguration' => $configuration,
            ]);
        } catch (S3Exception $exception) {
            Log::error('Failed to apply S3 lifecycle policy', [
                'profile' => $profileKey,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to apply S3 lifecycle policy: ' . $exception->getAwsErrorMessage(), 0, $exception);
        }

        return $configuration;
    }

    public function requestRestore(string $profileKey, string $objectKey, int $days = 2): void
    {
        $profile = $this->getProfile($profileKey);
        $client = $this->makeClient($profile);

        try {
            $client->restoreObject([
                'Bucket' => $profile['bucket'],
                'Key' => $objectKey,
                'RestoreRequest' => [
                    'Days' => max(1, $days),
                    'GlacierJobParameters' => [
                        'Tier' => 'Standard',
                    ],
                ],
            ]);
        } catch (S3Exception $exception) {
            Log::error('Failed to request object restore.', [
                'profile' => $profileKey,
                'key' => $objectKey,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Unable to request object restore: ' . $exception->getAwsErrorMessage(), 0, $exception);
        }
    }

    public function getProfile(string $profileKey): array
    {
        $profile = Arr::get($this->config->get('storage_lifecycle.profiles'), $profileKey);

        if (! is_array($profile) || empty($profile['bucket'])) {
            throw new RuntimeException(sprintf('Lifecycle profile [%s] is not configured.', $profileKey));
        }

        return $profile;
    }

    public function formatLifecycleConfiguration(array $profile): array
    {
        $prefix = Arr::get($profile, 'prefix');

        $rules = [
            [
                'ID' => ($prefix ? trim($prefix, '/') : 'root') . '-transition',
                'Filter' => array_filter([
                    'Prefix' => $prefix,
                ]),
                'Status' => 'Enabled',
                'Transitions' => $this->buildTransitions($profile),
                'AbortIncompleteMultipartUpload' => [
                    'DaysAfterInitiation' => Arr::get($profile, 'abort_multipart_days', 7),
                ],
            ],
        ];

        if ($expirationDays = Arr::get($profile, 'expiration_days')) {
            $rules[0]['Expiration'] = ['Days' => (int) $expirationDays];
        }

        if ($noncurrent = Arr::get($profile, 'noncurrent_versions')) {
            $rules[0]['NoncurrentVersionTransitions'] = [
                [
                    'NoncurrentDays' => Arr::get($noncurrent, 'transition.days', 30),
                    'StorageClass' => Arr::get($noncurrent, 'transition.storage_class', 'GLACIER'),
                ],
            ];

            if ($noncurrentExpiration = Arr::get($noncurrent, 'expiration_days')) {
                $rules[0]['NoncurrentVersionExpiration'] = [
                    'NoncurrentDays' => (int) $noncurrentExpiration,
                ];
            }
        }

        return ['Rules' => $rules];
    }

    private function makeClient(array $profile): S3Client
    {
        $clientConfig = array_merge([
            'version' => 'latest',
            'region' => env('CLOUDFLARE_R2_DEFAULT_REGION', env('AWS_DEFAULT_REGION')),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT', env('AWS_ENDPOINT')),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', false)),
            'credentials' => [
                'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
                'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
            ],
        ], Arr::get($profile, 'client', []));

        if ($this->clientFactory) {
            return ($this->clientFactory)($clientConfig);
        }

        return new S3Client($clientConfig);
    }

    private function buildTransitions(array $profile): array
    {
        $transitions = [];

        foreach (Arr::get($profile, 'transitions', []) as $transition) {
            if (! isset($transition['storage_class'], $transition['days'])) {
                continue;
            }

            $transitions[] = [
                'StorageClass' => $transition['storage_class'],
                'Days' => (int) $transition['days'],
            ];
        }

        if (empty($transitions)) {
            throw new RuntimeException('At least one lifecycle transition must be defined.');
        }

        return $transitions;
    }
}
