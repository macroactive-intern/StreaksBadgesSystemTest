<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\StreakType;
use App\Models\StreakConfig;
use Illuminate\Database\Eloquent\Collection;

class CreatorStreakConfigService
{
    /**
     * Return all streak configs for a creator app (one per enabled streak type).
     */
    public function getAll(int $creatorAppId): Collection
    {
        return StreakConfig::where('creator_app_id', $creatorAppId)
            ->orderBy('streak_type')
            ->get();
    }

    /**
     * Get a single streak config, or null if not yet configured.
     */
    public function get(int $creatorAppId, StreakType $streakType): ?StreakConfig
    {
        return StreakConfig::where('creator_app_id', $creatorAppId)
            ->where('streak_type', $streakType->value)
            ->first();
    }

    /**
     * Create or update a streak config for a creator app.
     * Only the keys present in $attributes are written; absent keys keep their
     * current DB value (or their defaults on first creation).
     *
     * Accepted attributes:
     *   enabled              bool
     *   qualifying_event_type string  (must be a valid EventType value)
     *   minimum_threshold    int
     *   reward_config        array|null
     */
    public function upsert(int $creatorAppId, StreakType $streakType, array $attributes): StreakConfig
    {
        if (isset($attributes['qualifying_event_type'])) {
            EventType::from($attributes['qualifying_event_type']); // throws on invalid value
        }

        $config = StreakConfig::firstOrNew(
            ['creator_app_id' => $creatorAppId, 'streak_type' => $streakType->value],
            [
                'qualifying_event_type' => $this->defaultEventType($streakType),
                'minimum_threshold'     => 1,
                'enabled'               => true,
            ],
        );

        foreach ($attributes as $key => $value) {
            $config->{$key} = $value;
        }

        $config->save();

        return $config->refresh();
    }

    public function enable(int $creatorAppId, StreakType $streakType): StreakConfig
    {
        return $this->upsert($creatorAppId, $streakType, ['enabled' => true]);
    }

    public function disable(int $creatorAppId, StreakType $streakType): StreakConfig
    {
        return $this->upsert($creatorAppId, $streakType, ['enabled' => false]);
    }

    public function setReward(int $creatorAppId, StreakType $streakType, array $reward): StreakConfig
    {
        return $this->upsert($creatorAppId, $streakType, ['reward_config' => $reward]);
    }

    private function defaultEventType(StreakType $streakType): string
    {
        return match ($streakType) {
            StreakType::WorkoutCompletion     => 'workout_completed',
            StreakType::NutritionLog          => 'nutrition_logged',
            StreakType::HabitCompletion       => 'habit_completed',
            StreakType::CommunityParticipation => 'community_comment_posted',
        };
    }
}
