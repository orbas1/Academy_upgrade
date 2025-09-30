<?php

namespace App\Support\Migrations;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MigrationRunbookStep implements Arrayable
{
    /**
     * @param  array<int, string>  $ownerRoles
     * @param  array<int, string>  $prechecks
     * @param  array<int, string>  $execution
     * @param  array<int, string>  $verification
     * @param  array<int, string>  $rollback
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $telemetry
     * @param  array<int, string>  $relatedMigrations
     * @param  array<int, string>  $relatedCommands
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $type,
        public readonly array $ownerRoles,
        public readonly array $prechecks,
        public readonly array $execution,
        public readonly array $verification,
        public readonly array $rollback,
        public readonly array $dependencies,
        public readonly array $telemetry,
        public readonly array $relatedMigrations,
        public readonly array $relatedCommands,
        public readonly ?int $maintenanceWindowMinutes = null,
        public readonly ?int $expectedRuntimeMinutes = null,
        public readonly ?string $notes = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $defaults
     */
    public static function fromConfig(string $key, array $config, array $defaults = []): self
    {
        $ownerRoles = Arr::wrap($config['owner_roles'] ?? $defaults['owner_roles'] ?? []);

        return new self(
            key: $key,
            name: $config['name'] ?? Str::headline($key),
            type: (string) ($config['type'] ?? 'migration'),
            ownerRoles: array_values(array_filter(array_map('strval', $ownerRoles))),
            prechecks: self::stringList($config['prechecks'] ?? []),
            execution: self::stringList($config['execution'] ?? []),
            verification: self::stringList($config['verification'] ?? []),
            rollback: self::stringList($config['rollback'] ?? []),
            dependencies: self::stringList($config['dependencies'] ?? []),
            telemetry: self::stringList($config['telemetry'] ?? []),
            relatedMigrations: self::stringList($config['related_migrations'] ?? []),
            relatedCommands: self::stringList($config['related_commands'] ?? []),
            maintenanceWindowMinutes: self::intOrNull($config['maintenance_window_minutes'] ?? $defaults['maintenance_window_minutes'] ?? null),
            expectedRuntimeMinutes: self::intOrNull($config['expected_runtime_minutes'] ?? $defaults['expected_runtime_minutes'] ?? null),
            notes: isset($config['notes']) ? (string) $config['notes'] : null,
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
            'owner_roles' => $this->ownerRoles,
            'prechecks' => $this->prechecks,
            'execution' => $this->execution,
            'verification' => $this->verification,
            'rollback' => $this->rollback,
            'dependencies' => $this->dependencies,
            'telemetry' => $this->telemetry,
            'related_migrations' => $this->relatedMigrations,
            'related_commands' => $this->relatedCommands,
            'maintenance_window_minutes' => $this->maintenanceWindowMinutes,
            'expected_runtime_minutes' => $this->expectedRuntimeMinutes,
            'notes' => $this->notes,
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
