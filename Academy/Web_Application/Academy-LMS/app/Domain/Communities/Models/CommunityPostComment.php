<?php

namespace App\Domain\Communities\Models;

use App\Domain\Search\Concerns\Searchable;
use App\Models\User;
use Database\Factories\CommunityPostCommentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CommunityPostComment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected $guarded = [];

    protected $casts = [
        'mentions' => 'array',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return CommunityPostCommentFactory::new();
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function toSearchRecord(): array
    {
        $this->loadMissing([
            'author:id,name',
            'community:id,slug',
            'post.community:id,slug,visibility',
            'post.author:id,name',
        ]);

        $bodyMarkdown = $this->body_md ?? '';
        $bodyHtml = $this->body_html ?? '';
        $body = $bodyMarkdown !== '' ? $bodyMarkdown : strip_tags($bodyHtml);
        $excerpt = Str::limit(strip_tags($bodyHtml !== '' ? $bodyHtml : $bodyMarkdown), 160);

        $mentions = $this->normaliseArray($this->mentions);

        $post = $this->post;
        $postMetadata = $this->normaliseJson($post?->metadata ?? []);
        $postTitle = Arr::get($postMetadata, 'title');

        if (! filled($postTitle)) {
            $postTitle = Str::limit(strip_tags($post?->body_html ?? $post?->body_md ?? ''), 80);
        }

        $publishedAt = $this->created_at ?? now();

        return [
            'id' => (int) $this->getKey(),
            'post_id' => (int) $this->post_id,
            'community_id' => (int) $this->community_id,
            'community_slug' => $this->community?->slug ?? $post?->community?->slug,
            'body' => $body,
            'body_html' => $bodyHtml ?: null,
            'excerpt' => $excerpt,
            'mentions' => $mentions,
            'author' => [
                'id' => (int) $this->author_id,
                'name' => $this->author?->name,
            ],
            'visibility' => $post?->visibility ?? 'community',
            'paywall_tier_id' => $post?->paywall_tier_id ? (int) $post->paywall_tier_id : null,
            'created_at' => $publishedAt?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'engagement' => [
                'like_count' => (int) $this->like_count,
                'reply_count' => (int) $this->reply_count,
            ],
            'post' => [
                'id' => $post?->getKey(),
                'title' => $postTitle,
            ],
        ];
    }

    protected function normaliseArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($item) => is_string($item) ? trim($item) : $item, $value)));
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

    protected function normaliseJson(mixed $value): array
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
}
