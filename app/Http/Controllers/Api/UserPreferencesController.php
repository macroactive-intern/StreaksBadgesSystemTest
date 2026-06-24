<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserPreferencesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferencesController extends Controller
{
    public function __construct(private UserPreferencesService $prefsService) {}

    /**
     * 15.1 — GET /api/user/preferences
     * Returns the user's current leaderboard preferences.
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'        => ['required', 'integer', 'min:1'],
            'creator_app_id' => ['required', 'integer', 'min:1'],
        ]);

        $pref = $this->prefsService->get((int) $request->user_id, (int) $request->creator_app_id);

        return response()->json(['data' => $this->format($pref)]);
    }

    /**
     * 15.1 — PATCH /api/user/preferences
     * Update nickname and/or visibility.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'               => ['required', 'integer', 'min:1'],
            'creator_app_id'        => ['required', 'integer', 'min:1'],
            'leaderboard_nickname'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'leaderboard_visible'   => ['sometimes', 'boolean'],
        ]);

        $pref = $this->prefsService->update(
            (int) $request->user_id,
            (int) $request->creator_app_id,
            $request->only(['leaderboard_nickname', 'leaderboard_visible']),
        );

        return response()->json(['data' => $this->format($pref)]);
    }

    private function format(\App\Models\UserPreference $pref): array
    {
        return [
            'user_id'              => $pref->user_id,
            'creator_app_id'       => $pref->creator_app_id,
            'leaderboard_nickname' => $pref->leaderboard_nickname,
            'leaderboard_visible'  => (bool) $pref->leaderboard_visible,
        ];
    }
}
