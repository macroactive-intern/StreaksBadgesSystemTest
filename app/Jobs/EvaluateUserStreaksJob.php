<?php

namespace App\Jobs;

use App\Enums\StreakStatus;
use App\Models\UserStreak;
use App\Services\BadgeEvaluationService;
use App\Services\NotificationTriggerService;
use App\Services\StreakEvaluationService;
use App\Services\StreakFreezeService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class EvaluateUserStreaksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $userId,
        public readonly int $creatorAppId,
        public readonly string $localDate,
    ) {}

    public function handle(
        StreakEvaluationService $streakService,
        BadgeEvaluationService $badgeService,
        StreakFreezeService $freezeService,
        NotificationTriggerService $notificationService,
    ): void {
        $this->autoApplyFreezes($freezeService);

        $results = $streakService->evaluateAllForUser($this->userId, $this->creatorAppId, $this->localDate);

        foreach ($results as $result) {
            if (!$result->wasUpdated) {
                continue;
            }

            $streak = $result->streak;

            if (!empty($result->milestonesReached)) {
                EvaluateUserBadgesJob::dispatch(
                    $this->userId,
                    $this->creatorAppId,
                    $streak->streak_type->value,
                    $streak->current_count,
                );

                foreach ($result->milestonesReached as $milestone) {
                    $notificationService->create(
                        $this->userId,
                        $this->creatorAppId,
                        NotificationTriggerService::TYPE_STREAK_MILESTONE,
                        [
                            'streak_type'   => $streak->streak_type->value,
                            'milestone'     => $milestone,
                            'current_count' => $streak->current_count,
                        ],
                    );
                }
            }

            if ($streak->status === StreakStatus::AtRisk) {
                if (!$notificationService->hasPendingTodayOf(
                    $this->userId, $this->creatorAppId,
                    NotificationTriggerService::TYPE_STREAK_AT_RISK,
                    $this->localDate,
                )) {
                    $notificationService->create(
                        $this->userId,
                        $this->creatorAppId,
                        NotificationTriggerService::TYPE_STREAK_AT_RISK,
                        [
                            'streak_type'   => $streak->streak_type->value,
                            'current_count' => $streak->current_count,
                        ],
                    );
                }
            }

            if ($streak->status === StreakStatus::Broken) {
                $notificationService->create(
                    $this->userId,
                    $this->creatorAppId,
                    NotificationTriggerService::TYPE_STREAK_BROKEN,
                    [
                        'streak_type'  => $streak->streak_type->value,
                        'longest_count' => $streak->longest_count,
                    ],
                );
            }
        }
    }

    /**
     * For any streak where exactly one day was missed and the user holds an unused freeze,
     * automatically apply the freeze to that missed day before evaluation runs.
     * Swallows exceptions — failure to auto-apply should not abort evaluation.
     */
    private function autoApplyFreezes(StreakFreezeService $freezeService): void
    {
        $yesterday = Carbon::parse($this->localDate)->subDay()->toDateString();

        $candidates = UserStreak::where('user_id', $this->userId)
            ->where('creator_app_id', $this->creatorAppId)
            ->whereIn('status', [StreakStatus::Active->value, StreakStatus::AtRisk->value])
            ->where('current_count', '>', 0)
            ->get();

        foreach ($candidates as $streak) {
            $lastCompleted = $streak->last_completed_date?->toDateString();

            if ($lastCompleted === null || $lastCompleted >= $yesterday) {
                continue;
            }

            $gap = Carbon::parse($lastCompleted)->diffInDays(Carbon::parse($this->localDate));

            if ($gap !== 2) {
                continue;
            }

            try {
                $freezeService->apply(
                    $this->userId,
                    $this->creatorAppId,
                    $streak->streak_type,
                    $yesterday,
                );
            } catch (Throwable) {
                // No freeze available, cooldown active, or already applied — skip.
            }
        }
    }
}
