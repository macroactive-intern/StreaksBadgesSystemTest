<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'leaderboard_nickname',
        'leaderboard_visible',
    ];

    protected $casts = [
        'leaderboard_visible' => 'boolean',
    ];
}
