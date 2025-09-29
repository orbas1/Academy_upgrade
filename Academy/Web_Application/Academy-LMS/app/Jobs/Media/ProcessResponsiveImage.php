<?php

namespace App\Jobs\Media;

use App\Services\Media\ResponsivePathGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Facades\Image;
use Throwable;

class ProcessResponsiveImage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $breakpoints;

    private string $visibility;

    private ?string $responsivePrefix;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        array $breakpoints,
        string $visibility = 'public',
        ?string $responsivePrefix = null
    ) {
        $this->breakpoints = $this->sanitizeBreakpoints($breakpoints);
        $this->visibility = $visibility;
        $this->responsivePrefix = $responsivePrefix;
    }

    public function handle(FilesystemManager $filesystem): void
    {
        if ($this->breakpoints === []) {
            return;
        }

        $disk = $filesystem->disk($this->disk);

        if (! $disk->exists($this->path)) {
            return;
        }

        $original = $disk->get($this->path);

        foreach ($this->breakpoints as $width) {
            try {
                $encoded = Image::make($original)
                    ->orientate()
                    ->resize($width, null, static function ($constraint): void {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode(pathinfo($this->path, PATHINFO_EXTENSION) ?: 'jpg', 80)
                    ->__toString();
            } catch (Throwable) {
                continue;
            }

            $variantPath = ResponsivePathGenerator::variantPath(
                $this->path,
                $width,
                $this->responsivePrefix
            );

            $disk->put($variantPath, $encoded, [
                'visibility' => $this->visibility,
            ]);
        }
    }

    private function sanitizeBreakpoints(array $breakpoints): array
    {
        $normalized = array_map(static fn ($value) => (int) $value, $breakpoints);
        $normalized = array_filter($normalized, static fn (int $value): bool => $value > 0);
        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }
}
