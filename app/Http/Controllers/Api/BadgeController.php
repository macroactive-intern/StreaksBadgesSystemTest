<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarnedBadgeResource;
use App\Http\Resources\LockedBadgeResource;
use App\Services\UserDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function __construct(private UserDashboardService $dashboardService) {}

    /**
     * 8.2 — GET /api/badges
     * Returns earned badges and optionally locked badges for a user.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'        => ['required', 'integer', 'min:1'],
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'show_locked'    => ['sometimes', 'boolean'],
        ]);

        $showLocked = filter_var($request->show_locked, FILTER_VALIDATE_BOOLEAN);

        $data = $this->dashboardService->getBadgeDisplay(
            (int) $request->user_id,
            (int) $request->creator_app_id,
            $showLocked,
        );

        return response()->json([
            'data' => [
                'earned' => EarnedBadgeResource::collection($data['earned']),
                'locked' => $showLocked ? LockedBadgeResource::collection($data['locked']) : [],
            ],
        ]);
    }
}
