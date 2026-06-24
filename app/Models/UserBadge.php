<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'badge_definition_id',
        'earned_at',
        'awarded_by',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function badgeDefinition()
    {
        return $this->belongsTo(BadgeDefinition::class);
    }
}
