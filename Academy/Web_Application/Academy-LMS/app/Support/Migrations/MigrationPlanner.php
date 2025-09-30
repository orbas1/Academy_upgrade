<?php

namespace App\Support\Migrations;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MigrationPlanner implements Arrayable
{
    /**
     * @var array<string, MigrationPlan>
     */
    private array $plans = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = [])
    {
        $planConfig = Arr::get($config, 'plans', []);
        $this->plans = Collection::make($planConfig)
            ->map(fn ($definition, $key) => MigrationPlan::fromConfig((string) $key, is_array($definition) ? $definition : [], $config))
            ->keyBy(fn (MigrationPlan $plan) => $plan->key)
            ->all();
    }

    /**
     * @return Collection<int, MigrationPlan>
     */
    public function plans(): Collection
    {
        return Collection::make($this->plans)
            ->sortBy(fn (MigrationPlan $plan) => $plan->name)
            ->values();
    }

    public function has(string $planKey): bool
    {
        return isset($this->plans[$planKey]);
    }

    public function get(string $planKey): MigrationPlan
    {
        if (! isset($this->plans[$planKey])) {
            throw new InvalidArgumentException("Unknown migration plan [{$planKey}].");
        }

        return $this->plans[$planKey];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default_stability_window_days' => Arr::get($this->config, 'default_stability_window_days'),
            'plans' => $this->plans()
                ->map(fn (MigrationPlan $plan) => $plan->toArray())
                ->all(),
        ];
    }
}
