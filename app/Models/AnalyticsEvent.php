<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    public const STREAK_STARTED   = 'streak_started';
    public const STREAK_CONTINUED = 'streak_continued';
    public const STREAK_BROKEN    = 'streak_broken';
    public const BADGE_EARNED     = 'badge_earned';
    public const FREEZE_USED      = 'freeze_used';

    protected $fillable = [
        'creator_app_id',
        'user_id',
        'event_type',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'occurred_at' => 'datetime',
    ];
}
