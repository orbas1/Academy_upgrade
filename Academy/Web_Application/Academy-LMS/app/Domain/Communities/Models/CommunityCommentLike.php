<?php

namespace App\Domain\Communities\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityCommentLike extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'reacted_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(CommunityPostComment::class, 'comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
