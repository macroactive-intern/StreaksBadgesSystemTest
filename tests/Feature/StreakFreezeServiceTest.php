<?php

use App\Enums\EventType;
use App\Enums\StreakStatus;
use App\Enums\StreakType;
use App\Exceptions\FreezeCooldownException;
use App\Exceptions\NoAvailableFreezeException;
use App\Models\ActivityEvent;
use App\Models\StreakConfig;
use App\Models\User;
use App\Services\StreakEvaluationService;
use App\Services\StreakFreezeService;
use Carbon\Carbon;

beforeEach(function () {
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
    $this->service      = app(StreakFreezeService::class);
});

test('user can use available freeze', function () {
    $this->service->grant($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion);

    $freeze = $this->service->apply(
        $this->user->id,
        $this->creatorAppId,
        StreakType::WorkoutCompletion,
        '2026-01-02',
    );

    expect($freeze->used_at)->not->toBeNull()
        ->and($freeze->applied_to_date->toDateString())->toBe('2026-01-02');
});

test('freeze preserves streak', function () {
    StreakConfig::create([
        'creator_app_id'        => $this->creatorAppId,
        'streak_type'           => StreakType::WorkoutCompletion->value,
        'enabled'               => true,
        'qualifying_event_type' => EventType::WorkoutCompleted->value,
        'minimum_threshold'     => 1,
    ]);

    [$d1, $d2, $d3] = ['2026-01-01', '2026-01-02', '2026-01-03'];

    ActivityEvent::create([
        'user_id'             => $this->user->id,
        'creator_app_id'      => $this->creatorAppId,
        'event_type'          => EventType::WorkoutCompleted->value,
        'event_timestamp_utc' => Carbon::parse($d1 . ' 12:00:00', 'UTC'),
        'user_timezone'       => 'UTC',
        'local_event_date'    => $d1,
        'source_type'         => 'workout',
    ]);

    $evaluator = app(StreakEvaluationService::class);
    $evaluator->evaluateStreak($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion, $d1);

    $this->service->grant($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion);
    $this->service->apply($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion, $d2);

    ActivityEvent::create([
        'user_id'             => $this->user->id,
        'creator_app_id'      => $this->creatorAppId,
        'event_type'          => EventType::WorkoutCompleted->value,
        'event_timestamp_utc' => Carbon::parse($d3 . ' 12:00:00', 'UTC'),
        'user_timezone'       => 'UTC',
        'local_event_date'    => $d3,
        'source_type'         => 'workout',
        'source_id'           => 99,
    ]);

    $result = $evaluator->evaluateStreak($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion, $d3);

    expect($result->streak->current_count)->toBe(2)
        ->and($result->streak->status)->toBe(StreakStatus::Active);
});

test('freeze records applied date', function () {
    $this->service->grant($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion);

    $freeze = $this->service->apply(
        $this->user->id,
        $this->creatorAppId,
        StreakType::WorkoutCompletion,
        '2026-01-02',
    );

    expect($freeze->applied_to_date->toDateString())->toBe('2026-01-02')
        ->and($freeze->used_at)->not->toBeNull();
});

test('user cannot apply freeze when none available', function () {
    $this->service->apply(
        $this->user->id,
        $this->creatorAppId,
        StreakType::WorkoutCompletion,
        '2026-01-02',
    );
})->throws(NoAvailableFreezeException::class);

test('freeze cannot be used within cooldown period', function () {
    $this->service->grant($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion);
    $this->service->apply($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion, '2026-01-02');

    $this->service->grant($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion);
    $this->service->apply($this->user->id, $this->creatorAppId, StreakType::WorkoutCompletion, '2026-01-03');
})->throws(FreezeCooldownException::class);
