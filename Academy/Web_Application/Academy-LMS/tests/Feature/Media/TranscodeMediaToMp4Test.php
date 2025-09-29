<?php

namespace Tests\Feature\Media;

use App\Jobs\Media\TranscodeMediaToMp4;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg as FFMpegFacade;
use Tests\TestCase;

class TranscodeMediaToMp4Test extends TestCase
{
    public function test_it_invokes_ffmpeg_pipeline(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('uploads/raw.mov', 'fake-content');

        $mediaMock = Mockery::mock();
        $openedMock = Mockery::mock();
        $exportMock = Mockery::mock();

        FFMpegFacade::shouldReceive('fromDisk')->once()->with('media')->andReturn($mediaMock);
        $mediaMock->shouldReceive('open')->once()->with('uploads/raw.mov')->andReturn($openedMock);
        $openedMock->shouldReceive('export')->once()->andReturn($exportMock);
        $exportMock->shouldReceive('toDisk')->once()->with('media')->andReturn($exportMock);
        $exportMock->shouldReceive('inFormat')->once()->with(Mockery::type(X264::class))->andReturn($exportMock);
        $exportMock->shouldReceive('save')->once()->with('transcoded/raw.mp4');

        $job = new TranscodeMediaToMp4('media', 'uploads/raw.mov', 'transcoded');

        $job->handle();
    }

    public function test_it_ignores_missing_files(): void
    {
        Storage::fake('media');

        FFMpegFacade::shouldReceive('fromDisk')->never();

        $job = new TranscodeMediaToMp4('media', 'uploads/missing.mov', 'transcoded');

        $job->handle();
    }
}
