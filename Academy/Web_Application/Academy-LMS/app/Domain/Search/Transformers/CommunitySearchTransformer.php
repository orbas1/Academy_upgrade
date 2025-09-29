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

        $settings = $this->decodeJson(Arr::get($row, 'settings'));
        $tags = $this->normaliseArray(Arr::get($row, 'tags') ?? Arr::get($settings, 'tags', []));
        $tiers = $this->normaliseArray(Arr::get($row, 'tier_names') ?? Arr::get($row, 'tiers'));

        if (filled(Arr::get($row, 'category_name'))) {
            $tags[] = Arr::get($row, 'category_name');
        }

        $tags = array_values(array_unique(array_filter($tags, fn ($tag) => filled($tag))));

        $geoMetadata = $this->decodeJson(Arr::get($row, 'geo_metadata'));
        $locationCity = Arr::get($geoMetadata, 'city')
            ?? Arr::get($settings, 'location.city')
            ?? Arr::get($row, 'geo_name');
        $locationCountry = Arr::get($geoMetadata, 'country')
            ?? Arr::get($geoMetadata, 'country_code')
            ?? Arr::get($settings, 'location.country')
            ?? Arr::get($row, 'geo_country_code');

        $description = Arr::get($row, 'description')
            ?? Arr::get($row, 'tagline')
            ?? Arr::get($row, 'bio')
            ?? $this->trimHtml(Arr::get($row, 'about_html'));

        return [
            'id' => $identifier,
            'name' => Arr::get($row, 'name') ?? Arr::get($row, 'title'),
            'slug' => Arr::get($row, 'slug'),
            'description' => $description,
            'tags' => $tags,
            'tier_names' => $tiers,
            'visibility' => Arr::get($row, 'visibility') ?? Arr::get($row, 'is_private'),
            'member_count' => (int) (Arr::get($row, 'member_count') ?? Arr::get($row, 'members_count') ?? 0),
            'online_count' => (int) (Arr::get($row, 'online_count') ?? 0),
            'recent_activity_at' => $this->dateValue(Arr::get($row, 'recent_activity_at') ?? Arr::get($row, 'updated_at')),
            'location' => [
                'city' => $locationCity,
                'country' => $locationCountry,
                'timezone' => Arr::get($row, 'geo_timezone'),
            ],
            'is_featured' => $this->boolValue(Arr::get($row, 'is_featured')),
            'category' => [
                'id' => $this->intValue(Arr::get($row, 'category_id')),
                'name' => Arr::get($row, 'category_name'),
            ],
            'launched_at' => $this->dateValue(Arr::get($row, 'launched_at')),
            'updated_at' => $this->dateValue(Arr::get($row, 'updated_at')),
        ];
    }

    public function fromModel(Model $model): array
    {
        if (method_exists($model, 'toSearchRecord')) {
            return $model->toSearchRecord();
        }

        return $this->fromArray($model->toArray());
    }

    protected function normaliseArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(function ($item) {
                if (is_string($item)) {
                    return trim($item);
                }

                if (is_array($item) && array_key_exists('name', $item)) {
                    return (string) $item['name'];
                }

                return $item;
            }, $value)));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normaliseArray($decoded);
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

    protected function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    protected function trimHtml(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Str::limit(strip_tags($value), 240);
    }
}
