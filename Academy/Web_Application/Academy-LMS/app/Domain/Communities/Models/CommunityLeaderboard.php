<?php

namespace App\Domain\Communities\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityLeaderboard extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'entries' => 'array',
        'metadata' => EncryptedAttribute::class,
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
