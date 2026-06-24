<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarnedBadgeResource;
use App\Models\UserBadge;
use App\Services\BadgeVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BadgeVisibilityController extends Controller
{
    public function __construct(private BadgeVisibilityService $visibilityService) {}

    /**
     * 12.1 — PATCH /api/badges/{badge}/visibility
     * Toggle whether a badge is visible in public-facing views.
     */
    public function setVisibility(Request $request, UserBadge $badge): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'hidden'  => ['required', 'boolean'],
        ]);

        if ($badge->user_id !== (int) $request->user_id) {
            return response()->json(['message' => 'Badge does not belong to this user.'], 403);
        }

        $badge->load('badgeDefinition');
        $updated = $this->visibilityService->setHidden($badge, (bool) $request->hidden);

        return response()->json(['data' => new EarnedBadgeResource($updated)]);
    }

    /**
     * 12.1 — PATCH /api/badges/{badge}/feature
     * Set a badge as the user's featured badge.
     * Send hidden=false body or omit to set; set body to {"unfeature":true} to clear.
     */
    public function setFeatured(Request $request, UserBadge $badge): JsonResponse
    {
        $request->validate([
            'user_id'   => ['required', 'integer', 'min:1'],
            'unfeature' => ['sometimes', 'boolean'],
        ]);

        if ($badge->user_id !== (int) $request->user_id) {
            return response()->json(['message' => 'Badge does not belong to this user.'], 403);
        }

        $badge->load('badgeDefinition');

        if ($request->boolean('unfeature')) {
            $this->visibilityService->clearFeatured($badge->user_id, $badge->creator_app_id);
            $badge->refresh();
            return response()->json(['data' => new EarnedBadgeResource($badge)]);
        }

        try {
            $updated = $this->visibilityService->setFeatured($badge);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => new EarnedBadgeResource($updated)]);
    }
}
