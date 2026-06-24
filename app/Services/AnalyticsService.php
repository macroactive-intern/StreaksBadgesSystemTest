<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\StreakStatus;
use App\Models\ActivityEvent;
use App\Models\AnalyticsEvent;
use App\Models\UserBadge;
use App\Models\UserStreak;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * 13.2 — Full engagement metrics summary for a creator app on a given date.
     */
    public function summary(int $creatorAppId, string $date): array
    {
        return [
            'date'                       => $date,
            'daily_active_users'         => $this->dailyActiveUsers($creatorAppId, $date),
            'habit_completion_rate'      => $this->habitCompletionRate($creatorAppId, $date),
            'community_participation_rate' => $this->communityParticipationRate($creatorAppId, $date),
            'active_streak_percentage'   => $this->activeStreakPercentage($creatorAppId),
            'badge_earn_rate'            => $this->badgeEarnRate($creatorAppId),
        ];
    }

    /**
     * 13.2 — Count of distinct users with any activity event on the given local date.
     */
    public function dailyActiveUsers(int $creatorAppId, string $date): int
    {
        return ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('local_event_date', $date)
            ->whereNull('revoked_at')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * 13.2 — Fraction of historically active users who completed a habit today.
     * "Active" = has ever logged a habit_completed event.
     * Returns a float 0–1 (e.g. 0.42 = 42 %).
     */
    public function habitCompletionRate(int $creatorAppId, string $date): float
    {
        $totalHabitUsers = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::HabitCompleted->value)
            ->whereNull('revoked_at')
            ->distinct('user_id')
            ->count('user_id');

        if ($totalHabitUsers === 0) {
            return 0.0;
        }

        $completedToday = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::HabitCompleted->value)
            ->where('local_event_date', $date)
            ->whereNull('revoked_at')
            ->distinct('user_id')
            ->count('user_id');

        return round($completedToday / $totalHabitUsers, 4);
    }

    /**
     * 13.2 — Fraction of users who posted a community comment in the last 7 days.
     * Denominator = all users with any event for this creator app.
     * Returns a float 0–1.
     */
    public function communityParticipationRate(int $creatorAppId, string $date): float
    {
        $totalUsers = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->distinct('user_id')
            ->count('user_id');

        if ($totalUsers === 0) {
            return 0.0;
        }

        $cutoff = Carbon::parse($date)->subDays(7)->toDateString();

        $communityUsers = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::CommunityCommentPosted->value)
            ->where('local_event_date', '>=', $cutoff)
            ->whereNull('revoked_at')
            ->distinct('user_id')
            ->count('user_id');

        return round($communityUsers / $totalUsers, 4);
    }

    /**
     * 13.2 — Fraction of users with at least one streak who have an active streak now.
     * Returns a float 0–1.
     */
    public function activeStreakPercentage(int $creatorAppId): float
    {
        $totalWithStreaks = UserStreak::where('creator_app_id', $creatorAppId)
            ->distinct('user_id')
            ->count('user_id');

        if ($totalWithStreaks === 0) {
            return 0.0;
        }

        $activeUsers = UserStreak::where('creator_app_id', $creatorAppId)
            ->where('status', StreakStatus::Active->value)
            ->where('current_count', '>', 0)
            ->distinct('user_id')
            ->count('user_id');

        return round($activeUsers / $totalWithStreaks, 4);
    }

    /**
     * 13.2 — Average badge earns per distinct user over the last N days.
     * Returns a float (e.g. 0.3 = 0.3 badges earned per user in the period).
     */
    public function badgeEarnRate(int $creatorAppId, int $days = 30): float
    {
        $since = now()->subDays($days);

        $totalUsers = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->distinct('user_id')
            ->count('user_id');

        if ($totalUsers === 0) {
            return 0.0;
        }

        $badgesEarned = UserBadge::where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->where('earned_at', '>=', $since)
            ->count();

        return round($badgesEarned / $totalUsers, 4);
    }

    /**
     * 13.1 — Recent engagement event counts broken down by type.
     */
    public function engagementEventCounts(int $creatorAppId, int $days = 30): array
    {
        $since = now()->subDays($days);

        return AnalyticsEvent::where('creator_app_id', $creatorAppId)
            ->where('occurred_at', '>=', $since)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderBy('event_type')
            ->get()
            ->pluck('count', 'event_type')
            ->all();
    }
}
