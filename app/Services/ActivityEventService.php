<?php

namespace App\Services;

use App\Data\ActivityEventMetadata;
use App\Enums\EventType;
use App\Exceptions\FutureDatedEventException;
use App\Models\ActivityEvent;
use Carbon\Carbon;

class ActivityEventService
{
    /**
     * Record a user activity event.
     *
     * Throws FutureDatedEventException if the event's local date is in the future.
     * All events are persisted; duplicate streak credit prevention is enforced
     * via hasQualifyingEventForDate(), which the streak evaluation service calls
     * before crediting a day.
     */
    public function record(
        int $userId,
        int $creatorAppId,
        EventType $eventType,
        Carbon $eventTimestampUtc,
        string $userTimezone,
        ?ActivityEventMetadata $metadata = null,
    ): ActivityEvent {
        $localDate  = $eventTimestampUtc->copy()->setTimezone($userTimezone)->toDateString();
        $todayLocal = Carbon::now($userTimezone)->toDateString();

        if ($localDate > $todayLocal) {
            throw new FutureDatedEventException();
        }

        return ActivityEvent::create([
            'user_id'              => $userId,
            'creator_app_id'       => $creatorAppId,
            'event_type'           => $eventType->value,
            'event_timestamp_utc'  => $eventTimestampUtc->utc(),
            'user_timezone'        => $userTimezone,
            'local_event_date'     => $localDate,
            'metadata'             => $metadata?->toArray(),
            'source_type'          => $eventType->sourceType(),
            'source_id'            => $this->resolveSourceId($eventType, $metadata),
        ]);
    }

    /**
     * Returns true if a qualifying event already exists for the given
     * user / creator app / event type / local date combination.
     * Used by the streak evaluation service to avoid duplicate streak credit.
     */
    public function hasQualifyingEventForDate(
        int $userId,
        int $creatorAppId,
        EventType $eventType,
        string $localDate,
    ): bool {
        return ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('event_type', $eventType->value)
            ->where('local_event_date', $localDate)
            ->exists();
    }

    private function resolveSourceId(EventType $eventType, ?ActivityEventMetadata $metadata): ?int
    {
        if ($metadata === null) {
            return null;
        }

        return match ($eventType) {
            EventType::WorkoutCompleted       => $metadata->workoutId,
            EventType::NutritionLogged        => $metadata->mealLogId,
            EventType::HabitCompleted         => $metadata->habitId,
            EventType::CommunityCommentPosted => $metadata->commentPostId,
            EventType::ChallengeCompleted     => $metadata->challengeId,
            EventType::ProgramCompleted       => null,
        };
    }
}
