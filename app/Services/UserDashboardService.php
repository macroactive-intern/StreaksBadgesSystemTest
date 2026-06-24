<?php

namespace App\Services;

use App\Models\BadgeDefinition;
use App\Models\UserBadge;
use App\Models\UserStreak;
use Illuminate\Database\Eloquent\Collection;

class UserDashboardService
{
    // Highest-priority badge category for automatic featured selection (fallback when
    // no badge has is_featured = true).
    private const CATEGORY_PRIORITY = [
        'certification' => 1,
        'challenge'     => 2,
        'consistency'   => 3,
        'milestone'     => 4,
        'community'     => 5,
    ];

    /**
     * 7.1 — Data for the "Your Streak" widget.
     */
    public function getStreakWidgets(int $userId, int $creatorAppId): Collection
    {
        return UserStreak::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->orderBy('streak_type')
            ->get();
    }

    /**
     * 7.2 — Badge display section (owner view).
     * Returns ALL earned non-revoked badges including hidden ones, so the user
     * can see and manage their privacy settings.
     * privacy_hidden and is_featured are included in EarnedBadgeResource.
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
     * 7.3 — Profile badge display (public-facing).
     * Excludes hidden badges. Featured badge is the user-selected one if set,
     * otherwise the highest-priority visible badge by category.
     */
    public function getProfileBadges(int $userId, int $creatorAppId): array
    {
        $visible  = $this->loadVisible($userId, $creatorAppId);
        $featured = $this->highestPriorityBadge($visible);

        return ['featured' => $featured, 'earned' => $visible];
    }

    /**
     * 7.4 — Single badge for community / chat display (public-facing).
     * Excludes hidden badges. Respects is_featured; falls back to category priority.
     * Returns null when the user has no visible earned badges.
     */
    public function getCommunityBadge(int $userId, int $creatorAppId): ?UserBadge
    {
        return $this->highestPriorityBadge(
            $this->loadVisible($userId, $creatorAppId)
        );
    }

    // -------------------------------------------------------------------------

    /** All non-revoked badges — used for the owner's own badge list. */
    private function loadEarned(int $userId, int $creatorAppId): Collection
    {
        return UserBadge::with('badgeDefinition')
            ->where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->orderByDesc('earned_at')
            ->get();
    }

    /** Non-revoked, non-hidden badges — used for public-facing views. */
    private function loadVisible(int $userId, int $creatorAppId): Collection
    {
        return UserBadge::with('badgeDefinition')
            ->where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->whereNull('revoked_at')
            ->where('privacy_hidden', false)
            ->orderByDesc('earned_at')
            ->get();
    }

    /**
     * Returns the user-selected featured badge if one is set, otherwise the
     * highest-priority badge by category. Returns null on an empty collection.
     */
    private function highestPriorityBadge(Collection $badges): ?UserBadge
    {
        $featured = $badges->firstWhere('is_featured', true);
        if ($featured !== null) {
            return $featured;
        }

        return $badges
            ->sortBy(fn ($ub) => self::CATEGORY_PRIORITY[$ub->badgeDefinition?->badge_category] ?? 99)
            ->first();
    }
}
