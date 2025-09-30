<?php

namespace App\Support\Migrations;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MigrationRunbookRegistry implements Arrayable
{
    /** @var array<string, MigrationRunbook> */
    private array $runbooks = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = [])
    {
        $this->runbooks = Collection::make(Arr::get($config, 'runbooks', []))
            ->map(fn ($definition, $key) => MigrationRunbook::fromConfig((string) $key, is_array($definition) ? $definition : [], $config))
            ->keyBy(fn (MigrationRunbook $runbook) => $runbook->key)
            ->all();
    }

    /**
     * @return Collection<int, MigrationRunbook>
     */
    public function runbooks(): Collection
    {
        return Collection::make($this->runbooks)
            ->sortBy(fn (MigrationRunbook $runbook) => $runbook->name)
            ->values();
    }

    public function has(string $runbookKey): bool
    {
        return isset($this->runbooks[$runbookKey]);
    }

    public function get(string $runbookKey): MigrationRunbook
    {
        if (! isset($this->runbooks[$runbookKey])) {
            throw new InvalidArgumentException("Unknown migration runbook [{$runbookKey}].");
        }

        return $this->runbooks[$runbookKey];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default_maintenance_window_minutes' => Arr::get($this->config, 'default_maintenance_window_minutes'),
            'runbooks' => $this->runbooks()
                ->map(fn (MigrationRunbook $runbook) => $runbook->toArray())
                ->all(),
        ];
    }
}
