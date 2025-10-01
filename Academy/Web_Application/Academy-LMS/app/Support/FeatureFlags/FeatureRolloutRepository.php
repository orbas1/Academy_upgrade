<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Date;

class FeatureRolloutRepository
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(string $flag, array $payload): void
    {
        $rollouts = $this->all();
        $payload['updated_at'] = Date::now()->toIso8601String();
        $rollouts[$flag] = array_merge($rollouts[$flag] ?? [], $payload);

        $this->persist($rollouts);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if (! $this->files->exists($this->path)) {
            return [];
        }

        $decoded = json_decode($this->files->get($this->path), true);
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param  array<string, array<string, mixed>>  $rollouts
     */
    private function persist(array $rollouts): void
    {
        $this->ensureDirectory();
        $this->files->put($this->path, json_encode($rollouts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }

    private function ensureDirectory(): void
    {
        $directory = dirname($this->path);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }
}
