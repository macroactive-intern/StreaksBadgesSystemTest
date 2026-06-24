<?php

use App\Enums\BadgeRuleType;
use App\Enums\StreakStatus;
use App\Enums\StreakType;
use App\Models\BadgeDefinition;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStreak;
use App\Services\UserDashboardService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
});

function dashboardMakeStreak(int $userId, int $creatorAppId, int $count = 5): UserStreak
{
    return UserStreak::create([
        'user_id'             => $userId,
        'creator_app_id'      => $creatorAppId,
        'streak_type'         => StreakType::WorkoutCompletion->value,
        'current_count'       => $count,
        'longest_count'       => $count,
        'status'              => StreakStatus::Active->value,
        'last_completed_date' => Carbon::today()->toDateString(),
    ]);
}

function dashboardMakeBadge(int $userId, int $creatorAppId, bool $hidden = false): UserBadge
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
        'privacy_hidden'      => $hidden,
    ]);
}

test('user can fetch streak summary', function () {
    dashboardMakeStreak($this->user->id, $this->creatorAppId);

    $this->getJson("/api/streaks?user_id={$this->user->id}&creator_app_id={$this->creatorAppId}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['*' => ['id', 'streak_type', 'current_count', 'longest_count', 'status']],
        ])
        ->assertJsonCount(1, 'data');
});

test('user can fetch earned badges', function () {
    dashboardMakeBadge($this->user->id, $this->creatorAppId);

    $this->getJson("/api/badges?user_id={$this->user->id}&creator_app_id={$this->creatorAppId}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['earned' => ['*' => ['user_badge_id', 'badge_id', 'name', 'earned_at']]],
        ])
        ->assertJsonCount(1, 'data.earned');
});

test('dashboard response includes next milestone', function () {
    dashboardMakeStreak($this->user->id, $this->creatorAppId, count: 5);

    $response = $this->getJson("/api/streaks?user_id={$this->user->id}&creator_app_id={$this->creatorAppId}")
        ->assertOk();

    expect($response->json('data.0.next_milestone'))->toBe(7);
});

test('dashboard response includes progress value', function () {
    dashboardMakeStreak($this->user->id, $this->creatorAppId, count: 3);

    $response = $this->getJson("/api/streaks?user_id={$this->user->id}&creator_app_id={$this->creatorAppId}")
        ->assertOk();

    expect($response->json('data.0.progress_percent'))->toBe(42);
});

test('private badges are not exposed in public profile', function () {
    dashboardMakeBadge($this->user->id, $this->creatorAppId, hidden: true);

    $dashboard = app(UserDashboardService::class);

    $ownerView = $dashboard->getBadgeDisplay($this->user->id, $this->creatorAppId);
    $profile   = $dashboard->getProfileBadges($this->user->id, $this->creatorAppId);

    expect($ownerView['earned'])->toHaveCount(1)
        ->and($profile['earned'])->toHaveCount(0)
        ->and($profile['featured'])->toBeNull();
});
