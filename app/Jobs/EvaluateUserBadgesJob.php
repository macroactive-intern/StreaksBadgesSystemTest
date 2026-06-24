<?php

namespace App\Jobs;

use App\Enums\StreakType;
use App\Models\AnalyticsEvent;
use App\Services\AnalyticsEventService;
use App\Services\BadgeEvaluationService;
use App\Services\NotificationTriggerService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluateUserBadgesJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param string|null $streakTypeValue  When set, only evaluates streak badges for this type+count.
     *                                      When null, runs a full badge evaluation for the user.
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $creatorAppId,
        public readonly ?string $streakTypeValue = null,
        public readonly ?int $currentStreakCount = null,
    ) {}

    public function handle(
        BadgeEvaluationService $badgeService,
        NotificationTriggerService $notificationService,
        AnalyticsEventService $analyticsService,
    ): void {
        if ($this->streakTypeValue !== null && $this->currentStreakCount !== null) {
            $awarded = $badgeService->evaluateStreakBadges(
                $this->userId,
                $this->creatorAppId,
                StreakType::from($this->streakTypeValue),
                $this->currentStreakCount,
            );
        } else {
            $awarded = $badgeService->evaluateForUser($this->userId, $this->creatorAppId);
        }

        $localDate = Carbon::now()->toDateString();

        foreach ($awarded as $userBadge) {
            $userBadge->load('badgeDefinition');

            // 10.4 — Full badge details: name, description, icon, category.
            $notificationService->badgeEarned(
                $this->userId,
                $this->creatorAppId,
                $userBadge,
                $localDate,
            );

            // 13.1 — Track badge earned.
            $analyticsService->record($this->creatorAppId, $this->userId, AnalyticsEvent::BADGE_EARNED, [
                'badge_definition_id' => $userBadge->badge_definition_id,
                'badge_slug'          => $userBadge->badgeDefinition?->slug,
            ]);
        }
    }
}
