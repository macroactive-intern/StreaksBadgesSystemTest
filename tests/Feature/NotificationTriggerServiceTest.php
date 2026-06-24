<?php

use App\Enums\BadgeRuleType;
use App\Models\BadgeDefinition;
use App\Models\NotificationTrigger;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\NotificationTriggerService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
    $this->service      = app(NotificationTriggerService::class);
    $this->today        = Carbon::today('UTC')->toDateString();
});

function makeUserBadgeWithDef(int $userId, int $creatorAppId): UserBadge
{
    $def = BadgeDefinition::create([
        'creator_app_id' => $creatorAppId,
        'name'           => 'Test Badge',
        'description'    => 'desc',
        'badge_category' => 'milestone',
        'icon'           => 'star',
        'rule_type'      => BadgeRuleType::Milestone->value,
        'rule_config'    => ['event_type' => 'workout_completed', 'count' => 1],
        'enabled'        => true,
    ]);

    return UserBadge::create([
        'user_id'             => $userId,
        'creator_app_id'      => $creatorAppId,
        'badge_definition_id' => $def->id,
        'earned_at'           => now(),
    ])->load('badgeDefinition');
}

test('creates at-risk trigger', function () {
    $trigger = $this->service->atRisk(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        streakType: 'workout_completion',
        currentCount: 5,
        timezone: 'UTC',
        localDate: $this->today,
    );

    expect($trigger)->not->toBeNull()
        ->and($trigger->trigger_type)->toBe(NotificationTriggerService::TYPE_STREAK_AT_RISK)
        ->and($trigger->payload['current_count'])->toBe(5);

    $this->assertDatabaseHas('notification_triggers', [
        'user_id'      => $this->user->id,
        'trigger_type' => NotificationTriggerService::TYPE_STREAK_AT_RISK,
    ]);
});

test('creates streak-broken trigger', function () {
    $trigger = $this->service->broken(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        streakType: 'workout_completion',
        longestCount: 10,
        previousCount: 5,
        localDate: $this->today,
    );

    expect($trigger->trigger_type)->toBe(NotificationTriggerService::TYPE_STREAK_BROKEN)
        ->and($trigger->payload['longest_count'])->toBe(10)
        ->and($trigger->payload['previous_count'])->toBe(5)
        ->and($trigger->payload['reactivation_message'])->not->toBeEmpty();
});

test('creates milestone trigger', function () {
    $trigger = $this->service->milestone(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        streakType: 'workout_completion',
        milestone: 7,
        currentCount: 7,
        localDate: $this->today,
    );

    expect($trigger->trigger_type)->toBe(NotificationTriggerService::TYPE_STREAK_MILESTONE)
        ->and($trigger->payload['milestone'])->toBe(7)
        ->and($trigger->payload['current_count'])->toBe(7);
});

test('creates badge-earned trigger', function () {
    $userBadge = makeUserBadgeWithDef($this->user->id, $this->creatorAppId);

    $trigger = $this->service->badgeEarned(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        userBadge: $userBadge,
        localDate: $this->today,
    );

    expect($trigger->trigger_type)->toBe(NotificationTriggerService::TYPE_BADGE_EARNED)
        ->and($trigger->payload['badge_id'])->toBe($userBadge->badge_definition_id)
        ->and($trigger->payload['badge_name'])->toBe('Test Badge')
        ->and($trigger->payload['earned_at'])->not->toBeEmpty();
});

test('does not create duplicate triggers for same event', function () {
    $first  = $this->service->atRisk($this->user->id, $this->creatorAppId, 'workout_completion', 5, 'UTC', $this->today);
    $second = $this->service->atRisk($this->user->id, $this->creatorAppId, 'workout_completion', 5, 'UTC', $this->today);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull();

    expect(
        NotificationTrigger::where('user_id', $this->user->id)
            ->where('trigger_type', NotificationTriggerService::TYPE_STREAK_AT_RISK)
            ->count()
    )->toBe(1);
});
