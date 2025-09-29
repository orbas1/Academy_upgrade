<?php

namespace App\Domain\Search;

use App\Domain\Search\Contracts\SearchRecordTransformer;
use App\Domain\Search\DataSources\SearchDataSource;
use App\Domain\Search\Jobs\DispatchResourceSync;
use App\Domain\Search\Jobs\PushDocumentsToIndex;
use App\Domain\Search\Jobs\RemoveDocumentsFromIndex;
use App\Services\Search\MeilisearchClient;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

class SearchSyncManager
{
    /**
     * @param array<string, array<string, mixed>> $resources
     */
    public function __construct(
        protected Application $app,
        protected Dispatcher $dispatcher,
        protected MeilisearchClient $client,
        protected LoggerInterface $logger,
        protected array $resources = []
    ) {
        if (empty($this->resources)) {
            $this->resources = config('search.sync.resources', []);
        }
    }

    public function dispatchFullSync(?string $resource = null): void
    {
        foreach ($this->filteredResources($resource) as $name => $config) {
            $this->dispatcher->dispatch(new DispatchResourceSync($name));
        }
    }

    public function runResourceSync(string $resource, bool $queueChunks = true): void
    {
        $config = $this->resourceConfig($resource);
        $transformer = $this->resolveTransformer($config);
        $dataSource = $this->resolveDataSource($config, $transformer);
        $chunkSize = $config['chunk_size'] ?? $dataSource->chunkSize();

        $cursor = $dataSource->cursor();

        $this->iterateCursor($cursor, $chunkSize, function (array $documents) use ($config, $queueChunks) {
            if (empty($documents)) {
                return;
            }

            if ($queueChunks) {
                $this->dispatcher->dispatch(new PushDocumentsToIndex($config['index'], $documents));

                return;
            }

            $this->pushDocuments($config['index'], $documents);
        });
    }

    public function queueModelSync(Model $model): void
    {
        $resource = $this->resourceNameForModel($model::class);

        if (! $resource) {
            return;
        }

        $config = $this->resourceConfig($resource);
        $transformer = $this->resolveTransformer($config);
        $document = method_exists($model, 'toSearchRecord')
            ? $model->toSearchRecord()
            : $transformer->fromModel($model);

        if (empty($document)) {
            return;
        }

        $this->dispatcher->dispatch(new PushDocumentsToIndex($config['index'], [$document]));
    }

    public function queueModelDeletion(Model $model): void
    {
        $resource = $this->resourceNameForModel($model::class);

        if (! $resource) {
            return;
        }

        $config = $this->resourceConfig($resource);
        $identifier = $model->getKey();

        if ($identifier === null) {
            return;
        }

        $this->dispatcher->dispatch(new RemoveDocumentsFromIndex($config['index'], [$identifier]));
    }

    protected function pushDocuments(string $index, array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        try {
            $this->client->upsertDocuments($index, $documents);
        } catch (Throwable $exception) {
            $this->logger->error('Search index upsert failed.', [
                'index' => $index,
                'document_count' => count($documents),
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    protected function deleteDocuments(string $index, array $identifiers): void
    {
        if (empty($identifiers)) {
            return;
        }

        try {
            $this->client->deleteDocuments($index, $identifiers);
        } catch (Throwable $exception) {
            $this->logger->error('Search index delete failed.', [
                'index' => $index,
                'identifiers' => $identifiers,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    protected function iterateCursor(LazyCollection $cursor, int $chunkSize, callable $callback): void
    {
        foreach ($cursor->chunk($chunkSize) as $chunk) {
            $documents = $chunk
                ->filter(fn (array $document) => ! empty($document))
                ->values()
                ->all();

            if (empty($documents)) {
                continue;
            }

            $callback($documents);
        }
    }

    protected function filteredResources(?string $resource): array
    {
        if ($resource === null) {
            return $this->resources;
        }

        if (! array_key_exists($resource, $this->resources)) {
            throw new InvalidArgumentException(sprintf('Unknown search resource [%s]', $resource));
        }

        return [$resource => $this->resources[$resource]];
    }

    protected function resourceConfig(string $resource): array
    {
        if (! array_key_exists($resource, $this->resources)) {
            throw new InvalidArgumentException(sprintf('Search resource [%s] is not configured.', $resource));
        }

        return $this->resources[$resource];
    }

    protected function resolveTransformer(array $config): SearchRecordTransformer
    {
        $class = Arr::get($config, 'transformer');

        if (! $class) {
            throw new InvalidArgumentException('Search transformer was not provided in configuration.');
        }

        return $this->app->make($class);
    }

    protected function resolveDataSource(array $config, SearchRecordTransformer $transformer): SearchDataSource
    {
        $class = Arr::get($config, 'data_source');

        if (! $class) {
            throw new InvalidArgumentException('Search data source was not provided in configuration.');
        }

        return $this->app->make($class, [
            'transformer' => $transformer,
            'config' => $config,
        ]);
    }

    protected function resourceNameForModel(string $modelClass): ?string
    {
        foreach ($this->resources as $name => $config) {
            if (! isset($config['model'])) {
                continue;
            }

            if ($config['model'] === $modelClass) {
                return $name;
            }
        }

        return null;
    }
}
