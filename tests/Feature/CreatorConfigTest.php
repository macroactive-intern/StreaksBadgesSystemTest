<?php

use App\Enums\BadgeRuleType;
use App\Enums\StreakType;
use App\Models\BadgeDefinition;
use App\Models\User;
use App\Services\CreatorBadgeConfigService;
use App\Services\CreatorStreakConfigService;

beforeEach(function () {
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
    $this->streakConfig = app(CreatorStreakConfigService::class);
    $this->badgeConfig  = app(CreatorBadgeConfigService::class);
});

function makeCreatorOwnedBadge(int $creatorAppId, bool $enabled = true): BadgeDefinition
{
    return BadgeDefinition::create([
        'creator_app_id' => $creatorAppId,
        'name'           => 'Creator Badge',
        'description'    => 'desc',
        'badge_category' => 'milestone',
        'icon'           => 'star',
        'rule_type'      => BadgeRuleType::Milestone->value,
        'rule_config'    => ['event_type' => 'workout_completed', 'count' => 10],
        'enabled'        => $enabled,
    ]);
}

test('creator can enable streak type', function () {
    $config = $this->streakConfig->enable($this->creatorAppId, StreakType::WorkoutCompletion);

    expect($config->enabled)->toBeTrue();
    $this->assertDatabaseHas('streak_configs', [
        'creator_app_id' => $this->creatorAppId,
        'streak_type'    => StreakType::WorkoutCompletion->value,
        'enabled'        => 1,
    ]);
});

test('creator can disable streak type', function () {
    $this->streakConfig->enable($this->creatorAppId, StreakType::WorkoutCompletion);

    $config = $this->streakConfig->disable($this->creatorAppId, StreakType::WorkoutCompletion);

    expect($config->enabled)->toBeFalse();
});

test('creator can update threshold', function () {
    $config = $this->streakConfig->upsert(
        $this->creatorAppId,
        StreakType::WorkoutCompletion,
        ['minimum_threshold' => 3],
    );

    expect($config->minimum_threshold)->toBe(3);
});

test('creator can enable badge template', function () {
    $badge   = makeCreatorOwnedBadge($this->creatorAppId, enabled: false);
    $updated = $this->badgeConfig->enable($this->creatorAppId, $badge->id);

    expect($updated->enabled)->toBeTrue();
});

test('creator can manually award badge', function () {
    $badge     = makeCreatorOwnedBadge($this->creatorAppId);
    $userBadge = $this->badgeConfig->manualAward(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        badgeDefinitionId: $badge->id,
        awardedBy: $this->creatorAppId,
    );

    expect($userBadge->user_id)->toBe($this->user->id)
        ->and($userBadge->badge_definition_id)->toBe($badge->id);
});

test('non-creator cannot manage platform badge', function () {
    $platformBadge = BadgeDefinition::create([
        'creator_app_id' => null,
        'name'           => 'Platform-Wide Badge',
        'description'    => 'Global badge',
        'badge_category' => 'milestone',
        'icon'           => 'globe',
        'rule_type'      => BadgeRuleType::Milestone->value,
        'rule_config'    => ['event_type' => 'workout_completed', 'count' => 100],
        'enabled'        => true,
    ]);

    $this->badgeConfig->disable($this->creatorAppId, $platformBadge->id);
})->throws(RuntimeException::class);
