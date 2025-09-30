<?php

namespace App\Console\Commands;

use App\Support\Secrets\SecretManager;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SyncSecretsCommand extends Command
{
    protected $signature = 'secrets:sync {--driver=} {--key=* : Specific keys to synchronize}';

    protected $description = 'Prime the local cache with secrets from the configured providers.';

    public function __construct(private readonly SecretManager $secrets)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $driver = $this->option('driver');
        $keys = $this->option('key');
        $configuredKeys = config('secrets.sync.keys', []);

        if ($keys === [] && $configuredKeys === []) {
            $this->components->warn('No keys configured for synchronization. Provide --key option or SECRET_SYNC_KEYS.');

            return self::INVALID;
        }

        $targets = $keys !== [] ? $keys : $configuredKeys;
        $this->components->info(sprintf('Synchronizing %d secret(s) via %s driver.', count($targets), $driver ?: 'default'));

        foreach ($targets as $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            try {
                $secret = $this->secrets->get($key, $driver ?: null);
            } catch (\Throwable $throwable) {
                $this->components->error(sprintf('Failed to synchronize %s: %s', $key, $throwable->getMessage()));

                report($throwable);

                return self::FAILURE;
            }

            $this->components->info(sprintf('Cached [%s] version %s (mask %s)', $secret->key, $secret->version, $secret->maskedValue()));
        }

        return self::SUCCESS;
    }
}
