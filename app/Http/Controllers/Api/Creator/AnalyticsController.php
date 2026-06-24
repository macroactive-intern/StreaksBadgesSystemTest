<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Services\PilotReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private PilotReportingService $pilotService,
    ) {}

    /**
     * 13.2 — GET /api/creator/analytics
     * Full engagement metrics summary for a creator app on a given date.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'date'           => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $date = $request->input('date', now()->toDateString());

        return response()->json([
            'data' => $this->analyticsService->summary((int) $request->creator_app_id, $date),
        ]);
    }

    /**
     * 13.3 — GET /api/creator/pilot/report
     * Cohort comparison between pilot and control creator groups.
     * Creator IDs are resolved from config (ANALYTICS_PILOT_CREATOR_IDS / ANALYTICS_CONTROL_CREATOR_IDS).
     */
    public function pilotReport(): JsonResponse
    {
        return response()->json([
            'data' => $this->pilotService->cohortComparison(
                PilotReportingService::pilotCreatorIds(),
                PilotReportingService::controlCreatorIds(),
            ),
        ]);
    }
}
