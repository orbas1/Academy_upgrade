<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor_role',
        'action',
        'http_method',
        'status_code',
        'ip_address',
        'user_agent',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'ip_address' => EncryptedAttribute::class,
        'user_agent' => EncryptedAttribute::class,
        'metadata' => EncryptedAttribute::class,
        'performed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
