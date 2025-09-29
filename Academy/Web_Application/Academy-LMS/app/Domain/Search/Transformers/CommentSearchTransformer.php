<?php

namespace App\Domain\Search\Transformers;

use App\Domain\Search\Contracts\SearchRecordTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CommentSearchTransformer implements SearchRecordTransformer
{
    public function fromArray(array $row): array
    {
        $identifier = $this->intValue(Arr::get($row, 'id'));

        if (! $identifier) {
            return [];
        }

        $mentions = $this->normaliseArray(Arr::get($row, 'mentions'));
        $bodyMarkdown = Arr::get($row, 'body_md');
        $bodyHtml = Arr::get($row, 'body_html');
        $body = $bodyMarkdown ?: ($bodyHtml ? strip_tags($bodyHtml) : null);
        $excerpt = Arr::get($row, 'excerpt')
            ?? Str::limit(strip_tags($bodyHtml ?: $bodyMarkdown ?: ''), 160);

        $postMetadata = $this->decodeJson(Arr::get($row, 'post_metadata'));
        $postTitle = Arr::get($postMetadata, 'title');
        if (! filled($postTitle)) {
            $postTitle = Str::limit(strip_tags(Arr::get($row, 'post_body_html') ?? Arr::get($row, 'post_body_md') ?? ''), 80);
        }

        $visibility = Arr::get($row, 'visibility')
            ?? Arr::get($row, 'post_visibility')
            ?? 'community';

        return [
            'id' => $identifier,
            'post_id' => $this->intValue(Arr::get($row, 'post_id')),
            'community_id' => $this->intValue(Arr::get($row, 'community_id') ?? Arr::get($row, 'post_community_id')),
            'community_slug' => Arr::get($row, 'community_slug'),
            'body' => $body,
            'body_html' => $bodyHtml ?: null,
            'excerpt' => $excerpt,
            'mentions' => $mentions,
            'author' => [
                'id' => $this->intValue(Arr::get($row, 'author_id')),
                'name' => Arr::get($row, 'author_name'),
            ],
            'visibility' => $visibility,
            'paywall_tier_id' => $this->intValue(Arr::get($row, 'paywall_tier_id')),
            'created_at' => $this->dateValue(Arr::get($row, 'created_at')),
            'updated_at' => $this->dateValue(Arr::get($row, 'updated_at')),
            'engagement' => [
                'like_count' => (int) (Arr::get($row, 'like_count') ?? 0),
                'reply_count' => (int) (Arr::get($row, 'reply_count') ?? 0),
            ],
            'post' => [
                'id' => $this->intValue(Arr::get($row, 'post_id')),
                'title' => $postTitle,
            ],
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

                if (is_array($item) && array_key_exists('username', $item)) {
                    return (string) $item['username'];
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

    protected function intValue(mixed $value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function dateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $parsed = strtotime((string) $value);

        if ($parsed === false) {
            return null;
        }

        return gmdate(DATE_ATOM, $parsed);
    }
}
