<?php

namespace App\Http\Resources;

use App\Enums\StreakStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a UserStreak model for the "Your Streak" widget.
 * Computes next_milestone and progress_percent from config('streaks.milestones').
 */
class StreakWidgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $milestones   = config('streaks.milestones', []);
        $currentCount = $this->current_count;

        $nextMilestone   = collect($milestones)->first(fn ($m) => $m > $currentCount);
        $progressPercent = $nextMilestone
            ? (int) min(100, (int) floor($currentCount / $nextMilestone * 100))
            : ($currentCount > 0 ? 100 : 0);

        return [
            'id'                 => $this->id,
            'streak_type'        => $this->streak_type->value,
            'current_count'      => $currentCount,
            'longest_count'      => $this->longest_count,
            'status'             => $this->status->value,
            'status_label'       => $this->statusLabel(),
            'last_completed_date' => $this->last_completed_date?->toDateString(),
            'next_milestone'     => $nextMilestone,
            'progress_percent'   => $progressPercent,
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            StreakStatus::Active => 'Active',
            StreakStatus::AtRisk => 'At Risk',
            StreakStatus::Broken => 'Broken',
        };
    }
}
