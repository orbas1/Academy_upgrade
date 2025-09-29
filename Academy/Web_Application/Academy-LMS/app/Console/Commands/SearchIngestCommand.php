<?php

namespace App\Console\Commands;

use App\Domain\Search\SearchSyncManager;
use Illuminate\Console\Command;
use Throwable;

class SearchIngestCommand extends Command
{
    protected $signature = 'search:ingest {resource? : Limit ingestion to a specific resource (e.g. members)} '
        . '{--sync : Run synchronously without queueing chunk jobs}'
        . '{--chunk= : Override the chunk size for this run}';

    protected $description = 'Ingest relational data into the search cluster by streaming configured resources.';

    public function __construct(private readonly SearchSyncManager $manager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $resource = $this->argument('resource');
        $chunkOverride = $this->option('chunk');

        if ($chunkOverride && ! $resource) {
            $this->error('The --chunk option requires a resource argument.');

            return self::FAILURE;
        }

        if ($chunkOverride) {
            config()->set("search.sync.resources.{$resource}.chunk_size", (int) $chunkOverride);
        }

        try {
            if ($this->option('sync')) {
                $this->runSynchronously($resource);
            } else {
                $this->queueIngestion($resource);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(sprintf('Search ingestion failed: %s', $exception->getMessage()));
            report($exception);

            return self::FAILURE;
        }
    }

    protected function runSynchronously(?string $resource = null): void
    {
        $resources = $resource
            ? [$resource]
            : array_keys(config('search.sync.resources', []));

        foreach ($resources as $name) {
            $this->info(sprintf('Synchronising search resource [%s] synchronously.', $name));
            $this->manager->runResourceSync($name, queueChunks: false);
        }

        $this->info('Search ingestion completed.');
    }

    protected function queueIngestion(?string $resource = null): void
    {
        $resources = $resource
            ? [$resource]
            : array_keys(config('search.sync.resources', []));

        $this->manager->dispatchFullSync($resource);

        $this->table(['Resource', 'Status'], collect($resources)->map(function (string $name) {
            return [$name, 'queued'];
        })->all());

        $this->info('Search ingestion jobs dispatched to the queue.');
    }
}
