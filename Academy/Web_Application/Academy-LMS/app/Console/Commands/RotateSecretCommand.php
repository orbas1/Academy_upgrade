<?php

namespace App\Console\Commands;

use App\Support\Secrets\SecretManager;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class RotateSecretCommand extends Command
{
    protected $signature = 'secrets:rotate {key? : The key to rotate} {--driver=} {--value=} {--force}';

    protected $description = 'Rotate managed secrets and invalidate the local cache.';

    public function __construct(private readonly SecretManager $secrets)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $driver = $this->option('driver') ?: config('secrets.rotation.driver');
        $configuredKeys = config('secrets.rotation.keys', []);

        $key = $this->argument('key');
        $keys = $key ? [$key] : $configuredKeys;

        if ($keys === []) {
            $this->components->warn('No rotation keys defined. Provide a key argument or SECRET_ROTATION_KEYS.');

            return self::INVALID;
        }

        foreach ($keys as $target) {
            try {
                $result = $this->secrets->rotate($target, $driver, [
                    'value' => $this->option('value'),
                ]);

                $this->components->info(sprintf('Rotated [%s] version %s at %s', $target, $result->version, $result->rotatedAt->toIso8601String()));
            } catch (Throwable $throwable) {
                if (! $this->option('force')) {
                    $this->components->error(sprintf('Rotation failed for %s: %s', $target, $throwable->getMessage()));

                    report($throwable);

                    return self::FAILURE;
                }

                report($throwable);
                $this->components->warn(sprintf('Rotation failed for %s but continuing (--force).', $target));
            }
        }

        return self::SUCCESS;
    }
}
