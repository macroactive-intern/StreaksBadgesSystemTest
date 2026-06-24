<?php

namespace App\Http\Controllers\Api;

use App\Data\ActivityEventMetadata;
use App\Enums\EventType;
use App\Exceptions\FutureDatedEventException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordEventRequest;
use App\Http\Resources\EarnedBadgeResource;
use App\Services\ActivityEventService;
use App\Services\BadgeEvaluationService;
use App\Services\StreakEvaluationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function __construct(
        private ActivityEventService $eventService,
        private StreakEvaluationService $streakService,
        private BadgeEvaluationService $badgeService,
    ) {}

    /**
     * 8.3 — POST /api/events
     * Records an activity event, then triggers streak and badge evaluation.
     */
    public function store(RecordEventRequest $request): JsonResponse
    {
        $userId       = (int) $request->user_id;
        $creatorAppId = (int) $request->creator_app_id;
        $eventType    = EventType::from($request->event_type);
        $timestamp    = Carbon::parse($request->event_timestamp_utc, 'UTC');

        $metadata = null;
        if ($request->has('metadata')) {
            $m        = $request->metadata;
            $metadata = new ActivityEventMetadata(
                workoutId:     $m['workout_id'] ?? null,
                mealLogId:     $m['meal_log_id'] ?? null,
                habitId:       $m['habit_id'] ?? null,
                commentPostId: $m['comment_post_id'] ?? null,
                challengeId:   $m['challenge_id'] ?? null,
                volumeLifted:  isset($m['volume_lifted']) ? (float) $m['volume_lifted'] : null,
            );
        }

        try {
            $event = $this->eventService->record(
                $userId, $creatorAppId, $eventType, $timestamp, $request->user_timezone, $metadata
            );
        } catch (FutureDatedEventException) {
            return response()->json(['message' => 'Event date cannot be in the future.'], 422);
        }

        $localDate = $timestamp->copy()->setTimezone($request->user_timezone)->toDateString();

        $streakResults = $this->streakService->evaluateAllForUser($userId, $creatorAppId, $localDate);

        $newBadges = [];
        foreach ($streakResults as $result) {
            if (!empty($result->milestonesReached)) {
                $newBadges = array_merge(
                    $newBadges,
                    $this->badgeService->evaluateStreakBadges(
                        $userId, $creatorAppId, $result->streak->streak_type, $result->streak->current_count
                    )
                );
            }
        }
        $newBadges = array_merge(
            $newBadges,
            $this->badgeService->evaluateEventBadges($userId, $creatorAppId, $eventType)
        );

        return response()->json([
            'data' => [
                'event_id'   => $event->id,
                'local_date' => $localDate,
                'new_badges' => EarnedBadgeResource::collection($newBadges),
            ],
        ], 201);
    }
}
