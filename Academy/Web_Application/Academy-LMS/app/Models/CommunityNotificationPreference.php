<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EncryptedAttribute;
use App\Models\Community\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @property int $user_id
 * @property int|null $community_id
 * @property bool $channel_email
 * @property bool $channel_push
 * @property bool $channel_in_app
 * @property string $digest_frequency
 * @property array|null $muted_events
 * @property array|null $metadata
 * @property string|null $locale
 */
class CommunityNotificationPreference extends Model
{
    use HasFactory;

    protected $table = 'community_notification_preferences';

    protected $fillable = [
        'user_id',
        'community_id',
        'channel_email',
        'channel_push',
        'channel_in_app',
        'digest_frequency',
        'quiet_hours_start',
        'quiet_hours_end',
        'muted_events',
        'metadata',
        'locale',
    ];

    protected $casts = [
        'channel_email' => 'boolean',
        'channel_push' => 'boolean',
        'channel_in_app' => 'boolean',
        'muted_events' => 'array',
        'metadata' => EncryptedAttribute::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class, 'community_id');
    }

    public function scopeForCommunity(Builder $query, int $communityId): Builder
    {
        return $query->where('community_id', $communityId);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('community_id');
    }

    public function wantsEmailFor(string $eventKey): bool
    {
        return $this->channel_email && ! $this->eventIsMuted($eventKey);
    }

    public function wantsPushFor(string $eventKey): bool
    {
        return $this->channel_push && ! $this->eventIsMuted($eventKey);
    }

    public function wantsInAppFor(string $eventKey): bool
    {
        return $this->channel_in_app && ! $this->eventIsMuted($eventKey);
    }

    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    public function eventIsMuted(string $eventKey): bool
    {
        $muted = Collection::make($this->muted_events ?? []);

        if ($muted->contains('*')) {
            return true;
        }

        if ($muted->contains($eventKey)) {
            return true;
        }

        [$namespace] = explode('.', $eventKey.'._');

        return $muted->contains($namespace.'.*');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function defaults(array $attributes = []): self
    {
        $model = new self($attributes);
        $model->exists = false;
        $model->setAttribute('channel_email', true);
        $model->setAttribute('channel_push', true);
        $model->setAttribute('channel_in_app', true);
        $model->setAttribute('digest_frequency', $attributes['digest_frequency'] ?? 'daily');
        $model->setAttribute('muted_events', $attributes['muted_events'] ?? []);

        return $model;
    }

    /**
     * @param string $eventKey
     * @param array<string, mixed> $overrides
     */
    public function withOverride(string $eventKey, array $overrides): self
    {
        $clone = $this->replicate();
        $clone->exists = $this->exists;

        $muted = Collection::make($this->muted_events ?? []);
        $muted = $muted->filter(fn ($value) => $value !== $eventKey);

        if (Arr::get($overrides, 'muted', false) === true) {
            $muted = $muted->push($eventKey)->unique()->values();
        }

        $clone->muted_events = $muted->all();
        $clone->channel_email = Arr::get($overrides, 'channel_email', $this->channel_email);
        $clone->channel_push = Arr::get($overrides, 'channel_push', $this->channel_push);
        $clone->channel_in_app = Arr::get($overrides, 'channel_in_app', $this->channel_in_app);

        return $clone;
    }
}
