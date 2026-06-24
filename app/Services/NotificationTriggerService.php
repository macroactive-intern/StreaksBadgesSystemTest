<?php

namespace App\Services;

use App\Models\NotificationTrigger;
use App\Models\UserBadge;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class NotificationTriggerService
{
    public const TYPE_STREAK_AT_RISK   = 'streak_at_risk';
    public const TYPE_STREAK_BROKEN    = 'streak_broken';
    public const TYPE_STREAK_MILESTONE = 'streak_milestone';
    public const TYPE_BADGE_EARNED     = 'badge_earned';

    // Hours before local midnight to send the at-risk reminder (9 PM local).
    private const AT_RISK_REMINDER_HOUR = 21;

    // -------------------------------------------------------------------------
    // Typed factory methods (preferred — enforce correct payload shape)
    // -------------------------------------------------------------------------

    /**
     * 10.1 — At-risk reminder scheduled for 9 PM in the user's local timezone.
     * If 9 PM has already passed, fires immediately.
     * Returns null (and does NOT insert) when a trigger already exists for today.
     */
    public function atRisk(
        int $userId,
        int $creatorAppId,
        string $streakType,
        int $currentCount,
        string $timezone,
        string $localDate,
    ): ?NotificationTrigger {
        if ($this->hasAnyTodayOf($userId, $creatorAppId, self::TYPE_STREAK_AT_RISK, $localDate)) {
            return null;
        }

        $reminderLocal = Carbon::parse($localDate, $timezone)
            ->setHour(self::AT_RISK_REMINDER_HOUR)
            ->setMinute(0)
            ->setSecond(0);

        $scheduledFor = $reminderLocal->isPast() ? now() : $reminderLocal->utc();

        return $this->create($userId, $creatorAppId, self::TYPE_STREAK_AT_RISK, [
            'local_date'    => $localDate,
            'streak_type'   => $streakType,
            'current_count' => $currentCount,
            'message'       => "You haven't completed today's activity. Don't break your {$currentCount}-day streak!",
        ], $scheduledFor);
    }

    /**
     * 10.2 — Streak broken trigger with reactivation messaging.
     */
    public function broken(
        int $userId,
        int $creatorAppId,
        string $streakType,
        int $longestCount,
        int $previousCount,
        string $localDate,
    ): NotificationTrigger {
        return $this->create($userId, $creatorAppId, self::TYPE_STREAK_BROKEN, [
            'local_date'           => $localDate,
            'streak_type'          => $streakType,
            'longest_count'        => $longestCount,
            'previous_count'       => $previousCount,
            'reactivation_message' => 'Your streak ended, but every champion starts again. Begin a new streak today!',
        ]);
    }

    /**
     * 10.3 — Milestone trigger including any badges earned at this milestone.
     *
     * @param UserBadge[] $badgesEarned  UserBadge models with badgeDefinition already loaded.
     */
    public function milestone(
        int $userId,
        int $creatorAppId,
        string $streakType,
        int $milestone,
        int $currentCount,
        string $localDate,
        array $badgesEarned = [],
    ): NotificationTrigger {
        $badgePayloads = array_map(fn (UserBadge $ub) => [
            'badge_id'          => $ub->badge_definition_id,
            'badge_name'        => $ub->badgeDefinition?->name,
            'badge_description' => $ub->badgeDefinition?->description,
            'badge_icon'        => $ub->badgeDefinition?->icon,
        ], $badgesEarned);

        return $this->create($userId, $creatorAppId, self::TYPE_STREAK_MILESTONE, [
            'local_date'    => $localDate,
            'streak_type'   => $streakType,
            'milestone'     => $milestone,
            'current_count' => $currentCount,
            'badges_earned' => $badgePayloads,
        ]);
    }

    /**
     * 10.4 — Badge earned trigger with full badge details.
     * Expects badgeDefinition to be loaded on the UserBadge.
     */
    public function badgeEarned(
        int $userId,
        int $creatorAppId,
        UserBadge $userBadge,
        string $localDate,
    ): NotificationTrigger {
        $def = $userBadge->badgeDefinition;

        return $this->create($userId, $creatorAppId, self::TYPE_BADGE_EARNED, [
            'local_date'        => $localDate,
            'badge_id'          => $userBadge->badge_definition_id,
            'badge_name'        => $def?->name,
            'badge_description' => $def?->description,
            'badge_icon'        => $def?->icon,
            'badge_category'    => $def?->badge_category,
            'earned_at'         => $userBadge->earned_at->toIso8601String(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Generic creation and query helpers
    // -------------------------------------------------------------------------

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
     * Returns true when ANY trigger of this type (sent or pending) already exists
     * for the given local date. Uses the local_date stored inside the JSON payload.
     */
    public function hasAnyTodayOf(int $userId, int $creatorAppId, string $type, string $localDate): bool
    {
        return NotificationTrigger::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('trigger_type', $type)
            ->whereRaw("json_extract(payload, '$.local_date') = ?", [$localDate])
            ->exists();
    }

    /**
     * @deprecated  Use hasAnyTodayOf() — it covers sent triggers too.
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
