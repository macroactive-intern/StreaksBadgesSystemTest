<?php

namespace App\Data;

use App\Models\UserStreak;

readonly class StreakEvaluationResult
{
    /**
     * @param int[] $milestonesReached Milestone day values crossed during this evaluation (e.g. [7, 30])
     */
    public function __construct(
        public UserStreak $streak,
        public array $milestonesReached,
        public bool $wasUpdated,
    ) {}
}
