<?php

namespace App\Domain\Search\DataSources;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;

class CommunityDataSource extends AbstractSearchDataSource
{
    protected function table(): string
    {
        return 'communities';
    }

    public function cursor(): LazyCollection
    {
        if (! Schema::hasTable($this->table())) {
            return LazyCollection::empty();
        }

        $query = DB::table($this->table());

        if (Schema::hasColumn($this->table(), 'id')) {
            $query->orderBy('id');
        }

        return $query
            ->lazy($this->chunkSize())
            ->map(function ($row) {
                return $this->transformer->fromArray((array) $row);
            })
            ->filter(fn (array $payload) => ! empty($payload));
    }
}
