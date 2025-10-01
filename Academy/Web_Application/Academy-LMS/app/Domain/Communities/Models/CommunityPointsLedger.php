<?php

namespace App\Domain\Communities\Models;

use App\Models\User;
use Database\Factories\CommunityPointsLedgerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunityPointsLedger extends Model
{
    use HasFactory;

    protected $table = 'community_points_ledger';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function newFactory(): Factory
    {
        return CommunityPointsLedgerFactory::new();
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CommunityMember::class, 'member_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
