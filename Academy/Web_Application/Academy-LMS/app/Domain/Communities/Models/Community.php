<?php

namespace App\Domain\Communities\Models;

use App\Domain\Search\Concerns\Searchable;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Community extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected $guarded = [];

    protected $casts = [
        'links' => 'array',
        'settings' => 'array',
        'is_featured' => 'boolean',
        'launched_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommunityCategory::class, 'category_id');
    }

    public function geoPlace(): BelongsTo
    {
        return $this->belongsTo(GeoPlace::class, 'geo_place_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CommunityLevel::class);
    }

    public function pointsRules(): HasMany
    {
        return $this->hasMany(CommunityPointsRule::class);
    }

    public function subscriptionTiers(): HasMany
    {
        return $this->hasMany(CommunitySubscriptionTier::class);
    }

    public function adminSettings(): HasOne
    {
        return $this->hasOne(CommunityAdminSetting::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(CommunityMember::class)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->orderBy('joined_at');
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(CommunityLeaderboard::class);
    }

    public function toSearchRecord(): array
    {
        $this->loadMissing([
            'category:id,name',
            'geoPlace:id,name,country_code,metadata,timezone',
            'subscriptionTiers:id,community_id,name',
        ]);

        $settings = $this->normaliseJson($this->settings);
        $tags = $this->normaliseArray($settings['tags'] ?? []);

        if ($this->category && filled($this->category->name)) {
            $tags[] = $this->category->name;
        }

        $tags = array_values(array_unique(array_filter($tags, fn ($tag) => filled($tag))));

        $memberStats = $this->members()
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'active' AND is_online = 1 THEN 1 ELSE 0 END) as online_count")
            ->first();

        $memberCount = (int) ($memberStats?->active_count ?? 0);
        $onlineCount = (int) ($memberStats?->online_count ?? 0);

        $recentActivity = $this->posts()
            ->selectRaw('MAX(COALESCE(published_at, updated_at, created_at)) as recent_activity_at')
            ->value('recent_activity_at');

        $geoMetadata = $this->normaliseJson($this->geoPlace?->metadata ?? []);
        $locationCity = Arr::get($settings, 'location.city')
            ?? Arr::get($geoMetadata, 'city')
            ?? Arr::get($geoMetadata, 'locality')
            ?? $this->geoPlace?->name;
        $locationCountry = Arr::get($settings, 'location.country')
            ?? Arr::get($geoMetadata, 'country')
            ?? Arr::get($geoMetadata, 'country_code')
            ?? $this->geoPlace?->country_code;

        $tierNames = $this->subscriptionTiers
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => (string) $name)
            ->unique()
            ->values()
            ->all();

        $description = $this->tagline
            ?? $this->bio
            ?? ($this->about_html ? Str::limit(strip_tags($this->about_html), 280) : null);

        $recentActivityIso = null;
        if ($recentActivity) {
            $recentActivityIso = CarbonImmutable::parse($recentActivity)->toIso8601String();
        }

        return [
            'id' => (int) $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $description,
            'tags' => $tags,
            'tier_names' => $tierNames,
            'visibility' => $this->visibility,
            'member_count' => $memberCount,
            'online_count' => $onlineCount,
            'recent_activity_at' => $recentActivityIso,
            'location' => [
                'city' => $locationCity,
                'country' => $locationCountry,
                'timezone' => $this->geoPlace?->timezone,
            ],
            'is_featured' => (bool) $this->is_featured,
            'category' => [
                'id' => $this->category?->getKey(),
                'name' => $this->category?->name,
            ],
            'launched_at' => $this->launched_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
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
}
