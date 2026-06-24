<?php

namespace App\Console\Commands;

use App\Enums\StreakStatus;
use App\Jobs\EvaluateUserStreaksJob;
use App\Models\ActivityEvent;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EvaluateStreaksDailyCommand extends Command
{
    protected $signature   = 'streaks:evaluate-daily';
    protected $description = 'Dispatch streak evaluation for each user whose local day has not been evaluated yet.';

    public function handle(): int
    {
        // Collect distinct (user_id, creator_app_id) pairs with streaks that may need updating.
        // Broken streaks with current_count=0 have nothing left to break — exclude them.
        $pairs = UserStreak::query()
            ->where(fn ($q) => $q
                ->whereIn('status', [StreakStatus::Active->value, StreakStatus::AtRisk->value])
                ->orWhere(fn ($q2) => $q2
                    ->where('status', StreakStatus::Broken->value)
                    ->where('current_count', '>', 0)
                )
            )
            ->select('user_id', 'creator_app_id')
            ->distinct()
            ->get();

        $dispatched = 0;

        foreach ($pairs as $pair) {
            $timezone = $this->resolveTimezone($pair->user_id, $pair->creator_app_id);
            $localToday = Carbon::now($timezone)->toDateString();

            // Skip users already fully evaluated for today in their timezone.
            $allEvaluated = UserStreak::where('user_id', $pair->user_id)
                ->where('creator_app_id', $pair->creator_app_id)
                ->whereNotNull('last_evaluated_date')
                ->where('last_evaluated_date', '>=', $localToday)
                ->whereIn('status', [StreakStatus::Active->value, StreakStatus::AtRisk->value])
                ->count();

            $total = UserStreak::where('user_id', $pair->user_id)
                ->where('creator_app_id', $pair->creator_app_id)
                ->whereIn('status', [StreakStatus::Active->value, StreakStatus::AtRisk->value])
                ->count();

            if ($total > 0 && $allEvaluated >= $total) {
                continue;
            }

            EvaluateUserStreaksJob::dispatch($pair->user_id, $pair->creator_app_id, $localToday, $timezone);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} streak evaluation jobs.");

        return self::SUCCESS;
    }

    private function resolveTimezone(int $userId, int $creatorAppId): string
    {
        return ActivityEvent::where('user_id', $userId)
            ->where('creator_app_id', $creatorAppId)
            ->latest('event_timestamp_utc')
            ->value('user_timezone') ?? 'UTC';
    }
}
