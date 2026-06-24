<?php

namespace App\Jobs;

use App\Enums\StreakType;
use App\Services\BadgeEvaluationService;
use App\Services\NotificationTriggerService;
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

        foreach ($awarded as $userBadge) {
            $userBadge->load('badgeDefinition');

            $notificationService->create(
                $this->userId,
                $this->creatorAppId,
                NotificationTriggerService::TYPE_BADGE_EARNED,
                [
                    'badge_id'   => $userBadge->badge_definition_id,
                    'badge_name' => $userBadge->badgeDefinition?->name,
                    'earned_at'  => $userBadge->earned_at->toIso8601String(),
                ],
            );
        }
    }
}
