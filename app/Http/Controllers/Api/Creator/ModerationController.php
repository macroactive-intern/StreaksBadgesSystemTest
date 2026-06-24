<?php

namespace App\Http\Controllers\Api\Creator;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\ModerationItem;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ModerationController extends Controller
{
    public function __construct(private ModerationService $moderationService) {}

    /**
     * 15.4 — GET /api/creator/moderation/queue
     * Returns flagged items for a creator app, ordered by severity then age.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'status'         => ['sometimes', new Enum(ModerationStatus::class)],
        ]);

        $status = $request->input('status', ModerationStatus::Pending->value);

        $items = $this->moderationService->getQueue((int) $request->creator_app_id, $status);

        return response()->json([
            'data' => $items->map(fn ($item) => [
                'id'             => $item->id,
                'user_id'        => $item->user_id,
                'detection_type' => $item->detection_type,
                'severity'       => $item->severity,
                'payload'        => $item->payload,
                'status'         => $item->status,
                'created_at'     => $item->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * 15.4 — PATCH /api/creator/moderation/{item}
     * Resolve or dismiss a moderation item.
     */
    public function review(Request $request, ModerationItem $item): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'status'         => ['required', new Enum(ModerationStatus::class)],
            'reviewer_id'    => ['sometimes', 'integer', 'min:1'],
            'review_notes'   => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        if ($item->creator_app_id !== (int) $request->creator_app_id) {
            return response()->json(['message' => 'Item not found for this creator app.'], 404);
        }

        $updated = $this->moderationService->review(
            $item,
            ModerationStatus::from($request->status),
            $request->review_notes,
            $request->integer('reviewer_id') ?: null,
        );

        return response()->json([
            'data' => [
                'id'           => $updated->id,
                'status'       => $updated->status,
                'reviewed_at'  => $updated->reviewed_at?->toIso8601String(),
                'review_notes' => $updated->review_notes,
            ],
        ]);
    }
}
