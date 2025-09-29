<?php

namespace App\Domain\Search\Transformers;

use App\Domain\Search\Contracts\SearchRecordTransformer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class MemberSearchTransformer implements SearchRecordTransformer
{
    public function fromArray(array $row): array
    {
        $identifier = $this->intValue(Arr::get($row, 'id'));

        if (! $identifier) {
            return [];
        }

        $skills = $this->normaliseArray(Arr::get($row, 'skills'));
        $roles = $this->normaliseArray(Arr::get($row, 'role'));

        return [
            'id' => $identifier,
            'name' => Arr::get($row, 'name'),
            'email' => Arr::get($row, 'email'),
            'avatar_url' => Arr::get($row, 'avatar') ?? Arr::get($row, 'profile_photo_url'),
            'headline' => Arr::get($row, 'headline') ?? Arr::get($row, 'title'),
            'about' => Arr::get($row, 'about'),
            'roles' => $roles,
            'skills' => $skills,
            'location' => [
                'city' => Arr::get($row, 'city') ?? Arr::get($row, 'address_city'),
                'country' => Arr::get($row, 'country') ?? Arr::get($row, 'address_country'),
            ],
            'joined_at' => $this->dateValue(Arr::get($row, 'created_at')),
            'last_active_at' => $this->dateValue(Arr::get($row, 'last_login_at') ?? Arr::get($row, 'updated_at')),
            'has_mentor_status' => $this->boolValue(Arr::get($row, 'is_mentor') ?? Arr::get($row, 'mentor_opt_in')),
            'engagement' => [
                'score' => (float) (Arr::get($row, 'engagement_score') ?? 0),
                'contribution_count' => (int) (Arr::get($row, 'contribution_count') ?? 0),
            ],
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
