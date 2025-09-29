<?php

namespace App\Jobs\Media;

use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class TranscodeMediaToMp4 implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private string $targetPrefix;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        ?string $targetPrefix = null
    ) {
        $this->targetPrefix = $targetPrefix ?? config('media.transcoded_prefix', 'transcoded');
    }

    public function handle(): void
    {
        if (! Storage::disk($this->disk)->exists($this->path)) {
            return;
        }

        $targetPath = self::targetPath($this->path, $this->targetPrefix);

        FFMpeg::fromDisk($this->disk)
            ->open($this->path)
            ->export()
            ->toDisk($this->disk)
            ->inFormat(new X264('libmp3lame'))
            ->save($targetPath);
    }

    public static function targetPath(string $path, ?string $targetPrefix = null): string
    {
        $targetPrefix = trim($targetPrefix ?? config('media.transcoded_prefix', 'transcoded'), '/');
        $filename = pathinfo($path, PATHINFO_FILENAME) ?: 'video';

        $directory = $targetPrefix === '' ? '' : $targetPrefix . '/';

        return $directory . $filename . '.mp4';
    }
}
