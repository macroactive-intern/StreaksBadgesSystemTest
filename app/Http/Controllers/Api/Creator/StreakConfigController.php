<?php

namespace App\Http\Controllers\Api\Creator;

use App\Enums\StreakType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStreakConfigRequest;
use App\Services\CreatorStreakConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakConfigController extends Controller
{
    public function __construct(private CreatorStreakConfigService $configService) {}

    /**
     * 8.5 — GET /api/creator/streak-config
     * Returns all streak configs for a creator app.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
        ]);

        $configs = $this->configService->getAll((int) $request->creator_app_id);

        return response()->json(['data' => $configs]);
    }

    /**
     * 8.6 — PATCH /api/creator/streak-config
     * Create or update a streak config for a given streak type.
     */
    public function update(UpdateStreakConfigRequest $request): JsonResponse
    {
        $streakType = StreakType::from($request->streak_type);

        $attributes = array_filter([
            'enabled'                 => $request->enabled,
            'qualifying_event_type'   => $request->qualifying_event_type,
            'freeze_grants_per_month' => $request->freeze_grants_per_month,
            'at_risk_grace_hours'     => $request->at_risk_grace_hours,
            'reward_config'           => $request->reward_config,
        ], fn ($v) => $v !== null);

        $config = $this->configService->upsert(
            (int) $request->creator_app_id,
            $streakType,
            $attributes,
        );

        return response()->json(['data' => $config]);
    }
}
