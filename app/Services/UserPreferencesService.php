<?php

namespace App\Services;

use App\Models\UserPreference;

class UserPreferencesService
{
    /**
     * 15.1 — Retrieve preferences for a user in a creator app, creating defaults if absent.
     */
    public function get(int $userId, int $creatorAppId): UserPreference
    {
        return UserPreference::firstOrCreate(
            ['user_id' => $userId, 'creator_app_id' => $creatorAppId],
            ['leaderboard_nickname' => null, 'leaderboard_visible' => true],
        );
    }

    /**
     * 15.1 — Update nickname and/or visibility in one call.
     */
    public function update(int $userId, int $creatorAppId, array $data): UserPreference
    {
        $pref = $this->get($userId, $creatorAppId);

        $allowed = array_intersect_key($data, array_flip(['leaderboard_nickname', 'leaderboard_visible']));

        $pref->fill($allowed)->save();

        return $pref;
    }
}
