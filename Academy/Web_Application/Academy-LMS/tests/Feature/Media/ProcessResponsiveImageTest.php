<?php

namespace Tests\Feature\Media;

use App\Jobs\Media\ProcessResponsiveImage;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class ProcessResponsiveImageTest extends TestCase
{
    public function test_it_generates_responsive_variants(): void
    {
        Storage::fake('media');

        $image = Image::canvas(1600, 900, '#ff0000')->encode('jpg', 90);
        Storage::disk('media')->put('uploads/banner.jpg', (string) $image);

        $job = new ProcessResponsiveImage('media', 'uploads/banner.jpg', [400, 800], 'public', 'responsive');

        $job->handle(app('filesystem'));

        Storage::disk('media')->assertExists('responsive/uploads/banner-400.jpg');
        Storage::disk('media')->assertExists('responsive/uploads/banner-800.jpg');
    }
}
