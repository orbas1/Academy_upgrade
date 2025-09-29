<?php

namespace App\Domain\Communities\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'media' => 'array',
        'mentions' => 'array',
        'topics' => 'array',
        'metadata' => 'array',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

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
}
