<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\ActivityEvent;
use Carbon\Carbon;

class AdvancedAntiCheatService
{
    /**
     * Detection type constants used as keys in moderation_items.
     */
    public const TIMEZONE_SWITCH    = 'timezone_switch';
    public const VOLUME_ANOMALY     = 'volume_anomaly';
    public const COMMENT_SPAM       = 'comment_spam';
    public const BACKFILL_PATTERN   = 'backfill_pattern';

    /**
     * Maximum acceptable single-session volume before flagging.
     * Professional powerlifters rarely lift more than 1,000 kg in one session.
     */
    private const MAX_VOLUME_PER_EVENT = 1000.0;

    /**
     * Timezone changes within this many hours of an activity event are suspicious.
     */
    private const TIMEZONE_SWITCH_WINDOW_HOURS = 2;

    /**
     * Number of events in a backfill burst that triggers a flag.
     */
    private const BACKFILL_BURST_THRESHOLD = 10;

    /**
     * Maximum reasonable community events per hour.
     */
    private const MAX_COMMUNITY_EVENTS_PER_HOUR = 10;

    /**
     * 15.4 — Detect rapid timezone switching to game day boundaries.
     * Flags if the user's timezone has changed more than once in the last 2 hours
     * across recent activity events.
     */
    public function detectTimezoneSwitch(int $userId, int $creatorAppId, string $newTimezone, string $localDate): bool
    {
        $since = now()->subHours(self::TIMEZONE_SWITCH_WINDOW_HOURS);

        $recentTimezones = ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('event_timestamp_utc', '>=', $since)
            ->distinct('user_timezone')
            ->pluck('user_timezone')
            ->all();

        if (count($recentTimezones) === 0) {
            return false;
        }

        // Flag if there's a prior timezone and the new one is different.
        return !in_array($newTimezone, $recentTimezones, true) && count($recentTimezones) > 0;
    }

    /**
     * 15.4 — Detect unrealistic workout volume in a single event.
     */
    public function detectVolumeAnomaly(int $userId, int $creatorAppId, ?float $volumeLifted): bool
    {
        if ($volumeLifted === null) {
            return false;
        }

        return $volumeLifted > self::MAX_VOLUME_PER_EVENT;
    }

    /**
     * 15.4 — Detect comment spam: more than MAX_COMMUNITY_EVENTS_PER_HOUR posts in an hour.
     */
    public function detectCommentSpam(int $userId, int $creatorAppId): bool
    {
        $since = now()->subHour();

        $count = ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('event_type', EventType::CommunityCommentPosted->value)
            ->where('event_timestamp_utc', '>=', $since)
            ->whereNull('revoked_at')
            ->count();

        return $count >= self::MAX_COMMUNITY_EVENTS_PER_HOUR;
    }

    /**
     * 15.4 — Detect suspicious backfill: a burst of events with old local_event_dates
     * all submitted within a short window (implying fabrication).
     * Checks for BACKFILL_BURST_THRESHOLD or more events created in the last hour
     * that are dated more than 1 day in the past.
     */
    public function detectSuspiciousBackfill(int $userId, int $creatorAppId, string $localDate): bool
    {
        $yesterday = Carbon::parse($localDate)->subDay()->toDateString();
        $since     = now()->subHour();

        $count = ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('local_event_date', '<', $yesterday)
            ->where('created_at', '>=', $since)
            ->count();

        return $count >= self::BACKFILL_BURST_THRESHOLD;
    }

    /**
     * 15.4 — Run all checks for a newly submitted event and return an array
     * of triggered detections: [['type' => ..., 'severity' => ..., 'payload' => [...]]]
     */
    public function runAllChecks(
        int $userId,
        int $creatorAppId,
        string $eventType,
        string $timezone,
        string $localDate,
        ?float $volumeLifted = null,
    ): array {
        $detections = [];

        if ($this->detectTimezoneSwitch($userId, $creatorAppId, $timezone, $localDate)) {
            $detections[] = [
                'type'     => self::TIMEZONE_SWITCH,
                'severity' => 'medium',
                'payload'  => ['new_timezone' => $timezone, 'local_date' => $localDate],
            ];
        }

        if ($this->detectVolumeAnomaly($userId, $creatorAppId, $volumeLifted)) {
            $detections[] = [
                'type'     => self::VOLUME_ANOMALY,
                'severity' => 'high',
                'payload'  => ['volume_lifted' => $volumeLifted, 'local_date' => $localDate],
            ];
        }

        if ($eventType === EventType::CommunityCommentPosted->value && $this->detectCommentSpam($userId, $creatorAppId)) {
            $detections[] = [
                'type'     => self::COMMENT_SPAM,
                'severity' => 'medium',
                'payload'  => ['local_date' => $localDate],
            ];
        }

        if ($this->detectSuspiciousBackfill($userId, $creatorAppId, $localDate)) {
            $detections[] = [
                'type'     => self::BACKFILL_PATTERN,
                'severity' => 'high',
                'payload'  => ['local_date' => $localDate],
            ];
        }

        return $detections;
    }
}
