<?php

declare(strict_types=1);

namespace App\Jobs\Community;

use App\Models\Community\CommunityPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TranscodeCommunityMedia implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    public string $queue = 'media';

    public int $tries = 1;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $postId = Arr::get($this->payload, 'post_id');
        $disk = Arr::get($this->payload, 'disk', config('filesystems.default'));
        $ffmpeg = Arr::get($this->payload, 'ffmpeg', config('services.ffmpeg.binary', 'ffmpeg'));

        $post = $postId ? CommunityPost::query()->find($postId) : null;
        $media = Arr::get($this->payload, 'media', $post?->media ?? []);

        if (empty($media)) {
            return;
        }

        $transcodeResults = [];

        foreach ($media as $item) {
            $path = Arr::get($item, 'path');
            $mime = Arr::get($item, 'mime_type', '');

            if (! $path || stripos($mime, 'video') === false) {
                continue;
            }

            $absoluteSource = Storage::disk($disk)->path($path);

            if (! file_exists($absoluteSource)) {
                $transcodeResults[$path] = ['status' => 'missing'];

                continue;
            }

            if (! $ffmpeg || ! $this->commandExists($ffmpeg)) {
                $transcodeResults[$path] = ['status' => 'skipped'];

                continue;
            }

            $targetRelative = Arr::get($item, 'transcoded_path');

            if (! $targetRelative) {
                $targetRelative = Str::replaceLast('.', '_transcoded.', $path) ?: $path.'_transcoded.mp4';
            }

            $targetAbsolute = Storage::disk($disk)->path($targetRelative);
            $process = new Process([
                $ffmpeg,
                '-y',
                '-i',
                $absoluteSource,
                '-vcodec',
                'libx264',
                '-acodec',
                'aac',
                '-movflags',
                '+faststart',
                $targetAbsolute,
            ]);

            try {
                $process->mustRun();
                Storage::disk($disk)->setVisibility($targetRelative, 'public');
                $transcodeResults[$path] = [
                    'status' => 'completed',
                    'output' => $targetRelative,
                ];
            } catch (ProcessFailedException $exception) {
                Log::error('community.media.transcode.failed', [
                    'path' => $path,
                    'message' => $exception->getMessage(),
                ]);
                $transcodeResults[$path] = [
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ];
            }
        }

        if ($post && ! empty($transcodeResults)) {
            $metadata = $post->metadata ?? [];
            $metadata['transcoding'] = $transcodeResults;
            $post->forceFill(['metadata' => $metadata])->save();
        }
    }

    protected function commandExists(string $binary): bool
    {
        $process = Process::fromShellCommandline(sprintf('command -v %s', escapeshellarg($binary)));
        $process->run();

        return $process->isSuccessful();
    }
}
