<?php

namespace App\Domain\Communities\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPointsRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'conditions' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
