<?php

namespace App\Data;

readonly class ActivityEventMetadata
{
    public function __construct(
        public ?int   $workoutId = null,
        public ?int   $mealLogId = null,
        public ?int   $habitId = null,
        public ?int   $commentPostId = null,
        public ?int   $challengeId = null,
        public ?float $volumeLifted = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'workout_id'     => $this->workoutId,
            'meal_log_id'    => $this->mealLogId,
            'habit_id'       => $this->habitId,
            'comment_post_id'=> $this->commentPostId,
            'challenge_id'   => $this->challengeId,
            'volume_lifted'  => $this->volumeLifted,
        ], fn ($v) => $v !== null);
    }
}
