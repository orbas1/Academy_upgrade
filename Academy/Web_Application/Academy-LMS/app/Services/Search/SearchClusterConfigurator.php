<?php

namespace App\Services\Search;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchClusterConfigurator
{
    public function __construct(
        private readonly MeilisearchClient $client,
        private readonly array $indexes
    ) {
    }

    public function synchronize(?string $onlyIndex = null): Collection
    {
        $results = collect();

        $indexes = $this->indexes;
        if ($onlyIndex) {
            $indexes = Arr::only($indexes, [$onlyIndex]);
        }

        foreach ($indexes as $name => $configuration) {
            $results->push($this->configureIndex($name, $configuration));
        }

        return $results;
    }

    protected function configureIndex(string $name, array $configuration): array
    {
        $primaryKey = Arr::get($configuration, 'primaryKey');
        $settings = Arr::except($configuration, ['primaryKey']);

        $this->client->ensureIndex($name, $primaryKey);
        $this->client->updateSettings($name, $settings);

        return [
            'index' => $name,
            'primary_key' => $primaryKey,
            'settings_applied' => $this->describeSettings($settings),
        ];
    }

    protected function describeSettings(array $settings): array
    {
        $summary = [];

        foreach ($settings as $key => $value) {
            $summary[$key] = is_array($value)
                ? sprintf('%s (%d)', Str::title(str_replace('_', ' ', $key)), count($value))
                : Str::title(str_replace('_', ' ', $key));
        }

        return $summary;
    }
}
