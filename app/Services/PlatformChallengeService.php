<?php

namespace App\Services;

use App\Models\PlatformChallenge;
use App\Models\PlatformChallengeEntry;
use App\Models\UserPreference;
use Illuminate\Support\Collection;

class PlatformChallengeService
{
    /**
     * 15.2 — All currently active platform-wide challenges.
     */
    public function getActive(): Collection
    {
        return PlatformChallenge::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at')
            ->get();
    }

    /**
     * 15.2 — Ranked leaderboard for a platform challenge (opt-in only).
     *
     * @return Collection<int, array{rank: int, user_id: int, creator_app_id: int, nickname: string|null, score: int}>
     */
    public function getLeaderboard(PlatformChallenge $challenge): Collection
    {
        $entries = PlatformChallengeEntry::where('platform_challenge_id', $challenge->id)
            ->orderByDesc('score')
            ->get();

        if ($entries->isEmpty()) {
            return collect();
        }

        // Collect all (creator_app_id, user_id) pairs and look up prefs.
        $prefsByUser = collect();

        $entries->groupBy('creator_app_id')->each(function ($group, $creatorAppId) use (&$prefsByUser) {
            $userIds = $group->pluck('user_id');

            UserPreference::where('creator_app_id', $creatorAppId)
                ->whereIn('user_id', $userIds)
                ->get()
                ->each(fn ($p) => $prefsByUser->put($p->user_id, $p));
        });

        $rank = 0;

        return $entries
            ->filter(function ($entry) use ($prefsByUser) {
                $pref = $prefsByUser->get($entry->user_id);
                return $pref === null || $pref->leaderboard_visible;
            })
            ->values()
            ->map(function ($entry) use (&$rank, $prefsByUser) {
                $pref = $prefsByUser->get($entry->user_id);
                return [
                    'rank'           => ++$rank,
                    'user_id'        => $entry->user_id,
                    'creator_app_id' => $entry->creator_app_id,
                    'nickname'       => $pref?->leaderboard_nickname,
                    'score'          => (int) $entry->score,
                ];
            });
    }

    /**
     * 15.2 — Upsert a user's score for a platform challenge.
     */
    public function recordEntry(int $userId, int $creatorAppId, int $platformChallengeId, int $score): PlatformChallengeEntry
    {
        return PlatformChallengeEntry::updateOrCreate(
            ['platform_challenge_id' => $platformChallengeId, 'user_id' => $userId],
            ['creator_app_id' => $creatorAppId, 'score' => $score],
        );
    }
}
