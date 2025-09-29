<?php

namespace App\Domain\Communities\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    use HasFactory;
    use SoftDeletes;

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

    public function leaderboards(): HasMany
    {
        return $this->hasMany(CommunityLeaderboard::class);
    }
}
