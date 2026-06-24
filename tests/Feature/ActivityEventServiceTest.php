<?php

use App\Data\ActivityEventMetadata;
use App\Enums\EventType;
use App\Exceptions\DuplicateSourceEventException;
use App\Exceptions\FutureDatedEventException;
use App\Models\User;
use App\Services\ActivityEventService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service      = app(ActivityEventService::class);
    $this->user         = User::factory()->create();
    $this->creatorAppId = 1;
});

test('can record valid activity event', function () {
    $event = $this->service->record(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        eventType: EventType::WorkoutCompleted,
        eventTimestampUtc: Carbon::now('UTC'),
        userTimezone: 'UTC',
    );

    $this->assertDatabaseHas('activity_events', [
        'id'             => $event->id,
        'user_id'        => $this->user->id,
        'creator_app_id' => $this->creatorAppId,
        'event_type'     => 'workout_completed',
    ]);
});

test('cannot record future-dated event', function () {
    $this->service->record(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        eventType: EventType::WorkoutCompleted,
        eventTimestampUtc: Carbon::tomorrow('UTC'),
        userTimezone: 'UTC',
    );
})->throws(FutureDatedEventException::class);

test('stores user timezone', function () {
    $timezone = 'America/New_York';

    $event = $this->service->record(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        eventType: EventType::WorkoutCompleted,
        eventTimestampUtc: Carbon::now($timezone)->utc(),
        userTimezone: $timezone,
    );

    expect($event->user_timezone)->toBe($timezone);
});

test('stores local event date', function () {
    $now  = Carbon::now('UTC');
    $date = $now->toDateString();

    $event = $this->service->record(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        eventType: EventType::NutritionLogged,
        eventTimestampUtc: $now,
        userTimezone: 'UTC',
    );

    expect($event->local_event_date->toDateString())->toBe($date);
});

test('prevents duplicate streak credit', function () {
    $metadata = new ActivityEventMetadata(workoutId: 42);
    $now      = Carbon::now('UTC');

    $this->service->record(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        eventType: EventType::WorkoutCompleted,
        eventTimestampUtc: $now,
        userTimezone: 'UTC',
        metadata: $metadata,
    );

    // Same workout_id submitted again
    $this->service->record(
        userId: $this->user->id,
        creatorAppId: $this->creatorAppId,
        eventType: EventType::WorkoutCompleted,
        eventTimestampUtc: $now->addMinutes(5),
        userTimezone: 'UTC',
        metadata: $metadata,
    );
})->throws(DuplicateSourceEventException::class);
