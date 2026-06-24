<?php

namespace App\Services;

use App\Models\NotificationTrigger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class NotificationTriggerService
{
    public const TYPE_STREAK_AT_RISK   = 'streak_at_risk';
    public const TYPE_STREAK_BROKEN    = 'streak_broken';
    public const TYPE_STREAK_MILESTONE = 'streak_milestone';
    public const TYPE_BADGE_EARNED     = 'badge_earned';

    public function create(
        int $userId,
        int $creatorAppId,
        string $type,
        array $payload,
        ?Carbon $scheduledFor = null,
    ): NotificationTrigger {
        return NotificationTrigger::create([
            'user_id'        => $userId,
            'creator_app_id' => $creatorAppId,
            'trigger_type'   => $type,
            'payload'        => $payload,
            'scheduled_for'  => $scheduledFor ?? now(),
        ]);
    }

    /**
     * Unsent triggers whose scheduled_for has passed, ordered oldest first.
     */
    public function pending(int $limit = 200): Collection
    {
        return NotificationTrigger::whereNull('sent_at')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->get();
    }

    public function markSent(NotificationTrigger $trigger): void
    {
        $trigger->update(['sent_at' => now()]);
    }

    /**
     * Returns true if an unsent trigger of this type already exists for the user today.
     * Prevents duplicate at-risk/broken triggers for the same day.
     */
    public function hasPendingTodayOf(int $userId, int $creatorAppId, string $type, string $localDate): bool
    {
        return NotificationTrigger::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('trigger_type', $type)
            ->whereNull('sent_at')
            ->whereDate('scheduled_for', $localDate)
            ->exists();
    }
}
