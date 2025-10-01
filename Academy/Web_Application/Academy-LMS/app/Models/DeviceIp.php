<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
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
        'ip_address' => EncryptedAttribute::class,
        'session_id' => EncryptedAttribute::class,
        'device_name' => EncryptedAttribute::class,
        'platform' => EncryptedAttribute::class,
        'app_version' => EncryptedAttribute::class,
        'label' => EncryptedAttribute::class,
        'trusted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_headers' => EncryptedAttribute::class,
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(DeviceAccessToken::class, 'device_ip_id');
    }
}
