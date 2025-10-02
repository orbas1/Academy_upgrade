<?php

namespace App\Domain\Communities\Models;

use App\Casts\EncryptedAttribute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunitySubscription extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => EncryptedAttribute::class,
        'renews_at' => 'datetime',
        'ended_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CommunitySubscriptionTier::class, 'subscription_tier_id');
    }
}
