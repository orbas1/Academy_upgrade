<?php

namespace App\Services\Media;

use App\Jobs\Media\ProcessResponsiveImage;
use App\Jobs\Media\TranscodeMediaToMp4;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaManager
{
    public function __construct(private readonly FilesystemManager $filesystem)
    {
    }

    public function storeUploadedFile(UploadedFile $file, string $directory, ?string $disk = null): MediaUploadResult
    {
        $diskName = $disk ?: config('media.default_disk', config('filesystems.default'));
        $visibility = config('media.visibility', 'public');

        $path = $file->storePublicly($directory, $diskName);

        $mime = $file->getClientMimeType() ?: $file->getMimeType() ?: '';
        $variants = [];
        $derivatives = [];

        if (Str::startsWith($mime, 'image/')) {
            $breakpoints = $this->normalizedBreakpoints();

            if ($breakpoints !== []) {
                $pending = ProcessResponsiveImage::dispatch(
                    $diskName,
                    $path,
                    $breakpoints,
                    $visibility,
                    config('media.responsive_prefix')
                );

                $queue = config('media.queue');

                if (! empty($queue)) {
                    $pending->onQueue($queue);
                }

                foreach ($breakpoints as $width) {
                    $variantPath = ResponsivePathGenerator::variantPath($path, $width, config('media.responsive_prefix'));
                    $variants[$width] = $this->toCdnUrl($variantPath, $diskName);
                }
            }
        }

        if (Str::startsWith($mime, 'video/')) {
            $pending = TranscodeMediaToMp4::dispatch(
                $diskName,
                $path,
                config('media.transcoded_prefix')
            );

            $queue = config('media.queue');

            if (! empty($queue)) {
                $pending->onQueue($queue);
            }

            $targetPath = TranscodeMediaToMp4::targetPath($path, config('media.transcoded_prefix'));
            $derivatives['mp4'] = $this->toCdnUrl($targetPath, $diskName);
        }

        return new MediaUploadResult(
            $diskName,
            $path,
            $this->toCdnUrl($path, $diskName),
            $variants,
            $derivatives
        );
    }

    public function toCdnUrl(string $path, ?string $disk = null): string
    {
        $diskName = $disk ?: config('media.default_disk', config('filesystems.default'));
        $cdnUrl = trim((string) config('media.cdn_url', ''));

        if ($cdnUrl !== '') {
            return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
        }

        return $this->filesystem->disk($diskName)->url($path);
    }

    public function responsiveUrl(string $path, int $width, ?string $disk = null): string
    {
        $variantPath = ResponsivePathGenerator::variantPath($path, $width, config('media.responsive_prefix'));

        return $this->toCdnUrl($variantPath, $disk);
    }

    public function transcodeUrl(string $path, ?string $disk = null): string
    {
        $target = TranscodeMediaToMp4::targetPath($path, config('media.transcoded_prefix'));

        return $this->toCdnUrl($target, $disk);
    }

    private function normalizedBreakpoints(): array
    {
        $breakpoints = config('media.image_breakpoints', []);

        $breakpoints = array_map(static fn ($value) => (int) $value, $breakpoints);
        $breakpoints = array_filter($breakpoints, static fn (int $value): bool => $value > 0);

        $breakpoints = array_values(array_unique($breakpoints));
        sort($breakpoints);

        return $breakpoints;
    }
}
