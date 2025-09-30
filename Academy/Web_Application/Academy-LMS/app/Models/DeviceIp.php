<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'session_id',
        'user_agent',
        'device_name',
        'platform',
        'app_version',
        'label',
        'trusted_at',
        'revoked_at',
        'last_seen_at',
        'last_headers',
    ];

    protected $casts = [
        'trusted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_headers' => 'array',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(DeviceAccessToken::class, 'device_ip_id');
    }
}
