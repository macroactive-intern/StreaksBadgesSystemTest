<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTrigger extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'trigger_type',
        'payload',
        'scheduled_for',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
