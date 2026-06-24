<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\AnalyticsEvent;
use App\Models\UserStatusLevel;
use App\Models\UserStreak;
use Carbon\Carbon;

class CrossAppIdentityService
{
    /**
     * XP awarded per analytics event type.
     */
    private const XP_MAP = [
        AnalyticsEvent::STREAK_STARTED   => 5,
        AnalyticsEvent::STREAK_CONTINUED => 1,
        AnalyticsEvent::BADGE_EARNED     => 10,
    ];

    /**
     * XP thresholds per level (index = level - 1).
     */
    private const LEVEL_THRESHOLDS = [0, 50, 150, 300, 500, 750, 1000, 1500, 2500, 5000];

    /**
     * 15.2 — Get (or create) a user's universal status level record.
     */
    public function getStatusLevel(int $userId): UserStatusLevel
    {
        return UserStatusLevel::firstOrCreate(
            ['user_id' => $userId],
            ['xp_total' => 0, 'level' => 1],
        );
    }

    /**
     * 15.2 — Recompute XP and level from analytics_events across all creator apps.
     */
    public function recomputeStatusLevel(int $userId): UserStatusLevel
    {
        $xp = 0;

        foreach (self::XP_MAP as $eventType => $points) {
            $count = \App\Models\AnalyticsEvent::where('user_id', $userId)
                ->where('event_type', $eventType)
                ->count();

            $xp += $count * $points;
        }

        $level = $this->xpToLevel($xp);

        $statusLevel = UserStatusLevel::updateOrCreate(
            ['user_id' => $userId],
            ['xp_total' => $xp, 'level' => $level, 'computed_at' => now()],
        );

        return $statusLevel;
    }

    /**
     * 15.2 — Cross-app streak: longest consecutive-day run the user has had
     * across ANY creator app, based on distinct local_event_dates.
     */
    public function getCrossAppStreak(int $userId): int
    {
        $dates = ActivityEvent::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->selectRaw('DISTINCT local_event_date')
            ->orderBy('local_event_date')
            ->pluck('local_event_date')
            ->map(fn ($d) => Carbon::parse($d))
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $longest = 1;
        $current = 1;

        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates[$i]->diffInDays($dates[$i - 1]) === 1) {
                $current++;
                $longest = max($longest, $current);
            } else {
                $current = 1;
            }
        }

        return $longest;
    }

    /**
     * 15.2 — All creator app IDs the user has ever been active in.
     *
     * @return int[]
     */
    public function getActiveCreatorApps(int $userId): array
    {
        return ActivityEvent::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->distinct('creator_app_id')
            ->pluck('creator_app_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * 15.2 — Current cross-app streak: consecutive days ending today (or yesterday).
     */
    public function getCurrentCrossAppStreak(int $userId): int
    {
        $dates = ActivityEvent::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->selectRaw('DISTINCT local_event_date')
            ->orderByDesc('local_event_date')
            ->pluck('local_event_date')
            ->map(fn ($d) => Carbon::parse($d))
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $today     = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        // Streak must end today or yesterday to be considered current.
        if (!in_array($dates[0]->toDateString(), [$today, $yesterday], true)) {
            return 0;
        }

        $streak = 1;

        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates[$i - 1]->diffInDays($dates[$i]) === 1) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function xpToLevel(int $xp): int
    {
        $level = 1;

        foreach (self::LEVEL_THRESHOLDS as $index => $threshold) {
            if ($xp >= $threshold) {
                $level = $index + 1;
            }
        }

        return min($level, count(self::LEVEL_THRESHOLDS));
    }
}
