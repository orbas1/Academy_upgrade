<?php

declare(strict_types=1);

namespace App\Domain\Search\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSavedSearch extends Model
{
    use HasFactory;

    protected $table = 'admin_saved_searches';

    protected $fillable = [
        'user_id',
        'name',
        'scope',
        'query',
        'filters',
        'sort',
        'frequency',
        'last_triggered_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'last_triggered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

