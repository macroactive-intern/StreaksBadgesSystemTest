<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformChallengeEntry extends Model
{
    protected $fillable = [
        'platform_challenge_id',
        'user_id',
        'creator_app_id',
        'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function platformChallenge(): BelongsTo
    {
        return $this->belongsTo(PlatformChallenge::class);
    }
}
