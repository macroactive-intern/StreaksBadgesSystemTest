<?php

namespace App\Enums;

enum StreakType: string
{
    case WorkoutCompletion = 'workout_completion';
    case NutritionLog = 'nutrition_log';
    case HabitCompletion = 'habit_completion';
    case CommunityParticipation = 'community_participation';
}
