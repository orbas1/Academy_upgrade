<?php

namespace Tests\Feature\Media;

use App\Jobs\Media\ProcessResponsiveImage;
use App\Jobs\Media\TranscodeMediaToMp4;
use App\Services\Media\MediaManager;
use App\Services\Media\MediaUploadResult;
use App\Services\Media\ResponsivePathGenerator;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    public function test_it_stores_images_and_dispatches_responsive_generation(): void
    {
        Storage::fake('media');

        config([
            'media.default_disk' => 'media',
            'media.image_breakpoints' => [640, 320],
            'media.queue' => 'media',
            'media.responsive_prefix' => 'responsive',
            'media.cdn_url' => 'https://cdn.example.test',
        ]);

        Bus::fake();

        $service = new MediaManager(app(FilesystemManager::class));
        $file = UploadedFile::fake()->image('cover.jpg', 1280, 720);

        $result = $service->storeUploadedFile($file, 'uploads/covers');

        $this->assertInstanceOf(MediaUploadResult::class, $result);
        $this->assertSame('media', $result->disk);
        Storage::disk('media')->assertExists($result->path);

        $this->assertSame(
            'https://cdn.example.test/' . $result->path,
            $result->url
        );

        $this->assertArrayHasKey(320, $result->variants);
        $this->assertSame(
            'https://cdn.example.test/' . ResponsivePathGenerator::variantPath($result->path, 320, 'responsive'),
            $result->variants[320]
        );

        Bus::assertDispatched(ProcessResponsiveImage::class, function (ProcessResponsiveImage $job) use ($result): bool {
            return $job->disk === 'media'
                && $job->path === $result->path
                && $job->breakpoints === [320, 640];
        });
    }

    public function test_it_dispatches_transcode_job_for_videos(): void
    {
        Storage::fake('media');

        config([
            'media.default_disk' => 'media',
            'media.image_breakpoints' => [],
            'media.queue' => 'media',
            'media.transcoded_prefix' => 'transcoded',
            'media.cdn_url' => 'https://cdn.example.test',
        ]);

        Bus::fake();

        $service = new MediaManager(app(FilesystemManager::class));
        $file = UploadedFile::fake()->create('lecture.mov', 1024, 'video/quicktime');

        $result = $service->storeUploadedFile($file, 'uploads/videos');

        $this->assertInstanceOf(MediaUploadResult::class, $result);
        $this->assertSame('media', $result->disk);
        Storage::disk('media')->assertExists($result->path);

        $expectedTarget = TranscodeMediaToMp4::targetPath($result->path, 'transcoded');
        $this->assertArrayHasKey('mp4', $result->derivatives);
        $this->assertSame(
            'https://cdn.example.test/' . $expectedTarget,
            $result->derivatives['mp4']
        );

        Bus::assertDispatched(TranscodeMediaToMp4::class, function (TranscodeMediaToMp4 $job) use ($result): bool {
            return $job->disk === 'media' && $job->path === $result->path;
        });
    }
}
