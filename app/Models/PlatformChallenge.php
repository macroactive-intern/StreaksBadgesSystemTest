<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformChallenge extends Model
{
    protected $fillable = [
        'title',
        'description',
        'challenge_type',
        'config',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'config'    => 'array',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(PlatformChallengeEntry::class);
    }
}
