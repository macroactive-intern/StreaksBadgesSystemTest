<?php

namespace App\Services;

use App\Enums\StreakStatus;
use App\Models\ActivityEvent;
use App\Models\UserBadge;
use App\Models\UserStreak;
use Illuminate\Support\Collection;

class CreatorEngagementService
{
    /**
     * Return all engagement data in a single call for dashboard rendering.
     */
    public function summary(int $creatorAppId, int $limit = 10): array
    {
        return [
            'top_engaged_members'    => $this->topEngagedMembers($creatorAppId, $limit),
            'users_with_active_streaks' => $this->usersWithActiveStreaks($creatorAppId, $limit),
            'users_at_risk'          => $this->usersAtRisk($creatorAppId, $limit),
            'recently_broken_streaks' => $this->recentlyBrokenStreaks($creatorAppId, $limit),
            'recent_badge_earns'     => $this->recentBadgeEarns($creatorAppId, $limit),
        ];
    }

    /**
     * Users ranked by total activity event count for this creator app.
     * Returns: user_id, event_count.
     */
    public function topEngagedMembers(int $creatorAppId, int $limit = 10): Collection
    {
        return ActivityEvent::where('creator_app_id', $creatorAppId)
            ->selectRaw('user_id, COUNT(*) as event_count')
            ->groupBy('user_id')
            ->orderByDesc('event_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Users with at least one active streak, ordered by longest current streak.
     * Returns: user_id, streak_type, current_count, longest_count, last_completed_date.
     */
    public function usersWithActiveStreaks(int $creatorAppId, int $limit = 10): Collection
    {
        return UserStreak::where('creator_app_id', $creatorAppId)
            ->where('status', StreakStatus::Active->value)
            ->where('current_count', '>', 0)
            ->orderByDesc('current_count')
            ->limit($limit)
            ->get(['user_id', 'streak_type', 'current_count', 'longest_count', 'last_completed_date']);
    }

    /**
     * Users whose streak is at risk (no completion today), ordered by streak value descending
     * so the highest-value streaks surface first.
     * Returns: user_id, streak_type, current_count, last_completed_date.
     */
    public function usersAtRisk(int $creatorAppId, int $limit = 10): Collection
    {
        return UserStreak::where('creator_app_id', $creatorAppId)
            ->where('status', StreakStatus::AtRisk->value)
            ->orderByDesc('current_count')
            ->limit($limit)
            ->get(['user_id', 'streak_type', 'current_count', 'last_completed_date']);
    }

    /**
     * Streaks broken within the last 7 days, most recent first.
     * Returns: user_id, streak_type, longest_count, last_evaluated_date.
     */
    public function recentlyBrokenStreaks(int $creatorAppId, int $limit = 10): Collection
    {
        return UserStreak::where('creator_app_id', $creatorAppId)
            ->where('status', StreakStatus::Broken->value)
            ->where('last_evaluated_date', '>=', now()->subDays(7)->toDateString())
            ->orderByDesc('last_evaluated_date')
            ->limit($limit)
            ->get(['user_id', 'streak_type', 'longest_count', 'last_evaluated_date']);
    }

    /**
     * Badge awards (non-revoked) in the last 7 days, most recent first.
     * Returns: user_id, badge_definition_id, earned_at, awarded_by.
     */
    public function recentBadgeEarns(int $creatorAppId, int $limit = 10): Collection
    {
        return UserBadge::where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->where('earned_at', '>=', now()->subDays(7))
            ->orderByDesc('earned_at')
            ->limit($limit)
            ->get(['user_id', 'badge_definition_id', 'earned_at', 'awarded_by']);
    }
}
