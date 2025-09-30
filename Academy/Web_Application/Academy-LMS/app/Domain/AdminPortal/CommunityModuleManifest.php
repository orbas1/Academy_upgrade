<?php

namespace App\Domain\AdminPortal;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CommunityModuleManifest
{
    public function __construct(private readonly array $config)
    {
    }

    public static function make(?array $config = null): self
    {
        return new self($config ?? config('community'));
    }

    public function build(array $featureFlagOverrides, array $grantedPermissions): array
    {
        $featureFlags = array_replace(
            Arr::get($this->config, 'spa.feature_flags', []),
            $featureFlagOverrides,
        );

        $modules = array_values(array_filter(array_map(
            function (array $module) use ($featureFlags, $grantedPermissions) {
                $requiredPermissions = $module['permissions'] ?? [];
                $featureFlag = $module['feature_flag'] ?? null;

                $isFeatureEnabled = $featureFlag ? ($featureFlags[$featureFlag] ?? false) : true;
                $hasPermission = empty($requiredPermissions)
                    || empty(array_diff($requiredPermissions, $grantedPermissions));

                if (! $isFeatureEnabled || ! $hasPermission) {
                    return null;
                }

                return [
                    'key' => $module['key'],
                    'name' => $module['name'],
                    'description' => $module['description'] ?? null,
                    'feature_flag' => $featureFlag,
                    'permissions' => $requiredPermissions,
                    'navigation' => array_map(
                        static fn (array $navigation) => [
                            'label' => $navigation['label'],
                            'route' => $navigation['route'],
                            'icon' => $navigation['icon'] ?? null,
                        ],
                        $module['navigation'] ?? [],
                    ),
                    'routes' => array_map(
                        static fn (array $route) => [
                            'name' => $route['name'],
                            'path' => $route['path'],
                            'title' => $route['title'] ?? $route['name'],
                        ],
                        $module['routes'] ?? [],
                    ),
                    'endpoints' => $module['endpoints'] ?? [],
                    'capabilities' => $module['capabilities'] ?? [],
                ];
            },
            Arr::get($this->config, 'spa.modules', []),
        )));

        return [
            'version' => Arr::get($this->config, 'spa.version'),
            'generated_at' => Carbon::now()->toIso8601String(),
            'base_path' => Arr::get($this->config, 'spa.base_path'),
            'feature_flags' => $featureFlags,
            'modules' => $modules,
            'api' => [
                'base_url' => Arr::get($this->config, 'api.base_url'),
                'manifest_endpoint' => Arr::get($this->config, 'api.manifest_endpoint'),
            ],
        ];
    }
}
