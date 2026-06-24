<?php

namespace App\Services;

use App\Data\StreakEvaluationResult;
use App\Enums\EventType;
use App\Enums\StreakStatus;
use App\Enums\StreakType;
use App\Models\StreakConfig;
use App\Models\UserStreak;
use Carbon\Carbon;

class StreakEvaluationService
{
    public function __construct(
        private ActivityEventService $eventService,
        private StreakFreezeService $freezeService,
    ) {}

    /**
     * Evaluate all enabled streak types for a user on a given local date.
     *
     * @return StreakEvaluationResult[]
     */
    public function evaluateAllForUser(int $userId, int $creatorAppId, string $localDate): array
    {
        return StreakConfig::where('creator_app_id', $creatorAppId)
            ->where('enabled', true)
            ->get()
            ->map(fn (StreakConfig $config) => $this->evaluateWithConfig($userId, $creatorAppId, $localDate, $config))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Evaluate a single streak type for a user on a given local date.
     * Returns null if no enabled config exists for this streak type.
     */
    public function evaluateStreak(
        int $userId,
        int $creatorAppId,
        StreakType $streakType,
        string $localDate,
    ): ?StreakEvaluationResult {
        $config = StreakConfig::where('creator_app_id', $creatorAppId)
            ->where('streak_type', $streakType->value)
            ->where('enabled', true)
            ->first();

        if ($config === null) {
            return null;
        }

        return $this->evaluateWithConfig($userId, $creatorAppId, $localDate, $config);
    }

    private function evaluateWithConfig(
        int $userId,
        int $creatorAppId,
        string $localDate,
        StreakConfig $config,
    ): ?StreakEvaluationResult {
        // streak_type is cast to StreakType enum on StreakConfig
        $streakType = $config->streak_type;
        $eventType  = EventType::from($config->qualifying_event_type);
        $yesterday  = Carbon::parse($localDate)->subDay()->toDateString();

        $streak = UserStreak::firstOrNew(
            [
                'user_id'        => $userId,
                'creator_app_id' => $creatorAppId,
                'streak_type'    => $streakType->value,
            ],
            [
                'current_count' => 0,
                'longest_count' => 0,
                'status'        => StreakStatus::Active->value,
            ],
        );

        $previousCount   = $streak->current_count;
        $completedToday  = $this->eventService->hasQualifyingEventForDate($userId, $creatorAppId, $eventType, $localDate);
        $lastCompletedOn = $streak->last_completed_date?->toDateString();

        if ($completedToday) {
            // Guard: this completion was already evaluated
            if ($lastCompletedOn === $localDate) {
                return new StreakEvaluationResult($streak, [], false);
            }

            if ($streak->current_count === 0 || $lastCompletedOn === null) {
                // New streak or resuming after break
                $streak->current_count = 1;
            } elseif ($lastCompletedOn === $yesterday) {
                // Perfect consecutive day
                $streak->current_count += 1;
            } elseif ($this->freezeCoversGap($userId, $creatorAppId, $streakType, $lastCompletedOn, $localDate)) {
                // Single missed day bridged by a freeze
                $streak->current_count += 1;
            } else {
                // Gap not covered — restart
                $streak->current_count = 1;
            }

            $streak->last_completed_date = $localDate;
            $streak->status              = StreakStatus::Active;
            $streak->longest_count       = max($streak->current_count, $streak->longest_count);
        } else {
            // No qualifying event today
            if ($streak->current_count === 0) {
                // Never started or already broken — nothing to update
                $streak->last_evaluated_date = $localDate;
                $streak->save();
                return new StreakEvaluationResult($streak, [], false);
            }

            if ($lastCompletedOn === $yesterday) {
                // Completed yesterday, today not yet done — at risk
                $streak->status = StreakStatus::AtRisk;
            } elseif ($lastCompletedOn === null || $lastCompletedOn < $yesterday) {
                // Missed a required day — broken
                $streak->current_count = 0;
                $streak->status        = StreakStatus::Broken;
            }
            // If lastCompletedOn === $localDate and completedToday === false that is
            // a data inconsistency; leave status unchanged.
        }

        $streak->last_evaluated_date = $localDate;
        $streak->save();

        $milestones = $this->checkMilestones($previousCount, $streak->current_count);

        return new StreakEvaluationResult($streak, $milestones, true);
    }

    /**
     * Returns true when a single-day gap between lastCompletedDate and localDate
     * is fully covered by an applied freeze (Phase 1: max 1 missed day bridged).
     */
    private function freezeCoversGap(
        int $userId,
        int $creatorAppId,
        StreakType $streakType,
        ?string $lastCompletedDate,
        string $localDate,
    ): bool {
        if ($lastCompletedDate === null) {
            return false;
        }

        // diffInDays === 2 means exactly 1 missed day between lastCompleted and today.
        // Cast to int because diffInDays() returns float in recent Carbon versions.
        $gap = (int) Carbon::parse($lastCompletedDate)->diffInDays(Carbon::parse($localDate));

        if ($gap !== 2) {
            return false;
        }

        $missedDate = Carbon::parse($lastCompletedDate)->addDay()->toDateString();

        return $this->freezeService->isFreezeAppliedToDate($userId, $creatorAppId, $streakType, $missedDate);
    }

    /**
     * Returns the milestone values (e.g. 7, 30) that were crossed in this evaluation.
     *
     * @return int[]
     */
    private function checkMilestones(int $previousCount, int $newCount): array
    {
        return array_values(array_filter(
            config('streaks.milestones', []),
            fn (int $m) => $previousCount < $m && $newCount >= $m,
        ));
    }
}
