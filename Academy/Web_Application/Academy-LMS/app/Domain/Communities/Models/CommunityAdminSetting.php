<?php

namespace App\Domain\Communities\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAdminSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'moderation_rules' => 'array',
        'membership_requirements' => 'array',
        'posting_policies' => 'array',
        'escalation_contacts' => 'array',
        'automation_settings' => 'array',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
