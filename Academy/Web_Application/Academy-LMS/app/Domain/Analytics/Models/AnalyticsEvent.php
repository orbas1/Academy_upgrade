<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'recorded_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    public function markDelivered(?string $status = 'delivered', ?string $error = null): void
    {
        $this->forceFill([
            'delivery_status' => $status ?? 'delivered',
            'delivered_at' => CarbonImmutable::now(),
            'delivery_error' => $error,
        ])->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
