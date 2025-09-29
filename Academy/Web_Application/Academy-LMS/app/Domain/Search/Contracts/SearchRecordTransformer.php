<?php

namespace App\Domain\Search\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SearchRecordTransformer
{
    /**
     * Transform a raw database row into a search document payload.
     */
    public function fromArray(array $row): array;

    /**
     * Transform an eloquent model instance into a search document payload.
     */
    public function fromModel(Model $model): array;
}
