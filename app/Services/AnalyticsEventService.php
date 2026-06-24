<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Carbon\Carbon;

class AnalyticsEventService
{
    /**
     * 13.1 — Record a discrete engagement event for analytics purposes.
     * Fire-and-forget: exceptions are swallowed so analytics failures
     * never interrupt the main user flow.
     */
    public function record(
        int $creatorAppId,
        int $userId,
        string $eventType,
        array $payload = [],
        ?Carbon $occurredAt = null,
    ): void {
        try {
            AnalyticsEvent::create([
                'creator_app_id' => $creatorAppId,
                'user_id'        => $userId,
                'event_type'     => $eventType,
                'payload'        => $payload ?: null,
                'occurred_at'    => $occurredAt ?? now(),
            ]);
        } catch (\Throwable) {
            // Analytics must not break the request path.
        }
    }
}
