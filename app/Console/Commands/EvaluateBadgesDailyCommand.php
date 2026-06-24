<?php

namespace App\Console\Commands;

use App\Enums\StreakStatus;
use App\Jobs\EvaluateUserBadgesJob;
use App\Models\UserStreak;
use Illuminate\Console\Command;

class EvaluateBadgesDailyCommand extends Command
{
    protected $signature   = 'badges:evaluate-daily';
    protected $description = 'Dispatch a full badge evaluation for every user with an active streak.';

    public function handle(): int
    {
        $pairs = UserStreak::query()
            ->whereIn('status', [StreakStatus::Active->value, StreakStatus::AtRisk->value])
            ->where('current_count', '>', 0)
            ->select('user_id', 'creator_app_id')
            ->distinct()
            ->get();

        $dispatched = 0;

        foreach ($pairs as $pair) {
            EvaluateUserBadgesJob::dispatch($pair->user_id, $pair->creator_app_id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} badge evaluation jobs.");

        return self::SUCCESS;
    }
}
