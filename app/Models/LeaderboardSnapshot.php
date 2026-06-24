<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderboardSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'creator_app_id',
        'leaderboard_type',
        'period_key',
        'user_id',
        'score',
        'rank',
        'nickname',
        'snapped_at',
    ];

    protected $casts = [
        'snapped_at' => 'datetime',
    ];
}
