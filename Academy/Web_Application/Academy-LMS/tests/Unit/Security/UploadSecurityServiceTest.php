<?php

namespace Tests\Unit\Security;

use App\Exceptions\Security\QuotaExceededException;
use App\Exceptions\Security\UnsafeFileException;
use App\Jobs\Security\PerformUploadScan;
use App\Models\UploadScan;
use App\Models\UploadUsage;
use App\Services\Security\UploadSecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class UploadSecurityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', database_path('database.sqlite'));

        Schema::dropIfExists('upload_scans');
        Schema::dropIfExists('upload_usages');
        Schema::create('upload_scans', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('absolute_path');
            $table->string('mime_type')->nullable();
            $table->string('status');
            $table->text('details')->nullable();
            $table->string('quarantine_path')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('upload_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('community_id')->nullable();
            $table->string('disk')->nullable();
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->string('visibility')->default('public');
            $table->timestamps();
        });

        config()->set('security.uploads.scanner.enabled', false);
        config()->set('security.uploads.block_until_clean', true);
        config()->set('security.uploads.quota', [
            'user' => ['limit_mb' => 50, 'window_days' => 30],
            'community' => ['limit_mb' => 500, 'window_days' => 30],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('uploads/tests'));
        Schema::dropIfExists('upload_scans');
        Schema::dropIfExists('upload_usages');
        parent::tearDown();
    }

    public function test_it_sanitizes_and_stores_image_uploads(): void
    {
        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);

        $path = $service->secureLegacyUpload($file, 'uploads/tests', [
            'resize_width' => 400,
            'optimized_width' => 200,
            'user_id' => 7,
        ]);

        $this->assertFileExists(public_path($path));
        $scan = UploadScan::first();
        $this->assertNotNull($scan);
        $this->assertEquals(UploadScan::STATUS_SKIPPED, $scan->status);
        $this->assertFileExists(dirname(public_path($path)).'/optimized/'.basename($path));

        $usage = UploadUsage::first();
        $this->assertNotNull($usage);
        $this->assertSame(7, $usage->user_id);
        $this->assertSame($path, $usage->path);
        $this->assertGreaterThan(0, $usage->size);
    }

    public function test_it_enforces_size_limits(): void
    {
        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->create('large.pdf', 2048, 'application/pdf');

        $this->expectException(UnsafeFileException::class);
        $service->secureLegacyUpload($file, 'uploads/tests', ['max_size' => 1024, 'user_id' => 1]);
    }

    public function test_it_dispatches_scan_job_when_enabled(): void
    {
        config()->set('security.uploads.scanner.enabled', true);
        config()->set('security.uploads.block_until_clean', false);
        config()->set('security.uploads.scanner.command', '/bin/true');

        Bus::fake();

        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->image('avatar.png', 600, 600);

        $service->secureLegacyUpload($file, 'uploads/tests', ['user_id' => 5]);

        Bus::assertDispatched(PerformUploadScan::class);
    }

    public function test_it_enforces_user_quota_limits(): void
    {
        config()->set('security.uploads.quota.user.limit_mb', 5);
        config()->set('security.uploads.quota.user.window_days', 30);

        UploadUsage::create([
            'user_id' => 9,
            'path' => 'uploads/tests/existing.jpg',
            'size' => 4 * 1024 * 1024,
        ]);

        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->create('document.pdf', 2048, 'application/pdf');

        $this->expectException(QuotaExceededException::class);
        $service->secureLegacyUpload($file, 'uploads/tests', ['user_id' => 9]);
    }
}
