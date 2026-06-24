<?php

use App\Enums\EventType;
use App\Enums\StreakStatus;
use App\Enums\StreakType;
use App\Models\ActivityEvent;
use App\Models\StreakConfig;
use App\Models\User;
use App\Services\StreakEvaluationService;
use Carbon\Carbon;

// Fixed historical dates — deterministic regardless of system clock or timezone.
const STREAK_D1 = '2026-01-01';
const STREAK_D2 = '2026-01-02';
const STREAK_D3 = '2026-01-03';
const STREAK_D4 = '2026-01-04';

beforeEach(function () {
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
    $this->service      = app(StreakEvaluationService::class);

    StreakConfig::create([
        'creator_app_id'        => $this->creatorAppId,
        'streak_type'           => StreakType::WorkoutCompletion->value,
        'enabled'               => true,
        'qualifying_event_type' => EventType::WorkoutCompleted->value,
        'minimum_threshold'     => 1,
    ]);
});

function streakSeedEvent(int $userId, int $creatorAppId, string $date, ?int $sourceId = null): void
{
    ActivityEvent::create([
        'user_id'             => $userId,
        'creator_app_id'      => $creatorAppId,
        'event_type'          => EventType::WorkoutCompleted->value,
        'event_timestamp_utc' => Carbon::parse($date . ' 12:00:00', 'UTC'),
        'user_timezone'       => 'UTC',
        'local_event_date'    => $date,
        'source_type'         => 'workout',
        'source_id'           => $sourceId,
    ]);
}

function streakEvaluate(StreakEvaluationService $service, int $userId, int $creatorAppId, string $date)
{
    return $service->evaluateStreak($userId, $creatorAppId, StreakType::WorkoutCompletion, $date);
}

test('starts streak after first qualifying event', function () {
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D1);

    $result = streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D1);

    expect($result->streak->current_count)->toBe(1)
        ->and($result->streak->status)->toBe(StreakStatus::Active);
});

test('increments streak after consecutive days', function () {
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D1, sourceId: 1);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D1);

    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D2, sourceId: 2);
    $result = streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D2);

    expect($result->streak->current_count)->toBe(2)
        ->and($result->streak->status)->toBe(StreakStatus::Active);
});

test('resets streak after missed day', function () {
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D1, sourceId: 1);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D1);

    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D2, sourceId: 2);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D2);

    // D3 skipped — gap resets streak
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D4, sourceId: 3);
    $result = streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D4);

    expect($result->streak->current_count)->toBe(1);
});

test('updates longest streak', function () {
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D1, sourceId: 1);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D1);

    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D2, sourceId: 2);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D2);

    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D3, sourceId: 3);
    $result = streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D3);

    expect($result->streak->current_count)->toBe(3)
        ->and($result->streak->longest_count)->toBe(3);
});

test('marks streak as at risk', function () {
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D1);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D1);

    // D2 has no event — streak should be at risk
    $result = streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D2);

    expect($result->streak->status)->toBe(StreakStatus::AtRisk)
        ->and($result->streak->current_count)->toBe(1);
});

test('marks streak as broken', function () {
    streakSeedEvent($this->user->id, $this->creatorAppId, STREAK_D1);
    streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D1);

    // D2 missed — evaluate D3 with no event
    $result = streakEvaluate($this->service, $this->user->id, $this->creatorAppId, STREAK_D3);

    expect($result->streak->status)->toBe(StreakStatus::Broken)
        ->and($result->streak->current_count)->toBe(0);
});

test('handles user timezone correctly', function () {
    // 20:00 UTC on Dec 31 is 05:00 Jan 1 in Tokyo (UTC+9)
    $timezone       = 'Asia/Tokyo';
    $utcMoment      = Carbon::parse('2025-12-31 20:00:00', 'UTC');
    $tokyoLocalDate = $utcMoment->copy()->setTimezone($timezone)->toDateString(); // 2026-01-01

    ActivityEvent::create([
        'user_id'             => $this->user->id,
        'creator_app_id'      => $this->creatorAppId,
        'event_type'          => EventType::WorkoutCompleted->value,
        'event_timestamp_utc' => $utcMoment,
        'user_timezone'       => $timezone,
        'local_event_date'    => $tokyoLocalDate,
        'source_type'         => 'workout',
    ]);

    $result = $this->service->evaluateStreak(
        $this->user->id,
        $this->creatorAppId,
        StreakType::WorkoutCompletion,
        $tokyoLocalDate,
    );

    expect($result)->not->toBeNull()
        ->and($result->streak->current_count)->toBe(1)
        ->and($result->streak->status)->toBe(StreakStatus::Active);
});
