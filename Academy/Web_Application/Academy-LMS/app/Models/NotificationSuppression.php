<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSuppression extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'identifier',
        'reason',
        'provider',
        'payload',
        'suppressed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'suppressed_at' => 'datetime',
    ];
}
