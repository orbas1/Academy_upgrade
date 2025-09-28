<?php

namespace App\Console\Commands;

use App\Support\Caching\Warmers\CacheWarmer;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class WarmApplicationCache extends Command
{
    protected $signature = 'app:cache-warm {--force : Run even if disabled in configuration}';

    protected $description = 'Warm framework and application caches for optimized performance.';

    public function __construct(private readonly Container $container)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('force') && ! config('performance.warmup.enabled', false)) {
            $this->components->warn('Cache warmup is disabled via configuration. Use --force to override.');

            return self::SUCCESS;
        }

        $this->warmFrameworkCaches();
        $this->warmApplicationCaches();

        $this->components->info('Cache warmup completed successfully.');

        return self::SUCCESS;
    }

    protected function warmFrameworkCaches(): void
    {
        $this->components->task('Caching configuration', fn () => $this->callSilent('config:cache') === 0);
        $this->components->task('Caching routes', fn () => $this->callSilent('route:cache') === 0);
        $this->components->task('Caching views', fn () => $this->callSilent('view:cache') === 0);
        $this->components->task('Caching events', fn () => $this->callSilent('event:cache') === 0);
    }

    protected function warmApplicationCaches(): void
    {
        $warmers = Collection::make(config('performance.warmup.warmers', []))
            ->filter()
            ->map(fn (string $warmer) => $this->container->make($warmer))
            ->filter(fn ($warmer) => $warmer instanceof CacheWarmer);

        $warmers->each(function (CacheWarmer $warmer): void {
            $warmer->warm();
        });
    }
}
