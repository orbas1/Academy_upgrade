<?php

declare(strict_types=1);

namespace App\Domain\Search\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSavedQuery extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'filters' => 'array',
        'flags' => 'array',
        'sort' => 'array',
        'is_shared' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function markUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }

    public function scopeAccessibleTo($query, User $user)
    {
        return $query
            ->where('user_id', $user->getKey())
            ->orWhere('is_shared', true);
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->getKey();
    }
}

