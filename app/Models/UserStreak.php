<?php

namespace App\Models;

use App\Enums\StreakStatus;
use App\Enums\StreakType;
use Illuminate\Database\Eloquent\Model;

class UserStreak extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'streak_type',
        'current_count',
        'longest_count',
        'last_completed_date',
        'last_evaluated_date',
        'status',
    ];

    protected $casts = [
        'streak_type' => StreakType::class,
        'status' => StreakStatus::class,
        'current_count' => 'integer',
        'longest_count' => 'integer',
        'last_completed_date' => 'date',
        'last_evaluated_date' => 'date',
    ];
}
