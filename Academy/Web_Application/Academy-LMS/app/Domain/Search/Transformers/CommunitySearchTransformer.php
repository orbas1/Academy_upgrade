<?php

namespace App\Domain\Search\Transformers;

use App\Domain\Search\Contracts\SearchRecordTransformer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class CommunitySearchTransformer implements SearchRecordTransformer
{
    public function fromArray(array $row): array
    {
        $identifier = $this->intValue(Arr::get($row, 'id'));

        if (! $identifier) {
            return [];
        }

        $tags = $this->normaliseArray(Arr::get($row, 'tags'));
        $tiers = $this->normaliseArray(Arr::get($row, 'tier_names') ?? Arr::get($row, 'tiers'));

        return [
            'id' => $identifier,
            'name' => Arr::get($row, 'name') ?? Arr::get($row, 'title'),
            'slug' => Arr::get($row, 'slug'),
            'description' => Arr::get($row, 'description') ?? Arr::get($row, 'summary'),
            'tags' => $tags,
            'tier_names' => $tiers,
            'visibility' => Arr::get($row, 'visibility') ?? Arr::get($row, 'is_private'),
            'member_count' => (int) (Arr::get($row, 'member_count') ?? Arr::get($row, 'members_count') ?? 0),
            'online_count' => (int) (Arr::get($row, 'online_count') ?? 0),
            'recent_activity_at' => $this->dateValue(Arr::get($row, 'recent_activity_at') ?? Arr::get($row, 'updated_at')),
            'location' => [
                'city' => Arr::get($row, 'city') ?? Arr::get($row, 'location_city'),
                'country' => Arr::get($row, 'country') ?? Arr::get($row, 'location_country'),
            ],
            'is_featured' => $this->boolValue(Arr::get($row, 'is_featured')),
        ];
    }

    public function fromModel(Model $model): array
    {
        return $this->fromArray($model->toArray());
    }

    protected function normaliseArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => filled($item)));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded, fn ($item) => filled($item)));
            }

            return array_values(array_filter(array_map('trim', preg_split('/[,;|]/', $value) ?: [])));
        }

        return [];
    }

    protected function intValue(mixed $value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(Str::lower($value), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    protected function dateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }
}
