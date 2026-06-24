<?php

namespace App\Http\Controllers\Api\Creator;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Services\AntiCheatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class EventAdminController extends Controller
{
    public function __construct(private AntiCheatService $antiCheat) {}

    /**
     * 11.3 — DELETE /api/creator/events/source
     * Revoke all activity events tied to a specific source (e.g. a deleted workout or comment).
     * Called by the source system when content is removed or flagged as invalid.
     */
    public function revokeBySource(Request $request): JsonResponse
    {
        $request->validate([
            'creator_app_id' => ['required', 'integer', 'min:1'],
            'event_type'     => ['required', 'string', new Enum(EventType::class)],
            'source_id'      => ['required', 'integer', 'min:1'],
        ]);

        $revoked = $this->antiCheat->revokeBySource(
            (int) $request->creator_app_id,
            EventType::from($request->event_type),
            (int) $request->source_id,
        );

        return response()->json([
            'data' => ['revoked_count' => $revoked],
        ]);
    }
}
