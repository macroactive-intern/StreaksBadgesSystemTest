<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatusLevel extends Model
{
    protected $fillable = [
        'user_id',
        'xp_total',
        'level',
        'computed_at',
    ];

    protected $casts = [
        'computed_at' => 'datetime',
    ];
}
