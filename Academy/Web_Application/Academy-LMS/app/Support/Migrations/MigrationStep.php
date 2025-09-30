<?php

namespace App\Support\Migrations;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class MigrationStep
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $summary,
        /** @var array<int, string> */
        public readonly array $operations,
        /** @var array<int, string> */
        public readonly array $backfill,
        /** @var array<int, string> */
        public readonly array $verification,
        /** @var array<int, string> */
        public readonly array $rollback,
        /** @var array<int, string> */
        public readonly array $owners,
        /** @var array<int, string> */
        public readonly array $dependencies,
        public readonly ?int $stabilityWindowDays = null,
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('Migration step key cannot be empty.');
        }

        if ($this->name === '') {
            throw new InvalidArgumentException('Migration step name cannot be empty.');
        }

        if ($this->summary === '') {
            throw new InvalidArgumentException("Migration step {$this->key} summary cannot be empty.");
        }

        if ($this->operations === []) {
            throw new InvalidArgumentException("Migration step {$this->key} must describe at least one operation.");
        }

        if ($this->verification === []) {
            throw new InvalidArgumentException("Migration step {$this->key} requires verification criteria.");
        }

        if ($this->rollback === []) {
            throw new InvalidArgumentException("Migration step {$this->key} requires rollback instructions.");
        }

        if ($this->owners === []) {
            throw new InvalidArgumentException("Migration step {$this->key} must list accountable owners.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $operations = Arr::where(array_values(Arr::get($config, 'operations', [])), fn ($value) => filled($value));
        $backfill = Arr::where(array_values(Arr::get($config, 'backfill', [])), fn ($value) => filled($value));
        $verification = Arr::where(array_values(Arr::get($config, 'verification', [])), fn ($value) => filled($value));
        $rollback = Arr::where(array_values(Arr::get($config, 'rollback', [])), fn ($value) => filled($value));
        $owners = Arr::where(array_values(Arr::get($config, 'owners', [])), fn ($value) => filled($value));
        $dependencies = Arr::where(array_values(Arr::get($config, 'dependencies', [])), fn ($value) => filled($value));

        return new self(
            key: (string) Arr::get($config, 'key', ''),
            name: (string) Arr::get($config, 'name', ''),
            summary: (string) Arr::get($config, 'summary', ''),
            operations: array_values($operations),
            backfill: array_values($backfill),
            verification: array_values($verification),
            rollback: array_values($rollback),
            owners: array_values($owners),
            dependencies: array_values($dependencies),
            stabilityWindowDays: Arr::has($config, 'stability_window_days')
                ? (int) Arr::get($config, 'stability_window_days')
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'summary' => $this->summary,
            'operations' => $this->operations,
            'backfill' => $this->backfill,
            'verification' => $this->verification,
            'rollback' => $this->rollback,
            'owners' => $this->owners,
            'dependencies' => $this->dependencies,
            'stability_window_days' => $this->stabilityWindowDays,
        ];
    }
}
