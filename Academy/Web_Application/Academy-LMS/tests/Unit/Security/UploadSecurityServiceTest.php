<?php

namespace Tests\Unit\Security;

use App\Exceptions\Security\UnsafeFileException;
use App\Jobs\Security\PerformUploadScan;
use App\Models\UploadScan;
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

        config()->set('security.uploads.scanner.enabled', false);
        config()->set('security.uploads.block_until_clean', true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('uploads/tests'));
        Schema::dropIfExists('upload_scans');
        parent::tearDown();
    }

    public function test_it_sanitizes_and_stores_image_uploads(): void
    {
        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);

        $path = $service->secureLegacyUpload($file, 'uploads/tests', [
            'resize_width' => 400,
            'optimized_width' => 200,
        ]);

        $this->assertFileExists(public_path($path));
        $scan = UploadScan::first();
        $this->assertNotNull($scan);
        $this->assertEquals(UploadScan::STATUS_SKIPPED, $scan->status);
        $this->assertFileExists(dirname(public_path($path)).'/optimized/'.basename($path));
    }

    public function test_it_enforces_size_limits(): void
    {
        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->create('large.pdf', 2048, 'application/pdf');

        $this->expectException(UnsafeFileException::class);
        $service->secureLegacyUpload($file, 'uploads/tests', ['max_size' => 1024]);
    }

    public function test_it_dispatches_scan_job_when_enabled(): void
    {
        config()->set('security.uploads.scanner.enabled', true);
        config()->set('security.uploads.block_until_clean', false);
        config()->set('security.uploads.scanner.command', '/bin/true');

        Bus::fake();

        $service = app(UploadSecurityService::class);
        $file = UploadedFile::fake()->image('avatar.png', 600, 600);

        $service->secureLegacyUpload($file, 'uploads/tests');

        Bus::assertDispatched(PerformUploadScan::class);
    }
}
