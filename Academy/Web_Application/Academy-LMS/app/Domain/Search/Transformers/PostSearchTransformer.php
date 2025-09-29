<?php

namespace App\Domain\Search\Transformers;

use App\Domain\Search\Contracts\SearchRecordTransformer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class PostSearchTransformer implements SearchRecordTransformer
{
    public function fromArray(array $row): array
    {
        $identifier = $this->intValue(Arr::get($row, 'id'));

        if (! $identifier) {
            return [];
        }

        $topics = $this->normaliseArray(Arr::get($row, 'topics'));

        return [
            'id' => $identifier,
            'community_id' => $this->intValue(Arr::get($row, 'community_id')),
            'title' => Arr::get($row, 'title') ?? Arr::get($row, 'headline'),
            'body' => Arr::get($row, 'body') ?? Arr::get($row, 'content'),
            'excerpt' => Arr::get($row, 'excerpt'),
            'author' => [
                'id' => $this->intValue(Arr::get($row, 'author_id') ?? Arr::get($row, 'user_id')),
                'name' => Arr::get($row, 'author_name'),
            ],
            'topics' => $topics,
            'visibility' => Arr::get($row, 'visibility') ?? Arr::get($row, 'is_private'),
            'is_paid' => $this->boolValue(Arr::get($row, 'is_paid')), 
            'created_at' => $this->dateValue(Arr::get($row, 'created_at')),
            'engagement' => [
                'score' => (float) (Arr::get($row, 'engagement_score') ?? 0),
                'comment_count' => (int) (Arr::get($row, 'comment_count') ?? 0),
                'reaction_count' => (int) (Arr::get($row, 'reaction_count') ?? 0),
            ],
            'media' => $this->normaliseArray(Arr::get($row, 'media_types') ?? Arr::get($row, 'attachments')),
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
