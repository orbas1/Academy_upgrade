<?php

namespace App\Domain\Communities\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityAdminSetting extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'moderation_rules' => EncryptedAttribute::class,
        'membership_requirements' => EncryptedAttribute::class,
        'posting_policies' => EncryptedAttribute::class,
        'escalation_contacts' => EncryptedAttribute::class,
        'automation_settings' => EncryptedAttribute::class,
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
