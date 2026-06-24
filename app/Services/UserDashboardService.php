<?php

namespace App\Services;

use App\Models\BadgeDefinition;
use App\Models\UserBadge;
use App\Models\UserStreak;
use Illuminate\Database\Eloquent\Collection;

class UserDashboardService
{
    // Highest-priority badge category surfaces first in profile and community views.
    private const CATEGORY_PRIORITY = [
        'certification' => 1,
        'challenge'     => 2,
        'consistency'   => 3,
        'milestone'     => 4,
        'community'     => 5,
    ];

    /**
     * 7.1 — Data for the "Your Streak" widget.
     * Returns one UserStreak per streak type the user has started.
     * Progress / milestone values are computed in StreakWidgetResource.
     */
    public function getStreakWidgets(int $userId, int $creatorAppId): Collection
    {
        return UserStreak::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->orderBy('streak_type')
            ->get();
    }

    /**
     * 7.2 — Data for the badge display section.
     * Returns earned (non-revoked) UserBadge records with their definitions loaded.
     * Optionally includes locked (unearned) BadgeDefinition records.
     *
     * Phase 1: privacy_hidden is always false; privacy controls are Phase 2.
     */
    public function getBadgeDisplay(int $userId, int $creatorAppId, bool $showLocked = false): array
    {
        $earned = $this->loadEarned($userId, $creatorAppId);

        $locked = collect();
        if ($showLocked) {
            $earnedIds = $earned->pluck('badge_definition_id');
            $locked    = BadgeDefinition::where('enabled', true)
                ->where(fn ($q) => $q->where('creator_app_id', $creatorAppId)->orWhereNull('creator_app_id'))
                ->whereNotIn('id', $earnedIds)
                ->orderBy('badge_category')
                ->orderBy('name')
                ->get();
        }

        return ['earned' => $earned, 'locked' => $locked];
    }

    /**
     * 7.3 — Data for the profile badge display.
     * Returns all earned badges plus the single featured (highest-priority) badge.
     *
     * Phase 1: featured is determined automatically by category priority.
     *          User-selected featured badge is a future enhancement.
     */
    public function getProfileBadges(int $userId, int $creatorAppId): array
    {
        $earned   = $this->loadEarned($userId, $creatorAppId);
        $featured = $this->highestPriorityBadge($earned);

        return ['featured' => $featured, 'earned' => $earned];
    }

    /**
     * 7.4 — Single badge for display next to a username in community/chat areas.
     * Returns the highest-priority earned badge, or null if the user has none.
     * Callers should render nothing (not a placeholder) when null is returned.
     *
     * Phase 1: all earned badges are public; privacy settings are Phase 2.
     */
    public function getCommunityBadge(int $userId, int $creatorAppId): ?UserBadge
    {
        return $this->highestPriorityBadge(
            $this->loadEarned($userId, $creatorAppId)
        );
    }

    // -------------------------------------------------------------------------

    private function loadEarned(int $userId, int $creatorAppId): Collection
    {
        return UserBadge::with('badgeDefinition')
            ->where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->orderByDesc('earned_at')
            ->get();
    }

    private function highestPriorityBadge(Collection $earned): ?UserBadge
    {
        return $earned
            ->sortBy(fn ($ub) => self::CATEGORY_PRIORITY[$ub->badgeDefinition?->badge_category] ?? 99)
            ->first();
    }
}
