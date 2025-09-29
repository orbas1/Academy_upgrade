<?php

namespace App\Domain\Search\DataSources;

use Illuminate\Support\LazyCollection;

interface SearchDataSource
{
    public function cursor(): LazyCollection;

    public function chunkSize(): int;
}
