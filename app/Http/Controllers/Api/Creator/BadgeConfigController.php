<?php

namespace App\Http\Controllers\Api\Creator;

use App\Exceptions\BadgeAlreadyAwardedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AwardBadgeRequest;
use App\Http\Requests\RevokeBadgeRequest;
use App\Http\Requests\UpdateBadgeConfigRequest;
use App\Http\Resources\EarnedBadgeResource;
use App\Models\UserBadge;
use App\Services\CreatorBadgeConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BadgeConfigController extends Controller
{
    public function __construct(private CreatorBadgeConfigService $configService) {}

    /**
     * 8.7 — GET /api/creator/badge-config
     * Returns all badge definitions visible to a creator (platform + creator-owned).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
        ]);

        $definitions = $this->configService->getDefinitions((int) $request->creator_app_id);

        return response()->json(['data' => $definitions]);
    }

    /**
     * 8.8 — PATCH /api/creator/badge-config
     * Toggle enabled or attach a reward to a creator-owned badge definition.
     */
    public function update(UpdateBadgeConfigRequest $request): JsonResponse
    {
        $creatorAppId = (int) $request->creator_app_id;
        $badgeId      = (int) $request->badge_id;

        try {
            if ($request->has('reward')) {
                $badge = $this->configService->attachReward($creatorAppId, $badgeId, $request->reward ?? []);
            } elseif ($request->has('enabled')) {
                $badge = $request->boolean('enabled')
                    ? $this->configService->enable($creatorAppId, $badgeId)
                    : $this->configService->disable($creatorAppId, $badgeId);
            } else {
                return response()->json(['message' => 'Nothing to update. Provide enabled or reward.'], 422);
            }
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $badge]);
    }

    /**
     * 8.9 — POST /api/creator/users/{user}/badges
     * Manually award a badge to a user.
     */
    public function awardBadge(AwardBadgeRequest $request, int $user): JsonResponse
    {
        try {
            $userBadge = $this->configService->manualAward(
                $user,
                (int) $request->creator_app_id,
                (int) $request->badge_id,
                (int) $request->awarded_by,
            );
        } catch (BadgeAlreadyAwardedException) {
            return response()->json(['message' => 'This badge has already been awarded to this user.'], 422);
        }

        $userBadge->load('badgeDefinition');

        return response()->json(['data' => new EarnedBadgeResource($userBadge)], 201);
    }

    /**
     * 8.10 — DELETE /api/creator/users/{user}/badges/{badge}
     * Revoke a previously awarded badge.
     */
    public function revokeBadge(RevokeBadgeRequest $request, int $user, UserBadge $badge): JsonResponse
    {
        if ($badge->user_id !== $user) {
            return response()->json(['message' => 'Badge does not belong to this user.'], 403);
        }

        try {
            $this->configService->revoke($badge->id, $request->revoke_reason ?? '');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }
}
