<?php

namespace App\Enums;

enum LeaderboardType: string
{
    case WeeklyWorkout  = 'weekly_workout';
    case MonthlyStreak  = 'monthly_streak';
    case VolumeLifted   = 'volume_lifted';

    public function label(): string
    {
        return match ($this) {
            self::WeeklyWorkout => 'Weekly Workout',
            self::MonthlyStreak => 'Monthly Streak',
            self::VolumeLifted  => 'Volume Lifted',
        };
    }
}
