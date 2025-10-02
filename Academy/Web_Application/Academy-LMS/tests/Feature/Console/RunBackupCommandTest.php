<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @group data-protection
 */
class RunBackupCommandTest extends TestCase
{
    public function test_it_creates_encrypted_archives_and_prunes_old_backups(): void
    {
        Storage::fake('s3-backups');

        $sourceDir = storage_path('app/testing-backups');
        app('files')->ensureDirectoryExists($sourceDir);
        file_put_contents($sourceDir . '/example.txt', 'payload');

        $disk = Storage::disk('s3-backups');
        $disk->put('backups/testing/old.tar.gz.enc', 'legacy');
        touch($disk->path('backups/testing/old.tar.gz.enc'), now()->subDays(10)->getTimestamp());

        config([
            'storage_lifecycle.profiles.testing.backup' => [
                'enabled' => true,
                'disk' => 's3-backups',
                'paths' => [$sourceDir],
                'retention_days' => 3,
                'database' => [
                    'enabled' => false,
                    'connection' => 'mysql',
                ],
                'encryption' => [
                    'enabled' => true,
                    'key' => 'unit-test-secret-key',
                    'cipher' => 'aes-256-cbc',
                ],
            ],
        ]);

        $this->artisan('storage:backup testing')->assertExitCode(0);

        $files = $disk->allFiles('backups/testing');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.tar.gz.enc', $files[0]);
        $this->assertGreaterThan(0, $disk->size($files[0]));
        $this->assertFalse($disk->exists('backups/testing/old.tar.gz.enc'));

        app('files')->deleteDirectory($sourceDir);
    }
}
