<?php

namespace App\Domain\Search\DataSources;

use App\Domain\Search\Contracts\SearchRecordTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

abstract class AbstractSearchDataSource implements SearchDataSource
{
    public function __construct(
        protected SearchRecordTransformer $transformer,
        protected array $config = []
    ) {
    }

    public function cursor(): LazyCollection
    {
        $table = $this->table();

        if (! Schema::hasTable($table)) {
            return LazyCollection::empty();
        }

        $columns = $this->columns($table);

        return DB::table($table)
            ->select($columns)
            ->orderBy($this->primaryKey(), 'asc')
            ->lazy($this->chunkSize())
            ->map(function ($row) {
                return $this->transformer->fromArray((array) $row);
            })
            ->filter(fn (array $payload) => ! empty($payload));
    }

    public function chunkSize(): int
    {
        return (int) ($this->config['chunk_size'] ?? 500);
    }

    protected function columns(string $table): array
    {
        $columns = Schema::getColumnListing($table);

        return empty($columns) ? ['*'] : $columns;
    }

    protected function primaryKey(): string
    {
        return $this->config['primary_key'] ?? 'id';
    }

    abstract protected function table(): string;
}
