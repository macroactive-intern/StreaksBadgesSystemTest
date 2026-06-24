<?php

namespace App\Services;

use App\Models\ActivityEvent;
use Carbon\Carbon;

class PilotReportingService
{
    /**
     * 13.3 — Day-N retention for a set of creator app IDs.
     *
     * Definition: of all users who had their FIRST ever event at least N days ago,
     * what fraction had ANY event on or around day N (within ±1 day tolerance)?
     *
     * Returns a float 0–1.
     */
    public function dayNRetention(array $creatorAppIds, int $day): float
    {
        if (empty($creatorAppIds)) {
            return 0.0;
        }

        // Cohort = users whose first event was at least $day days ago.
        $cohortCutoff = now()->subDays($day)->toDateString();

        $cohortUsers = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
            ->whereNull('revoked_at')
            ->selectRaw('user_id, MIN(local_event_date) as first_event_date')
            ->groupBy('user_id')
            ->having('first_event_date', '<=', $cohortCutoff)
            ->get()
            ->keyBy('user_id');

        $cohortSize = $cohortUsers->count();

        if ($cohortSize === 0) {
            return 0.0;
        }

        // Retained = cohort users who had an event on their day-N date (±1 day window).
        $retained = 0;

        foreach ($cohortUsers as $row) {
            $dayNDate    = Carbon::parse($row->first_event_date)->addDays($day)->toDateString();
            $windowStart = Carbon::parse($dayNDate)->subDay()->toDateString();
            $windowEnd   = Carbon::parse($dayNDate)->addDay()->toDateString();

            $hasActivity = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
                ->where('user_id', $row->user_id)
                ->whereBetween('local_event_date', [$windowStart, $windowEnd])
                ->whereNull('revoked_at')
                ->exists();

            if ($hasActivity) {
                $retained++;
            }
        }

        return round($retained / $cohortSize, 4);
    }

    /**
     * 13.3 — First-month churn: users active in week 1 but silent in weeks 2–4.
     * Returns a float 0–1.
     */
    public function firstMonthChurn(array $creatorAppIds): float
    {
        if (empty($creatorAppIds)) {
            return 0.0;
        }

        // Week-1 cohort: users with a first event at least 30 days ago.
        $cohortCutoff = now()->subDays(30)->toDateString();

        $cohortRows = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
            ->whereNull('revoked_at')
            ->selectRaw('user_id, MIN(local_event_date) as first_event_date')
            ->groupBy('user_id')
            ->having('first_event_date', '<=', $cohortCutoff)
            ->get();

        if ($cohortRows->isEmpty()) {
            return 0.0;
        }

        $churned = 0;

        foreach ($cohortRows as $row) {
            $week1End   = Carbon::parse($row->first_event_date)->addDays(7)->toDateString();
            $week2Start = Carbon::parse($row->first_event_date)->addDays(8)->toDateString();
            $week4End   = Carbon::parse($row->first_event_date)->addDays(30)->toDateString();

            $activeInWeek1 = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
                ->where('user_id', $row->user_id)
                ->whereBetween('local_event_date', [$row->first_event_date, $week1End])
                ->whereNull('revoked_at')
                ->exists();

            if (!$activeInWeek1) {
                continue;
            }

            $activeInWeeks2to4 = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
                ->where('user_id', $row->user_id)
                ->whereBetween('local_event_date', [$week2Start, $week4End])
                ->whereNull('revoked_at')
                ->exists();

            if (!$activeInWeeks2to4) {
                $churned++;
            }
        }

        return round($churned / $cohortRows->count(), 4);
    }

    /**
     * 13.3 — Full cohort comparison: pilot vs control on all retention windows
     * plus first-month churn delta.
     */
    public function cohortComparison(array $pilotCreatorIds, array $controlCreatorIds): array
    {
        $windows = config('analytics.retention_windows', [7, 30]);

        $pilot   = [];
        $control = [];

        foreach ($windows as $day) {
            $pilot["day_{$day}_retention"]   = $this->dayNRetention($pilotCreatorIds, $day);
            $control["day_{$day}_retention"] = $this->dayNRetention($controlCreatorIds, $day);
        }

        $pilot['first_month_churn']   = $this->firstMonthChurn($pilotCreatorIds);
        $control['first_month_churn'] = $this->firstMonthChurn($controlCreatorIds);

        $deltas = [];
        foreach (array_keys($pilot) as $metric) {
            $deltas[$metric . '_delta'] = round(($pilot[$metric] - $control[$metric]), 4);
        }

        return [
            'pilot'   => $pilot,
            'control' => $control,
            'deltas'  => $deltas,
        ];
    }

    /**
     * 13.3 — Resolve pilot/control creator IDs from config (comma-separated env strings).
     */
    public static function pilotCreatorIds(): array
    {
        return self::parseIds(config('analytics.pilot_creator_ids', ''));
    }

    public static function controlCreatorIds(): array
    {
        return self::parseIds(config('analytics.control_creator_ids', ''));
    }

    private static function parseIds(string $raw): array
    {
        return array_values(
            array_filter(
                array_map('intval', explode(',', $raw)),
                fn ($id) => $id > 0,
            )
        );
    }
}
