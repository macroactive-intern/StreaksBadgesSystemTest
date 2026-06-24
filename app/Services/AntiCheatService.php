<?php

namespace App\Services;

use App\Enums\EventType;
use App\Exceptions\BackfillNotAllowedException;
use App\Exceptions\CommunityRateLimitException;
use App\Exceptions\DuplicateSourceEventException;
use App\Models\ActivityEvent;
use Carbon\Carbon;

class AntiCheatService
{
    /**
     * Events older than this many days (local timezone) are rejected as unauthorised backfills.
     * Yesterday is always allowed so users can log before midnight rolls over on the server.
     */
    public const BACKFILL_WINDOW_DAYS = 1;

    /**
     * Maximum community_comment_posted events a user may record per local day.
     * Events beyond this cap are rejected at the API boundary.
     */
    public const MAX_DAILY_COMMUNITY_EVENTS = 20;

    // -------------------------------------------------------------------------
    // 11.2 / 11.3 — Event-recording guards (called from ActivityEventService)
    // -------------------------------------------------------------------------

    /**
     * 11.3 — Reject events older than BACKFILL_WINDOW_DAYS days (local time).
     * Throws BackfillNotAllowedException.
     */
    public function checkBackfillWindow(string $localDate, string $userTimezone): void
    {
        $cutoff = Carbon::now($userTimezone)->subDays(self::BACKFILL_WINDOW_DAYS)->toDateString();

        if ($localDate < $cutoff) {
            throw new BackfillNotAllowedException();
        }
    }

    /**
     * 11.3 — Reject events whose source_id has already been recorded for this user.
     * Prevents double-credit when the same workout/comment/habit is submitted twice.
     * No-op when sourceId is null (source not tracked for this event type).
     * Throws DuplicateSourceEventException.
     */
    public function checkDuplicateSource(
        int $userId,
        int $creatorAppId,
        EventType $eventType,
        ?int $sourceId,
    ): void {
        if ($sourceId === null) {
            return;
        }

        $exists = ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('event_type', $eventType->value)
            ->where('source_id', $sourceId)
            ->whereNull('revoked_at')
            ->exists();

        if ($exists) {
            throw new DuplicateSourceEventException();
        }
    }

    /**
     * 11.4 — Reject community comment events once the daily cap is reached.
     * Only applies to community_comment_posted events; all other types are uncapped.
     * Throws CommunityRateLimitException.
     */
    public function checkCommunityRateLimit(
        int $userId,
        int $creatorAppId,
        string $localDate,
    ): void {
        $count = ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::CommunityCommentPosted->value)
            ->whereDate('local_event_date', $localDate)
            ->whereNull('revoked_at')
            ->count();

        if ($count >= self::MAX_DAILY_COMMUNITY_EVENTS) {
            throw new CommunityRateLimitException();
        }
    }

    // -------------------------------------------------------------------------
    // 11.3 — Source-deletion revocation (called when source content is deleted)
    // -------------------------------------------------------------------------

    /**
     * Revoke all non-revoked events matching the given source, issued by a creator app.
     * Called when a workout, nutrition log, habit, comment, or challenge is deleted
     * in the source system so its streak / badge credit is invalidated.
     *
     * Returns the number of rows revoked.
     */
    public function revokeBySource(
        int $creatorAppId,
        EventType $eventType,
        int $sourceId,
    ): int {
        return ActivityEvent::where('creator_app_id', $creatorAppId)
            ->where('event_type', $eventType->value)
            ->where('source_id', $sourceId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
