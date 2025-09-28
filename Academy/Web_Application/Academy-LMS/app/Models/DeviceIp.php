<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'session_id',
        'user_agent',
        'label',
        'trusted_at',
        'last_seen_at',
    ];

    protected $casts = [
        'trusted_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
