<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FeatureToggleCommand extends Command
{
    protected $signature = 'feature:toggle {name : Feature flag name} {--on : Enable the feature} {--off : Disable the feature}';

    protected $description = 'Toggle a feature flag stored in storage/app/feature-flags.json';

    public function handle(): int
    {
        $name = $this->argument('name');
        $enable = $this->option('on');
        $disable = $this->option('off');

        if ($enable === $disable) {
            $this->error('Specify either --on or --off to toggle a feature.');
            return self::FAILURE;
        }

        $flags = $this->loadFlags();
        $flags[$name] = $enable;

        $this->storeFlags($flags);

        $state = $enable ? 'enabled' : 'disabled';
        $this->info(sprintf('Feature "%s" %s. Run php artisan config:clear to apply immediately.', $name, $state));

        return self::SUCCESS;
    }

    /**
     * @return array<string, bool>
     */
    protected function loadFlags(): array
    {
        $path = storage_path('app/feature-flags.json');
        if (! file_exists($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_map(static fn ($value) => (bool) $value, $decoded);
    }

    /**
     * @param array<string, bool> $flags
     */
    protected function storeFlags(array $flags): void
    {
        $path = storage_path('app/feature-flags.json');
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($flags, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
