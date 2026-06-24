<?php

namespace App\Enums;

enum EventType: string
{
    case WorkoutCompleted = 'workout_completed';
    case NutritionLogged = 'nutrition_logged';
    case HabitCompleted = 'habit_completed';
    case CommunityCommentPosted = 'community_comment_posted';
    case ProgramCompleted = 'program_completed';
    case ChallengeCompleted = 'challenge_completed';

    public function sourceType(): string
    {
        return match ($this) {
            self::WorkoutCompleted        => 'workout',
            self::NutritionLogged         => 'nutrition',
            self::HabitCompleted          => 'habit',
            self::CommunityCommentPosted  => 'community',
            self::ProgramCompleted        => 'program',
            self::ChallengeCompleted      => 'challenge',
        };
    }

    public function streakType(): ?StreakType
    {
        return match ($this) {
            self::WorkoutCompleted       => StreakType::WorkoutCompletion,
            self::NutritionLogged        => StreakType::NutritionLog,
            self::HabitCompleted         => StreakType::HabitCompletion,
            self::CommunityCommentPosted => StreakType::CommunityParticipation,
            default                      => null,
        };
    }
}
