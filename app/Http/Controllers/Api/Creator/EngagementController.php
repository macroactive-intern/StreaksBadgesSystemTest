<?php

namespace App\Http\Controllers\Api\Creator;

use App\Http\Controllers\Controller;
use App\Services\CreatorEngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function __construct(private CreatorEngagementService $engagementService) {}

    /**
     * 8.11 — GET /api/creator/engagement
     * Returns engagement summary for a creator app.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'limit'          => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $summary = $this->engagementService->summary(
            (int) $request->creator_app_id,
            (int) ($request->limit ?? 10),
        );

        return response()->json(['data' => $summary]);
    }
}
