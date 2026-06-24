<?php

namespace App\Services;

use App\Enums\ModerationStatus;
use App\Models\ModerationItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ModerationService
{
    /**
     * 15.4 — Flag a detection for human review.
     * Deduplicates within a 24-hour window per (user, creator_app, detection_type).
     */
    public function flag(
        int $userId,
        int $creatorAppId,
        string $detectionType,
        string $severity,
        array $payload = [],
    ): ModerationItem {
        if ($this->isDuplicate($userId, $creatorAppId, $detectionType)) {
            return ModerationItem::where('user_id', $userId)
                ->where('creator_app_id', $creatorAppId)
                ->where('detection_type', $detectionType)
                ->where('status', ModerationStatus::Pending->value)
                ->where('created_at', '>=', now()->subDay())
                ->latest()
                ->first();
        }

        return ModerationItem::create([
            'user_id'        => $userId,
            'creator_app_id' => $creatorAppId,
            'detection_type' => $detectionType,
            'severity'       => $severity,
            'payload'        => $payload ?: null,
            'status'         => ModerationStatus::Pending->value,
        ]);
    }

    /**
     * 15.4 — Returns pending (or filtered) moderation items for a creator app.
     */
    public function getQueue(int $creatorAppId, string $status = 'pending'): Collection
    {
        return ModerationItem::where('creator_app_id', $creatorAppId)
            ->where('status', $status)
            ->orderByRaw("CASE severity WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('created_at')
            ->get();
    }

    /**
     * 15.4 — Mark a moderation item as resolved or dismissed.
     */
    public function review(
        ModerationItem $item,
        ModerationStatus $status,
        ?string $notes = null,
        ?int $reviewerId = null,
    ): ModerationItem {
        $item->update([
            'status'       => $status->value,
            'reviewed_at'  => now(),
            'reviewer_id'  => $reviewerId,
            'review_notes' => $notes,
        ]);

        return $item->fresh();
    }

    /**
     * Returns true if a pending flag of this type already exists within the last 24 hours.
     */
    public function isDuplicate(int $userId, int $creatorAppId, string $detectionType): bool
    {
        return ModerationItem::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->where('detection_type', $detectionType)
            ->where('status', ModerationStatus::Pending->value)
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }
}
