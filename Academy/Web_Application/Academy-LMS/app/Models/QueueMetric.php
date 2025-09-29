<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueMetric extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;
    public const CREATED_AT = null;

    protected $table = 'queue_metrics';

    protected $fillable = [
        'queue_name',
        'connection_name',
        'pending_jobs',
        'reserved_jobs',
        'delayed_jobs',
        'oldest_pending_seconds',
        'oldest_reserved_seconds',
        'oldest_delayed_seconds',
        'backlog_delta_per_minute',
        'recorded_at',
    ];

    protected $casts = [
        'pending_jobs' => 'integer',
        'reserved_jobs' => 'integer',
        'delayed_jobs' => 'integer',
        'oldest_pending_seconds' => 'integer',
        'oldest_reserved_seconds' => 'integer',
        'oldest_delayed_seconds' => 'integer',
        'backlog_delta_per_minute' => 'float',
        'recorded_at' => 'datetime',
    ];
}
