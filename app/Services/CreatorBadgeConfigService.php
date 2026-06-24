<?php

namespace App\Services;

use App\Models\BadgeDefinition;
use App\Models\UserBadge;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class CreatorBadgeConfigService
{
    public function __construct(private BadgeEvaluationService $badgeService) {}

    /**
     * Return all badge definitions visible to a creator app:
     * platform defaults (creator_app_id = null) plus their own.
     */
    public function getDefinitions(int $creatorAppId): Collection
    {
        return BadgeDefinition::where(fn ($q) => $q
            ->whereNull('creator_app_id')
            ->orWhere('creator_app_id', $creatorAppId)
        )
            ->orderBy('badge_category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a creator-owned badge definition.
     * Required keys: name, description, badge_category, icon, rule_type, rule_config.
     */
    public function createBadge(int $creatorAppId, array $data): BadgeDefinition
    {
        return BadgeDefinition::create(array_merge($data, [
            'creator_app_id' => $creatorAppId,
            'enabled'        => $data['enabled'] ?? true,
        ]));
    }

    /**
     * Enable a creator-owned badge definition.
     * Platform badges (creator_app_id = null) cannot be toggled by creators.
     */
    public function enable(int $creatorAppId, int $badgeDefinitionId): BadgeDefinition
    {
        return $this->toggle($creatorAppId, $badgeDefinitionId, true);
    }

    /**
     * Disable a creator-owned badge definition.
     */
    public function disable(int $creatorAppId, int $badgeDefinitionId): BadgeDefinition
    {
        return $this->toggle($creatorAppId, $badgeDefinitionId, false);
    }

    /**
     * Attach or replace a reward inside the badge's rule_config.
     * Example reward: ['type' => 'points', 'amount' => 100]
     */
    public function attachReward(int $creatorAppId, int $badgeDefinitionId, array $reward): BadgeDefinition
    {
        $badge = BadgeDefinition::findOrFail($badgeDefinitionId);
        $this->assertCreatorOwns($creatorAppId, $badge);

        $config           = $badge->rule_config;
        $config['reward'] = $reward;

        $badge->update(['rule_config' => $config]);

        return $badge->refresh();
    }

    /**
     * Manually award a badge to a user on behalf of a creator.
     */
    public function manualAward(
        int $userId,
        int $creatorAppId,
        int $badgeDefinitionId,
        int $awardedBy,
    ): UserBadge {
        return $this->badgeService->manualAward($userId, $creatorAppId, $badgeDefinitionId, $awardedBy);
    }

    /**
     * Revoke a previously awarded badge.
     */
    public function revoke(int $userBadgeId, string $reason): UserBadge
    {
        return $this->badgeService->revoke($userBadgeId, $reason);
    }

    // -------------------------------------------------------------------------

    private function toggle(int $creatorAppId, int $badgeDefinitionId, bool $enabled): BadgeDefinition
    {
        $badge = BadgeDefinition::findOrFail($badgeDefinitionId);
        $this->assertCreatorOwns($creatorAppId, $badge);

        $badge->update(['enabled' => $enabled]);

        return $badge->refresh();
    }

    private function assertCreatorOwns(int $creatorAppId, BadgeDefinition $badge): void
    {
        if ($badge->creator_app_id !== $creatorAppId) {
            throw new RuntimeException(
                'Platform badge definitions cannot be modified by creators. Create a creator-specific badge instead.'
            );
        }
    }
}
