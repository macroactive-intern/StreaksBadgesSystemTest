<?php

namespace App\Services;

use App\Enums\BadgeRuleType;
use App\Enums\EventType;
use App\Enums\StreakType;
use App\Exceptions\BadgeAlreadyAwardedException;
use App\Models\ActivityEvent;
use App\Models\BadgeDefinition;
use App\Models\UserBadge;
use App\Models\UserStreak;
use RuntimeException;

class BadgeEvaluationService
{
    /**
     * Evaluate all enabled badge definitions for a user and award any newly earned badges.
     *
     * @return UserBadge[]
     */
    public function evaluateForUser(int $userId, int $creatorAppId): array
    {
        $awarded = [];

        foreach ($this->loadDefinitions($creatorAppId) as $badge) {
            if ($this->isAlreadyAwarded($userId, $badge->id)) {
                continue;
            }

            if ($this->userMeetsCriteria($userId, $creatorAppId, $badge)) {
                $userBadge = $this->awardBadge($userId, $creatorAppId, $badge);
                if ($userBadge) {
                    $awarded[] = $userBadge;
                }
            }
        }

        return $awarded;
    }

    /**
     * Evaluate only streak-type badges for a specific streak type and current count.
     * Called by the streak evaluation pipeline when a milestone is crossed.
     *
     * @return UserBadge[]
     */
    public function evaluateStreakBadges(
        int $userId,
        int $creatorAppId,
        StreakType $streakType,
        int $currentCount,
    ): array {
        $awarded = [];

        $definitions = BadgeDefinition::where('enabled', true)
            ->where('rule_type', BadgeRuleType::Streak->value)
            ->where(fn ($q) => $q->where('creator_app_id', $creatorAppId)->orWhereNull('creator_app_id'))
            ->get()
            ->filter(fn ($b) => ($b->rule_config['streak_type'] ?? null) === $streakType->value);

        foreach ($definitions as $badge) {
            if ($currentCount < ($badge->rule_config['min_streak_days'] ?? PHP_INT_MAX)) {
                continue;
            }

            $userBadge = $this->awardBadge($userId, $creatorAppId, $badge);
            if ($userBadge) {
                $awarded[] = $userBadge;
            }
        }

        return $awarded;
    }

    /**
     * Evaluate event-count and metric badges triggered by a specific event type.
     * Called after an activity event is recorded.
     *
     * @return UserBadge[]
     */
    public function evaluateEventBadges(int $userId, int $creatorAppId, EventType $eventType): array
    {
        $awarded = [];

        $definitions = BadgeDefinition::where('enabled', true)
            ->whereIn('rule_type', [
                BadgeRuleType::Milestone->value,
                BadgeRuleType::Challenge->value,
                BadgeRuleType::Certification->value,
                BadgeRuleType::Community->value,
            ])
            ->where(fn ($q) => $q->where('creator_app_id', $creatorAppId)->orWhereNull('creator_app_id'))
            ->get()
            ->filter(fn ($b) => ($b->rule_config['event_type'] ?? null) === $eventType->value);

        foreach ($definitions as $badge) {
            if ($this->isAlreadyAwarded($userId, $badge->id)) {
                continue;
            }

            if ($this->checkCountRule($userId, $creatorAppId, $badge->rule_config)) {
                $userBadge = $this->awardBadge($userId, $creatorAppId, $badge);
                if ($userBadge) {
                    $awarded[] = $userBadge;
                }
            }
        }

        return $awarded;
    }

    /**
     * Manually award a badge on behalf of a creator or admin.
     * Throws BadgeAlreadyAwardedException if a row already exists (even if revoked).
     */
    public function manualAward(
        int $userId,
        int $creatorAppId,
        int $badgeDefinitionId,
        int $awardedBy,
    ): UserBadge {
        $badge = BadgeDefinition::findOrFail($badgeDefinitionId);

        if ($this->isAlreadyAwarded($userId, $badgeDefinitionId)) {
            throw new BadgeAlreadyAwardedException();
        }

        return UserBadge::create([
            'user_id'             => $userId,
            'creator_app_id'      => $creatorAppId,
            'badge_definition_id' => $badge->id,
            'earned_at'           => now(),
            'awarded_by'          => $awardedBy,
        ]);
    }

    /**
     * Revoke a badge award, storing the reason for audit purposes.
     */
    public function revoke(int $userBadgeId, string $reason): UserBadge
    {
        $userBadge = UserBadge::findOrFail($userBadgeId);

        if ($userBadge->revoked_at !== null) {
            throw new RuntimeException('This badge award has already been revoked.');
        }

        $userBadge->update([
            'revoked_at'    => now(),
            'revoke_reason' => $reason,
        ]);

        return $userBadge->refresh();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function loadDefinitions(int $creatorAppId)
    {
        return BadgeDefinition::where('enabled', true)
            ->where(fn ($q) => $q->where('creator_app_id', $creatorAppId)->orWhereNull('creator_app_id'))
            ->get();
    }

    private function isAlreadyAwarded(int $userId, int $badgeDefinitionId): bool
    {
        return UserBadge::where('user_id', $userId)
            ->where('badge_definition_id', $badgeDefinitionId)
            ->exists();
    }

    private function awardBadge(
        int $userId,
        int $creatorAppId,
        BadgeDefinition $badge,
        ?int $awardedBy = null,
    ): ?UserBadge {
        if ($this->isAlreadyAwarded($userId, $badge->id)) {
            return null;
        }

        return UserBadge::create([
            'user_id'             => $userId,
            'creator_app_id'      => $creatorAppId,
            'badge_definition_id' => $badge->id,
            'earned_at'           => now(),
            'awarded_by'          => $awardedBy,
        ]);
    }

    private function userMeetsCriteria(int $userId, int $creatorAppId, BadgeDefinition $badge): bool
    {
        return match ($badge->rule_type) {
            BadgeRuleType::Streak                                      => $this->checkStreakRule($userId, $creatorAppId, $badge->rule_config),
            BadgeRuleType::Milestone,
            BadgeRuleType::Challenge,
            BadgeRuleType::Certification,
            BadgeRuleType::Community                                   => $this->checkCountRule($userId, $creatorAppId, $badge->rule_config),
        };
    }

    /**
     * Checks whether the user currently has a long enough active streak.
     * Once the badge is awarded it stays regardless of future streak breaks,
     * because isAlreadyAwarded() short-circuits subsequent evaluations.
     */
    private function checkStreakRule(int $userId, int $creatorAppId, array $config): bool
    {
        $streak = UserStreak::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('streak_type', $config['streak_type'])
            ->first();

        return $streak !== null && $streak->current_count >= ($config['min_streak_days'] ?? PHP_INT_MAX);
    }

    /**
     * Checks event count or aggregated metric against the badge threshold.
     * rule_config variants:
     *   count-based : { "event_type": "workout_completed", "count": 100 }
     *   metric-based: { "event_type": "workout_completed", "metric": "volume_lifted", "min_total": 100000 }
     */
    private function checkCountRule(int $userId, int $creatorAppId, array $config): bool
    {
        $query = ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('event_type', $config['event_type']);

        if (isset($config['metric'], $config['min_total'])) {
            $total = $query->get()->sum(fn ($e) => data_get($e->metadata, $config['metric'], 0));
            return $total >= $config['min_total'];
        }

        return $query->count() >= ($config['count'] ?? PHP_INT_MAX);
    }
}
