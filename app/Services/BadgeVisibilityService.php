<?php

namespace App\Services;

use App\Models\UserBadge;
use RuntimeException;

class BadgeVisibilityService
{
    /**
     * 12.1 — Show or hide a badge from public views (profile, community).
     * The badge remains in the owner's own badge list regardless.
     */
    public function setHidden(UserBadge $badge, bool $hidden): UserBadge
    {
        $badge->update(['privacy_hidden' => $hidden]);

        // Un-feature a badge that is being hidden — a hidden badge should not be showcased.
        if ($hidden && $badge->is_featured) {
            $badge->update(['is_featured' => false]);
        }

        return $badge->refresh();
    }

    /**
     * 12.1 — Mark a badge as the user's featured badge for this creator app.
     * Clears the is_featured flag on any previously featured badge.
     * Throws if the badge is hidden — a hidden badge cannot be featured.
     */
    public function setFeatured(UserBadge $badge): UserBadge
    {
        if ($badge->privacy_hidden) {
            throw new RuntimeException('A hidden badge cannot be set as the featured badge.');
        }

        // Exactly one featured badge per (user_id, creator_app_id).
        UserBadge::where('user_id', $badge->user_id)
            ->where('creator_app_id', $badge->creator_app_id)
            ->where('id', '!=', $badge->id)
            ->where('is_featured', true)
            ->update(['is_featured' => false]);

        $badge->update(['is_featured' => true]);

        return $badge->refresh();
    }

    /**
     * 12.1 — Remove the featured designation from all badges for this user.
     * Falls back to automatic category-priority selection in UserDashboardService.
     */
    public function clearFeatured(int $userId, int $creatorAppId): void
    {
        UserBadge::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('is_featured', true)
            ->update(['is_featured' => false]);
    }
}
