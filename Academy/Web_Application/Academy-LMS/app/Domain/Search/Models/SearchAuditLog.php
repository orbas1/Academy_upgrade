<?php

declare(strict_types=1);

namespace App\Domain\Search\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchAuditLog extends Model
{
    protected $table = 'search_audit_logs';

    protected $fillable = [
        'user_id',
        'scope',
        'query',
        'filters',
        'result_count',
        'is_admin',
        'executed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'executed_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

