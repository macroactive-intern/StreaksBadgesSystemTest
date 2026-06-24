<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\LeaderboardType;
use App\Enums\StreakStatus;
use App\Models\ActivityEvent;
use App\Models\Challenge;
use App\Models\ChallengeEntry;
use App\Models\LeaderboardSnapshot;
use App\Models\UserPreference;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * 15.1 — Weekly workout leaderboard.
     * Counts habit_completed events in the last 7 days, opt-in users only.
     *
     * @return Collection<int, array{rank: int, user_id: int, nickname: string|null, score: int}>
     */
    public function weeklyWorkout(int $creatorAppId, ?string $date = null): Collection
    {
        $since = Carbon::parse($date ?? now())->subDays(6)->toDateString();
        $until = Carbon::parse($date ?? now())->toDateString();

        $rows = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::HabitCompleted->value)
            ->whereBetween('local_event_date', [$since, $until])
            ->whereNull('revoked_at')
            ->selectRaw('user_id, COUNT(*) as score')
            ->groupBy('user_id')
            ->orderByDesc('score')
            ->get();

        return $this->attachNicknamesAndRank($creatorAppId, $rows);
    }

    /**
     * 15.1 — Monthly streak leaderboard.
     * Ranks users by their highest current active streak count.
     *
     * @return Collection<int, array{rank: int, user_id: int, nickname: string|null, score: int}>
     */
    public function monthlyStreak(int $creatorAppId): Collection
    {
        $rows = UserStreak::where('creator_app_id', $creatorAppId)
            ->whereIn('status', [StreakStatus::Active->value, StreakStatus::AtRisk->value])
            ->selectRaw('user_id, MAX(current_count) as score')
            ->groupBy('user_id')
            ->orderByDesc('score')
            ->get();

        return $this->attachNicknamesAndRank($creatorAppId, $rows);
    }

    /**
     * 15.1 — Volume lifted leaderboard.
     * Sums metadata->volume_kg from workout_completed events in the last 30 days.
     *
     * @return Collection<int, array{rank: int, user_id: int, nickname: string|null, score: int}>
     */
    public function volumeLifted(int $creatorAppId, ?string $date = null): Collection
    {
        $since = Carbon::parse($date ?? now())->subDays(29)->toDateString();
        $until = Carbon::parse($date ?? now())->toDateString();

        $rows = ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::WorkoutCompleted->value)
            ->whereBetween('local_event_date', [$since, $until])
            ->whereNull('revoked_at')
            ->whereRaw("json_extract(metadata, '$.volume_lifted') IS NOT NULL")
            ->selectRaw("user_id, CAST(SUM(json_extract(metadata, '$.volume_lifted')) AS INTEGER) as score")
            ->groupBy('user_id')
            ->orderByDesc('score')
            ->get();

        return $this->attachNicknamesAndRank($creatorAppId, $rows);
    }

    /**
     * 15.1 — Challenge leaderboard for a creator-scoped challenge.
     *
     * @return Collection<int, array{rank: int, user_id: int, nickname: string|null, score: int}>
     */
    public function challengeLeaderboard(Challenge $challenge): Collection
    {
        $rows = ChallengeEntry::where('challenge_id', $challenge->id)
            ->selectRaw('user_id, score')
            ->orderByDesc('score')
            ->get();

        return $this->attachNicknamesAndRank($challenge->creator_app_id, $rows);
    }

    /**
     * Persist a computed leaderboard as a snapshot for the given period key.
     */
    public function snapshot(int $creatorAppId, LeaderboardType $type, string $periodKey, Collection $ranked): void
    {
        foreach ($ranked as $entry) {
            LeaderboardSnapshot::updateOrCreate(
                [
                    'creator_app_id'   => $creatorAppId,
                    'leaderboard_type' => $type->value,
                    'period_key'       => $periodKey,
                    'user_id'          => $entry['user_id'],
                ],
                [
                    'score'      => $entry['score'],
                    'rank'       => $entry['rank'],
                    'nickname'   => $entry['nickname'],
                    'snapped_at' => now(),
                ],
            );
        }
    }

    /**
     * Fetch the latest cached snapshot for a leaderboard type + period.
     *
     * @return Collection<int, array>
     */
    public function getSnapshot(int $creatorAppId, LeaderboardType $type, string $periodKey): Collection
    {
        return LeaderboardSnapshot::where('creator_app_id', $creatorAppId)
            ->where('leaderboard_type', $type->value)
            ->where('period_key', $periodKey)
            ->orderBy('rank')
            ->get()
            ->map(fn ($row) => [
                'rank'     => $row->rank,
                'user_id'  => $row->user_id,
                'nickname' => $row->nickname,
                'score'    => $row->score,
            ]);
    }

    /**
     * Left-join user_preferences to get nicknames, filter non-visible users,
     * and assign sequential ranks.
     */
    private function attachNicknamesAndRank(int $creatorAppId, Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $userIds = $rows->pluck('user_id')->all();

        $prefs = UserPreference::where('creator_app_id', $creatorAppId)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $rank = 0;

        return $rows
            ->filter(function ($row) use ($prefs) {
                $pref = $prefs->get($row->user_id);
                // Default to visible if no preference record exists.
                return $pref === null || $pref->leaderboard_visible;
            })
            ->values()
            ->map(function ($row) use (&$rank, $prefs) {
                $pref = $prefs->get($row->user_id);
                return [
                    'rank'     => ++$rank,
                    'user_id'  => $row->user_id,
                    'nickname' => $pref?->leaderboard_nickname,
                    'score'    => (int) $row->score,
                ];
            });
    }
}
