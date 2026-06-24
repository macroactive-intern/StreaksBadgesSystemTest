<?php

namespace App\Services;

use App\Data\ActivityEventMetadata;
use App\Enums\EventType;
use App\Exceptions\FutureDatedEventException;
use App\Models\ActivityEvent;
use Carbon\Carbon;

class ActivityEventService
{
    public function __construct(private AntiCheatService $antiCheat) {}

    /**
     * Record a user activity event.
     *
     * Validation order (all throw on failure):
     *  1. FutureDatedEventException   — local date is in the future
     *  2. BackfillNotAllowedException — local date is older than the backfill window
     *  3. DuplicateSourceEventException — source_id already recorded for this user
     *  4. CommunityRateLimitException — daily cap exceeded for community events
     *
     * Multiple events per day are stored; only one counts for streak progress
     * (enforced via hasQualifyingEventForDate() in the streak evaluation service).
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

        $this->antiCheat->checkBackfillWindow($localDate, $userTimezone);

        $sourceId = $this->resolveSourceId($eventType, $metadata);
        $this->antiCheat->checkDuplicateSource($userId, $creatorAppId, $eventType, $sourceId);

        if ($eventType === EventType::CommunityCommentPosted) {
            $this->antiCheat->checkCommunityRateLimit($userId, $creatorAppId, $localDate);
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
            'source_id'            => $sourceId,
        ]);
    }

    /**
     * 11.1 — Returns true if a non-revoked qualifying event exists for this date.
     * Revoked events are excluded so that deleted source content cannot credit a streak.
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
            ->whereDate('local_event_date', $localDate)
            ->whereNull('revoked_at')
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
