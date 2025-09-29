<?php

namespace Tests\Unit\Services\Storage;

use App\Services\Storage\LifecycleManager;
use Aws\S3\S3Client;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;

class FakeS3Client extends S3Client
{
    public array $putRequests = [];
    public array $restoreRequests = [];

    public function __construct()
    {
        parent::__construct([
            'version' => 'latest',
            'region' => 'us-east-1',
            'service' => 's3',
            'credentials' => [
                'key' => 'fake',
                'secret' => 'fake',
            ],
        ]);
    }

    public function putBucketLifecycleConfiguration(array $configuration): array
    {
        $this->putRequests[] = $configuration;

        return ['Result' => true];
    }

    public function restoreObject(array $configuration): array
    {
        $this->restoreRequests[] = $configuration;

        return ['Result' => true];
    }
}

class LifecycleManagerTest extends TestCase
{
    public function test_it_builds_and_applies_lifecycle_configuration(): void
    {
        $config = new Repository([
            'storage_lifecycle' => [
                'profiles' => [
                    'media' => [
                        'bucket' => 'test-bucket',
                        'prefix' => 'media/',
                        'transitions' => [
                            ['storage_class' => 'STANDARD_IA', 'days' => 30],
                            ['storage_class' => 'GLACIER', 'days' => 120],
                        ],
                        'abort_multipart_days' => 5,
                        'expiration_days' => 400,
                        'noncurrent_versions' => [
                            'transition' => [
                                'storage_class' => 'GLACIER',
                                'days' => 45,
                            ],
                            'expiration_days' => 500,
                        ],
                    ],
                ],
            ],
        ]);

        $fakeClient = new FakeS3Client();

        $capturedClientConfig = null;

        $manager = new LifecycleManager($config, function (array $clientConfig) use (&$capturedClientConfig, $fakeClient) {
            $capturedClientConfig = $clientConfig;

            return $fakeClient;
        });

        $configuration = $manager->apply('media');

        $this->assertSame('media/', $configuration['Rules'][0]['Filter']['Prefix']);
        $this->assertSame('STANDARD_IA', $configuration['Rules'][0]['Transitions'][0]['StorageClass']);
        $this->assertSame(400, $configuration['Rules'][0]['Expiration']['Days']);
        $this->assertSame(45, $configuration['Rules'][0]['NoncurrentVersionTransitions'][0]['NoncurrentDays']);
        $this->assertSame(500, $configuration['Rules'][0]['NoncurrentVersionExpiration']['NoncurrentDays']);

        $this->assertCount(1, $fakeClient->putRequests);
        $this->assertSame('test-bucket', $fakeClient->putRequests[0]['Bucket']);

        $this->assertIsArray($capturedClientConfig);
        $this->assertSame('latest', $capturedClientConfig['version']);

        $manager->requestRestore('media', 'media/uploads/video.mp4', 3);

        $this->assertCount(1, $fakeClient->restoreRequests);
        $this->assertSame('test-bucket', $fakeClient->restoreRequests[0]['Bucket']);
        $this->assertSame('media/uploads/video.mp4', $fakeClient->restoreRequests[0]['Key']);
        $this->assertSame(3, $fakeClient->restoreRequests[0]['RestoreRequest']['Days']);
    }
}
