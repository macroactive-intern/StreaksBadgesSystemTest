<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FreezeCooldownException;
use App\Exceptions\NoAvailableFreezeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UseStreakFreezeRequest;
use App\Http\Resources\StreakWidgetResource;
use App\Models\UserStreak;
use App\Services\StreakFreezeService;
use App\Services\UserDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakController extends Controller
{
    public function __construct(
        private UserDashboardService $dashboardService,
        private StreakFreezeService $freezeService,
    ) {}

    /**
     * 8.1 — GET /api/streaks
     * Returns all streak widgets for a user in a creator app.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'        => ['required', 'integer', 'min:1'],
            'creator_app_id' => ['required', 'integer', 'min:1'],
        ]);

        $streaks = $this->dashboardService->getStreakWidgets(
            (int) $request->user_id,
            (int) $request->creator_app_id,
        );

        return response()->json([
            'data' => StreakWidgetResource::collection($streaks),
        ]);
    }

    /**
     * 8.4 — POST /api/streaks/{streak}/freeze
     * Applies the user's available freeze to the given missed date.
     */
    public function freeze(UseStreakFreezeRequest $request, UserStreak $streak): JsonResponse
    {
        if ($streak->user_id !== (int) $request->user_id) {
            return response()->json(['message' => 'Streak does not belong to this user.'], 403);
        }

        try {
            $applied = $this->freezeService->apply(
                (int) $request->user_id,
                (int) $request->creator_app_id,
                $streak->streak_type,
                $request->missed_date,
            );
        } catch (NoAvailableFreezeException) {
            return response()->json(['message' => 'No freeze available for this streak type.'], 422);
        } catch (FreezeCooldownException) {
            return response()->json(['message' => 'A freeze was used recently. Please wait 30 days before using another.'], 422);
        }

        return response()->json([
            'data' => [
                'applied_to_date' => $applied->applied_to_date,
                'used_at'         => $applied->used_at?->toIso8601String(),
            ],
        ]);
    }
}
