<?php

use App\Enums\BadgeRuleType;
use App\Enums\EventType;
use App\Enums\StreakStatus;
use App\Enums\StreakType;
use App\Models\ActivityEvent;
use App\Models\BadgeDefinition;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStreak;
use App\Services\BadgeEvaluationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
    $this->service      = app(BadgeEvaluationService::class);
});

function makeStreakBadge(int $creatorAppId, int $minDays, StreakType $type = StreakType::WorkoutCompletion): BadgeDefinition
{
    return BadgeDefinition::create([
        'creator_app_id' => $creatorAppId,
        'name'           => "{$minDays}-Day Streak",
        'description'    => "Earned at {$minDays} days",
        'badge_category' => 'consistency',
        'icon'           => 'fire',
        'rule_type'      => BadgeRuleType::Streak->value,
        'rule_config'    => ['streak_type' => $type->value, 'min_streak_days' => $minDays],
        'enabled'        => true,
    ]);
}

function makeStreak(int $userId, int $creatorAppId, int $count, StreakType $type = StreakType::WorkoutCompletion): UserStreak
{
    return UserStreak::create([
        'user_id'        => $userId,
        'creator_app_id' => $creatorAppId,
        'streak_type'    => $type->value,
        'current_count'  => $count,
        'longest_count'  => $count,
        'status'         => StreakStatus::Active->value,
    ]);
}

function makeCountBadge(int $creatorAppId, EventType $eventType, int $count, BadgeRuleType $ruleType = BadgeRuleType::Milestone): BadgeDefinition
{
    return BadgeDefinition::create([
        'creator_app_id' => $creatorAppId,
        'name'           => "{$count}x {$eventType->value}",
        'description'    => "After {$count} events",
        'badge_category' => 'milestone',
        'icon'           => 'star',
        'rule_type'      => $ruleType->value,
        'rule_config'    => ['event_type' => $eventType->value, 'count' => $count],
        'enabled'        => true,
    ]);
}

function seedBadgeEvents(int $userId, int $creatorAppId, EventType $eventType, int $count): void
{
    for ($i = 0; $i < $count; $i++) {
        ActivityEvent::create([
            'user_id'             => $userId,
            'creator_app_id'      => $creatorAppId,
            'event_type'          => $eventType->value,
            'event_timestamp_utc' => Carbon::now(),
            'user_timezone'       => 'UTC',
            'local_event_date'    => Carbon::today()->subDays($i)->toDateString(),
            'source_type'         => $eventType->sourceType(),
            'source_id'           => $i + 1,
        ]);
    }
}

test('awards 7-day badge', function () {
    $badge = makeStreakBadge($this->creatorAppId, 7);
    makeStreak($this->user->id, $this->creatorAppId, 7);

    $awarded = $this->service->evaluateForUser($this->user->id, $this->creatorAppId);

    expect($awarded)->toHaveCount(1)
        ->and($awarded[0]->badge_definition_id)->toBe($badge->id);
});

test('awards 30-day badge', function () {
    $badge = makeStreakBadge($this->creatorAppId, 30);
    makeStreak($this->user->id, $this->creatorAppId, 30);

    $awarded = $this->service->evaluateForUser($this->user->id, $this->creatorAppId);

    expect($awarded)->toHaveCount(1)
        ->and($awarded[0]->badge_definition_id)->toBe($badge->id);
});

test('awards milestone badge', function () {
    $badge = makeCountBadge($this->creatorAppId, EventType::WorkoutCompleted, 5);
    seedBadgeEvents($this->user->id, $this->creatorAppId, EventType::WorkoutCompleted, 5);

    $awarded = $this->service->evaluateForUser($this->user->id, $this->creatorAppId);

    expect($awarded)->toHaveCount(1)
        ->and($awarded[0]->badge_definition_id)->toBe($badge->id);
});

test('awards program completion badge', function () {
    $badge = makeCountBadge($this->creatorAppId, EventType::ProgramCompleted, 1, BadgeRuleType::Challenge);
    seedBadgeEvents($this->user->id, $this->creatorAppId, EventType::ProgramCompleted, 1);

    $awarded = $this->service->evaluateForUser($this->user->id, $this->creatorAppId);

    expect($awarded)->toHaveCount(1)
        ->and($awarded[0]->badge_definition_id)->toBe($badge->id);
});

test('does not award same badge twice', function () {
    makeStreakBadge($this->creatorAppId, 7);
    makeStreak($this->user->id, $this->creatorAppId, 7);

    $this->service->evaluateForUser($this->user->id, $this->creatorAppId);
    $this->service->evaluateForUser($this->user->id, $this->creatorAppId);

    expect(UserBadge::where('user_id', $this->user->id)->count())->toBe(1);
});

test('keeps badge after streak breaks', function () {
    $badge  = makeStreakBadge($this->creatorAppId, 7);
    $streak = makeStreak($this->user->id, $this->creatorAppId, 7);

    $this->service->evaluateForUser($this->user->id, $this->creatorAppId);
    $streak->update(['current_count' => 0, 'status' => StreakStatus::Broken->value]);
    $this->service->evaluateForUser($this->user->id, $this->creatorAppId);

    expect(
        UserBadge::where('user_id', $this->user->id)
            ->where('badge_definition_id', $badge->id)
            ->whereNull('revoked_at')
            ->count()
    )->toBe(1);
});

test('allows manual award', function () {
    $badge     = makeStreakBadge($this->creatorAppId, 7);
    $userBadge = $this->service->manualAward(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        badgeDefinitionId: $badge->id,
        awardedBy: 999,
    );

    expect($userBadge->awarded_by)->toBe(999);
    $this->assertDatabaseHas('user_badges', [
        'user_id'             => $this->user->id,
        'badge_definition_id' => $badge->id,
        'awarded_by'          => 999,
    ]);
});

test('allows manual revoke', function () {
    $badge     = makeStreakBadge($this->creatorAppId, 7);
    $userBadge = UserBadge::create([
        'user_id'             => $this->user->id,
        'creator_app_id'      => $this->creatorAppId,
        'badge_definition_id' => $badge->id,
        'earned_at'           => now(),
    ]);

    $revoked = $this->service->revoke($userBadge->id, 'Policy violation');

    expect($revoked->revoked_at)->not->toBeNull()
        ->and($revoked->revoke_reason)->toBe('Policy violation');
});
