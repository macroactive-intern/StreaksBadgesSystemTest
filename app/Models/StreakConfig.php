<?php

namespace App\Models;

use App\Enums\StreakType;
use Illuminate\Database\Eloquent\Model;

class StreakConfig extends Model
{
    protected $fillable = [
        'creator_app_id',
        'streak_type',
        'enabled',
        'qualifying_event_type',
        'minimum_threshold',
        'reward_config',
    ];

    protected $casts = [
        'streak_type' => StreakType::class,
        'enabled' => 'boolean',
        'minimum_threshold' => 'integer',
        'reward_config' => 'array',
    ];
}
