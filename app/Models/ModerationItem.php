<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Model;

class ModerationItem extends Model
{
    protected $fillable = [
        'user_id',
        'creator_app_id',
        'detection_type',
        'severity',
        'payload',
        'status',
        'reviewed_at',
        'reviewer_id',
        'review_notes',
    ];

    protected $casts = [
        'payload'     => 'array',
        'reviewed_at' => 'datetime',
        'status'      => ModerationStatus::class,
    ];
}
