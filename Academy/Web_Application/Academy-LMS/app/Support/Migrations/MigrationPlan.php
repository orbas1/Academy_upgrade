<?php

namespace App\Support\Migrations;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MigrationPlan
{
    /**
     * @param  array<int, MigrationPhase>  $phases
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $serviceOwners
     */
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description,
        public readonly array $phases,
        public readonly array $serviceOwners,
        public readonly array $dependencies,
        public readonly array $featureFlags,
        public readonly array $minimumVersions,
        public readonly ?int $defaultStabilityWindowDays = null,
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('Migration plan key cannot be empty.');
        }

        if ($this->name === '') {
            throw new InvalidArgumentException('Migration plan name cannot be empty.');
        }

        if ($this->description === '') {
            throw new InvalidArgumentException("Migration plan {$this->key} description cannot be empty.");
        }

        if ($this->phases === []) {
            throw new InvalidArgumentException("Migration plan {$this->key} must describe at least one phase.");
        }

        if ($this->serviceOwners === []) {
            throw new InvalidArgumentException("Migration plan {$this->key} requires at least one accountable owner.");
        }
    }

    /**
     * @param  string  $key
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $rootConfig
     */
    public static function fromConfig(string $key, array $config, array $rootConfig = []): self
    {
        $phaseConfigs = Arr::get($config, 'phases', []);
        $phases = Collection::make($phaseConfigs)
            ->map(fn ($phaseConfig) => MigrationPhase::fromConfig(is_array($phaseConfig) ? $phaseConfig : []))
            ->values()
            ->all();

        $defaultWindow = Arr::has($config, 'default_stability_window_days')
            ? (int) Arr::get($config, 'default_stability_window_days')
            : (Arr::has($rootConfig, 'default_stability_window_days')
                ? (int) Arr::get($rootConfig, 'default_stability_window_days')
                : null);

        return new self(
            key: $key,
            name: (string) Arr::get($config, 'name', ''),
            description: (string) Arr::get($config, 'description', ''),
            phases: $phases,
            serviceOwners: array_values(Arr::wrap(Arr::get($config, 'service_owner'))),
            dependencies: array_values(Arr::wrap(Arr::get($config, 'dependencies', []))),
            featureFlags: [
                'backend' => array_values(Arr::get($config, 'feature_flags.backend', [])),
                'mobile' => array_values(Arr::get($config, 'feature_flags.mobile', [])),
                'web' => array_values(Arr::get($config, 'feature_flags.web', [])),
            ],
            minimumVersions: [
                'api' => Arr::get($config, 'minimum_versions.api'),
                'mobile' => Arr::get($config, 'minimum_versions.mobile'),
                'web' => Arr::get($config, 'minimum_versions.web'),
            ],
            defaultStabilityWindowDays: $defaultWindow,
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
            'phases' => Collection::make($this->phases)
                ->map(fn (MigrationPhase $phase) => $phase->toArray())
                ->all(),
            'service_owner' => $this->serviceOwners,
            'dependencies' => $this->dependencies,
            'feature_flags' => $this->featureFlags,
            'minimum_versions' => $this->minimumVersions,
            'default_stability_window_days' => $this->defaultStabilityWindowDays,
        ];
    }
}
