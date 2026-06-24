<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Services\AdvancedAnalyticsService;
use App\Services\DataWarehouseExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvancedAnalyticsController extends Controller
{
    public function __construct(
        private AdvancedAnalyticsService $analyticsService,
        private DataWarehouseExportService $exportService,
    ) {}

    /**
     * 15.3 — GET /api/creator/analytics/cohort
     * Multi-window cohort retention for a creator app (or multiple via comma-separated IDs).
     */
    public function cohortRetention(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_ids' => ['required', 'string'],
            'windows'         => ['sometimes', 'string'],
        ]);

        $creatorAppIds = array_values(array_filter(
            array_map('intval', explode(',', $request->creator_app_ids)),
            fn ($id) => $id > 0,
        ));

        $windows = $request->has('windows')
            ? array_values(array_filter(array_map('intval', explode(',', $request->windows)), fn ($w) => $w > 0))
            : config('analytics.retention_windows', [7, 30]);

        return response()->json([
            'data' => $this->analyticsService->cohortRetention($creatorAppIds, $windows),
        ]);
    }

    /**
     * 15.3 — POST /api/creator/analytics/ltv
     * Correlate engagement metrics against caller-supplied LTV values.
     * Body: { creator_app_id: int, revenues: [{ user_id: int, ltv: float }] }
     */
    public function ltvCorrelation(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id'      => ['required', 'integer', 'min:1'],
            'revenues'            => ['required', 'array', 'min:1'],
            'revenues.*.user_id'  => ['required', 'integer', 'min:1'],
            'revenues.*.ltv'      => ['required', 'numeric', 'min:0'],
        ]);

        $userRevenues = collect($request->revenues)
            ->pluck('ltv', 'user_id')
            ->map(fn ($v) => (float) $v)
            ->all();

        return response()->json([
            'data' => $this->analyticsService->correlateWithLtv(
                (int) $request->creator_app_id,
                $userRevenues,
            ),
        ]);
    }

    /**
     * 15.3 — GET /api/creator/analytics/export
     * Batch-export analytics events for DWH ingestion.
     */
    public function exportBatch(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'since'          => ['required', 'date'],
            'until'          => ['sometimes', 'date', 'after_or_equal:since'],
            'table'          => ['sometimes', 'in:analytics_events,activity_events'],
        ]);

        $table = $request->input('table', 'analytics_events');

        $records = $table === 'activity_events'
            ? $this->exportService->exportActivityEvents(
                (int) $request->creator_app_id,
                $request->since,
                $request->until,
            )
            : $this->exportService->exportAnalyticsEvents(
                (int) $request->creator_app_id,
                $request->since,
                $request->until,
            );

        return response()->json([
            'data' => [
                'table'        => $table,
                'record_count' => count($records),
                'records'      => $records,
            ],
        ]);
    }
}
