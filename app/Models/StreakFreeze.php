<?php

namespace App\Models;

use App\Enums\StreakType;
use Illuminate\Database\Eloquent\Model;

class StreakFreeze extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'streak_type',
        'earned_at',
        'used_at',
        'applied_to_date',
    ];

    protected $casts = [
        'streak_type' => StreakType::class,
        'earned_at' => 'datetime',
        'used_at' => 'datetime',
        'applied_to_date' => 'date',
    ];
}
