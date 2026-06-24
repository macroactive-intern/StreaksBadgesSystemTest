<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityEvent extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'event_type',
        'event_timestamp_utc',
        'user_timezone',
        'local_event_date',
        'metadata',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'event_timestamp_utc' => 'datetime',
        'local_event_date' => 'date',
        'metadata' => 'array',
    ];
}
