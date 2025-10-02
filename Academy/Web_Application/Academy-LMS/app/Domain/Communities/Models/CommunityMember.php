<?php

namespace App\Domain\Communities\Models;

use App\Casts\EncryptedAttribute;
use App\Models\User;
use Database\Factories\CommunityMemberFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityMember extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'badges' => 'array',
        'preferences' => EncryptedAttribute::class,
        'metadata' => EncryptedAttribute::class,
        'is_online' => 'boolean',
        'joined_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    protected static function newFactory(): Factory
    {
        return CommunityMemberFactory::new();
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointsLedger(): HasMany
    {
        return $this->hasMany(CommunityPointsLedger::class, 'member_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'author_id', 'user_id');
    }
}
