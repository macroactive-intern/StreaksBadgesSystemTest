<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeaderboardType;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class LeaderboardController extends Controller
{
    public function __construct(private LeaderboardService $leaderboardService) {}

    /**
     * 15.1 — GET /api/leaderboards
     * Live-computed leaderboard for weekly_workout, monthly_streak, or volume_lifted.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'type'           => ['required', new Enum(LeaderboardType::class)],
            'date'           => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $type = LeaderboardType::from($request->type);

        $entries = match ($type) {
            LeaderboardType::WeeklyWorkout => $this->leaderboardService->weeklyWorkout(
                (int) $request->creator_app_id,
                $request->date,
            ),
            LeaderboardType::MonthlyStreak => $this->leaderboardService->monthlyStreak(
                (int) $request->creator_app_id,
            ),
            LeaderboardType::VolumeLifted => $this->leaderboardService->volumeLifted(
                (int) $request->creator_app_id,
                $request->date,
            ),
        };

        return response()->json([
            'data' => [
                'type'    => $type->value,
                'label'   => $type->label(),
                'entries' => $entries->values(),
            ],
        ]);
    }

    /**
     * 15.1 — GET /api/leaderboards/challenge/{challenge}
     * Leaderboard for a specific creator-scoped challenge.
     */
    public function challenge(Request $request, Challenge $challenge): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($challenge->creator_app_id !== (int) $request->creator_app_id) {
            return response()->json(['message' => 'Challenge not found for this creator app.'], 404);
        }

        return response()->json([
            'data' => [
                'challenge_id' => $challenge->id,
                'title'        => $challenge->title,
                'ends_at'      => $challenge->ends_at?->toIso8601String(),
                'entries'      => $this->leaderboardService->challengeLeaderboard($challenge)->values(),
            ],
        ]);
    }
}
