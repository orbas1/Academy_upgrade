<?php

namespace App\Support\Migrations;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MigrationRunbook implements Arrayable
{
    /**
     * @param  array<int, string>  $serviceOwner
     * @param  array<int, string>  $approvers
     * @param  array<int, string>  $communicationChannels
     * @param  array<int, MigrationRunbookStep>  $steps
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $planKey,
        public readonly array $serviceOwner,
        public readonly array $approvers,
        public readonly array $communicationChannels,
        public readonly ?int $maintenanceWindowMinutes,
        public readonly array $steps,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $rootConfig
     */
    public static function fromConfig(string $key, array $config, array $rootConfig = []): self
    {
        $steps = Collection::make($config['steps'] ?? [])
            ->map(function ($step, $index) use ($config, $rootConfig) {
                $stepKey = is_string($index) && ! is_numeric($index) ? $index : ($step['key'] ?? Str::slug((string) $index));

                return MigrationRunbookStep::fromConfig(
                    $stepKey,
                    is_array($step) ? $step : [],
                    [
                        'maintenance_window_minutes' => $config['maintenance_window_minutes'] ?? Arr::get($rootConfig, 'default_maintenance_window_minutes'),
                    ],
                );
            })
            ->values()
            ->all();

        return new self(
            key: $key,
            name: $config['name'] ?? Str::headline($key),
            description: $config['description'] ?? null,
            planKey: $config['plan_key'] ?? null,
            serviceOwner: self::stringList($config['service_owner'] ?? []),
            approvers: self::stringList($config['approvers'] ?? []),
            communicationChannels: self::stringList($config['communication_channels'] ?? []),
            maintenanceWindowMinutes: self::intOrNull($config['maintenance_window_minutes'] ?? Arr::get($rootConfig, 'default_maintenance_window_minutes')),
            steps: $steps,
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
            'description' => $this->description,
            'plan_key' => $this->planKey,
            'service_owner' => $this->serviceOwner,
            'approvers' => $this->approvers,
            'communication_channels' => $this->communicationChannels,
            'maintenance_window_minutes' => $this->maintenanceWindowMinutes,
            'steps' => array_map(
                static fn (MigrationRunbookStep $step) => $step->toArray(),
                $this->steps,
            ),
        ];
    }

    /**
     * @param  iterable<int, mixed>  $values
     * @return array<int, string>
     */
    private static function stringList(iterable $values): array
    {
        $list = [];

        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

            $list[] = (string) $value;
        }

        return array_values(array_unique($list));
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
