<?php

namespace App\Domain\Communities\Models;

use Database\Factories\CommunitySubscriptionTierFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunitySubscriptionTier extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'benefits' => 'array',
        'metadata' => 'array',
        'published_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return CommunitySubscriptionTierFactory::new();
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CommunitySubscription::class, 'subscription_tier_id');
    }
}
