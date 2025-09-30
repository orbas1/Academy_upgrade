<?php

namespace App\Support\Migrations;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MigrationPhase
{
    /**
     * @param  array<int, MigrationStep>  $steps
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $type,
        public readonly array $steps,
        public readonly ?int $stabilityWindowDays = null,
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('Migration phase key cannot be empty.');
        }

        if ($this->name === '') {
            throw new InvalidArgumentException('Migration phase name cannot be empty.');
        }

        if (! in_array($this->type, ['expand', 'backfill', 'contract'], true)) {
            throw new InvalidArgumentException("Migration phase {$this->key} has invalid type {$this->type}.");
        }

        if ($this->steps === []) {
            throw new InvalidArgumentException("Migration phase {$this->key} must contain at least one step.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $stepConfigs = Arr::get($config, 'steps', []);
        $steps = Collection::make($stepConfigs)
            ->map(fn ($step) => MigrationStep::fromConfig(is_array($step) ? $step : []))
            ->all();

        return new self(
            key: (string) Arr::get($config, 'key', ''),
            name: (string) Arr::get($config, 'name', ''),
            type: (string) Arr::get($config, 'type', ''),
            steps: $steps,
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
            'type' => $this->type,
            'steps' => Collection::make($this->steps)
                ->map(fn (MigrationStep $step) => $step->toArray())
                ->all(),
            'stability_window_days' => $this->stabilityWindowDays,
        ];
    }
}
