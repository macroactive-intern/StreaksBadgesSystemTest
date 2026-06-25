<?php

namespace App\Services;

use App\Enums\StreakType;
use App\Exceptions\FreezeCooldownException;
use App\Exceptions\NoAvailableFreezeException;
use App\Models\StreakFreeze;
use Illuminate\Support\Facades\DB;

class StreakFreezeService
{
    /**
     * Grant a freeze to a user.
     * Phase 1: freezes do not stack — returns null if the user already holds one.
     */
    public function grant(int $userId, int $creatorAppId, StreakType $streakType): ?StreakFreeze
    {
        if ($this->hasAvailableFreeze($userId, $creatorAppId, $streakType)) {
            return null;
        }

        return StreakFreeze::create([
            'user_id'        => $userId,
            'creator_app_id' => $creatorAppId,
            'streak_type'    => $streakType->value,
            'earned_at'      => now(),
        ]);
    }

    /**
     * Apply the user's available freeze to a specific missed local date.
     * Enforces the 30-day cooldown between usages.
     */
    public function apply(int $userId, int $creatorAppId, StreakType $streakType, string $missedDate): StreakFreeze
    {
        return DB::transaction(function () use ($userId, $creatorAppId, $streakType, $missedDate) {
            // Lock the row so concurrent requests block here rather than racing past the checks.
            $freeze = StreakFreeze::where('user_id', $userId)
                ->where('creator_app_id', $creatorAppId)
                ->where('streak_type', $streakType->value)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();

            if (!$freeze) {
                throw new NoAvailableFreezeException();
            }

            if ($this->withinCooldown($userId, $creatorAppId, $streakType)) {
                throw new FreezeCooldownException();
            }

            $freeze->update([
                'used_at'         => now(),
                'applied_to_date' => $missedDate,
            ]);

            return $freeze->refresh();
        });
    }

    /**
     * Returns true if the user holds an unused freeze for this streak type.
     * Phase 1: at most 1 freeze at a time (no stacking).
     */
    public function hasAvailableFreeze(int $userId, int $creatorAppId, StreakType $streakType): bool
    {
        return StreakFreeze::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('streak_type', $streakType->value)
            ->whereNull('used_at')
            ->exists();
    }

    /**
     * Returns true if a freeze was used within the last 30 days (cooldown check).
     */
    public function withinCooldown(int $userId, int $creatorAppId, StreakType $streakType): bool
    {
        return StreakFreeze::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('streak_type', $streakType->value)
            ->whereNotNull('used_at')
            ->where('used_at', '>=', now()->subDays(30))
            ->exists();
    }

    /**
     * Returns true if a freeze was applied to a specific local date.
     * Used by the streak evaluation service to bridge 1-day gaps.
     */
    public function isFreezeAppliedToDate(
        int $userId,
        int $creatorAppId,
        StreakType $streakType,
        string $localDate,
    ): bool {
        return StreakFreeze::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('streak_type', $streakType->value)
            ->whereNotNull('used_at')
            ->whereDate('applied_to_date', $localDate)
            ->exists();
    }
}
