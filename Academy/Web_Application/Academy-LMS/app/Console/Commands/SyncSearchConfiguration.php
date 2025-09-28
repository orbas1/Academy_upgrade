<?php

namespace App\Console\Commands;

use App\Services\Search\SearchClusterConfigurator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSearchConfiguration extends Command
{
    protected $signature = 'search:sync {--index=} {--quiet-errors : Suppress exception stack traces and log them instead}';

    protected $description = 'Synchronise Meilisearch indexes, synonyms, and ranking rules.';

    public function __construct(private readonly SearchClusterConfigurator $configurator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $index = $this->option('index');
            $results = $this->configurator->synchronize($index);

            if ($results->isEmpty()) {
                $this->warn('No indexes were configured. Please review search configuration.');

                return self::SUCCESS;
            }

            $this->table(
                ['Index', 'Primary Key', 'Settings Applied'],
                $results->map(function (array $row) {
                    return [
                        $row['index'],
                        $row['primary_key'] ?? 'auto',
                        collect($row['settings_applied'] ?? [])->map(fn ($value, $key) => $key.': '.$value)->implode(PHP_EOL),
                    ];
                })->toArray()
            );

            $this->info('Search configuration synchronised successfully.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $message = sprintf('Failed to synchronise search configuration: %s', $exception->getMessage());
            $this->error($message);

            if ($this->option('quiet-errors')) {
                Log::error($message, ['exception' => $exception]);
            } else {
                report($exception);
            }

            return self::FAILURE;
        }
    }
}
