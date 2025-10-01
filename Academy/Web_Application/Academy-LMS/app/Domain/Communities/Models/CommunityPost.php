<?php

namespace App\Domain\Communities\Models;

use App\Domain\Search\Concerns\Searchable;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\CommunityPostFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CommunityPost extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected $guarded = [];

    protected $casts = [
        'media' => 'array',
        'mentions' => 'array',
        'topics' => 'array',
        'metadata' => 'array',
        'lifecycle' => 'array',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_archived' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected static function newFactory(): Factory
    {
        return CommunityPostFactory::new();
    }

    public function markArchived(string $reason, CarbonImmutable $archivedAt, array $context = []): void
    {
        $lifecycle = $this->lifecycle ?? [];
        $history = $lifecycle['history'] ?? [];

        $history[] = [
            'event' => 'archived',
            'reason' => $reason,
            'context' => $context,
            'occurred_at' => $archivedAt->toIso8601String(),
        ];

        $lifecycle['history'] = $history;
        $lifecycle['archived'] = [
            'reason' => $reason,
            'context' => $context,
            'archived_at' => $archivedAt->toIso8601String(),
        ];

        $this->forceFill([
            'is_archived' => true,
            'archived_at' => $archivedAt,
            'lifecycle' => $lifecycle,
        ])->save();
    }

    public function markActive(CarbonImmutable $reactivatedAt, string $reason = 'activity'): void
    {
        $lifecycle = $this->lifecycle ?? [];
        $history = $lifecycle['history'] ?? [];

        $history[] = [
            'event' => 'reactivated',
            'reason' => $reason,
            'occurred_at' => $reactivatedAt->toIso8601String(),
        ];

        unset($lifecycle['archived']);
        $lifecycle['history'] = $history;

        $this->forceFill([
            'is_archived' => false,
            'archived_at' => null,
            'lifecycle' => $lifecycle,
        ])->save();
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function paywallTier(): BelongsTo
    {
        return $this->belongsTo(CommunitySubscriptionTier::class, 'paywall_tier_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityPostComment::class, 'post_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommunityPostLike::class, 'post_id');
    }

    public function toSearchRecord(): array
    {
        $this->loadMissing([
            'author:id,name',
            'community:id,slug,visibility',
            'paywallTier:id,name',
        ]);

        $topics = $this->normaliseArray($this->topics);
        $mediaTypes = $this->normaliseMediaTypes($this->media);
        $mentions = $this->normaliseArray($this->mentions);

        $bodyMarkdown = $this->body_md ?? '';
        $bodyHtml = $this->body_html ?? '';
        $body = $bodyMarkdown !== '' ? $bodyMarkdown : strip_tags($bodyHtml);
        $excerpt = Str::limit(strip_tags($bodyHtml !== '' ? $bodyHtml : $bodyMarkdown), 240);

        $metadata = $this->normaliseJson($this->metadata);
        $title = Arr::get($metadata, 'title');
        if (! filled($title)) {
            $title = Str::limit(Str::title(Str::of($body)->limit(80)), 80);
        }

        $engagementScore = $this->calculateEngagementScore();

        $publishedAt = $this->published_at ?? $this->created_at;

        return [
            'id' => (int) $this->getKey(),
            'community_id' => (int) $this->community_id,
            'community_slug' => $this->community?->slug,
            'title' => $title,
            'body' => $body,
            'excerpt' => $excerpt,
            'author' => [
                'id' => (int) $this->author_id,
                'name' => $this->author?->name,
            ],
            'topics' => $topics,
            'mentions' => $mentions,
            'visibility' => $this->visibility,
            'is_paid' => $this->isPaid(),
            'paywall_tier_id' => $this->paywall_tier_id ? (int) $this->paywall_tier_id : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'published_at' => $publishedAt?->toIso8601String(),
            'media' => $mediaTypes,
            'engagement' => [
                'score' => $engagementScore,
                'comment_count' => (int) $this->comment_count,
                'reaction_count' => (int) $this->like_count,
            ],
        ];
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

    protected function normaliseMediaTypes(mixed $media): array
    {
        $decoded = $media;

        if (is_string($media) && $media !== '') {
            $decoded = json_decode($media, true);
        }

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(function ($item) {
                if (is_array($item) && isset($item['type'])) {
                    return (string) $item['type'];
                }

                if (is_string($item)) {
                    return trim($item);
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function calculateEngagementScore(): float
    {
        $weightedLikes = $this->like_count * 1.5;
        $weightedComments = $this->comment_count * 2;
        $weightedShares = $this->share_count * 1.25;
        $weightedViews = $this->view_count * 0.1;

        return (float) ($weightedLikes + $weightedComments + $weightedShares + $weightedViews);
    }

    protected function isPaid(): bool
    {
        if ($this->visibility === 'paid') {
            return true;
        }

        return $this->paywall_tier_id !== null;
    }
}
