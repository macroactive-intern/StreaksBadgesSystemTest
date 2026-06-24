<?php

namespace App\Services;

use App\Models\ActivityEvent;
use App\Models\AnalyticsEvent;
use App\Models\UserBadge;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdvancedAnalyticsService
{
    /**
     * 15.3 — Multi-window cohort retention breakdown.
     * Returns day-N retention rates for each window in the given array.
     *
     * @param  int[]  $creatorAppIds
     * @param  int[]  $windows
     */
    public function cohortRetention(array $creatorAppIds, array $windows): array
    {
        if (empty($creatorAppIds) || empty($windows)) {
            return [];
        }

        $result = [];

        foreach ($windows as $day) {
            $cohortCutoff = now()->subDays($day)->toDateString();

            $cohort = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
                ->whereNull('revoked_at')
                ->selectRaw('user_id, MIN(local_event_date) as first_event_date')
                ->groupBy('user_id')
                ->having('first_event_date', '<=', $cohortCutoff)
                ->get()
                ->keyBy('user_id');

            $cohortSize = $cohort->count();

            if ($cohortSize === 0) {
                $result["day_{$day}"] = ['cohort_size' => 0, 'retained' => 0, 'rate' => 0.0];
                continue;
            }

            $retained = 0;

            foreach ($cohort as $row) {
                $dayNDate    = Carbon::parse($row->first_event_date)->addDays($day)->toDateString();
                $windowStart = Carbon::parse($dayNDate)->subDay()->toDateString();
                $windowEnd   = Carbon::parse($dayNDate)->addDay()->toDateString();

                $has = ActivityEvent::whereIn('creator_app_id', $creatorAppIds)
                    ->where('user_id', $row->user_id)
                    ->whereBetween('local_event_date', [$windowStart, $windowEnd])
                    ->whereNull('revoked_at')
                    ->exists();

                if ($has) {
                    $retained++;
                }
            }

            $result["day_{$day}"] = [
                'cohort_size' => $cohortSize,
                'retained'    => $retained,
                'rate'        => round($retained / $cohortSize, 4),
            ];
        }

        return $result;
    }

    /**
     * 15.3 — LTV correlation: correlates engagement score with provided LTV values.
     * Engagement score = analytics event count per user.
     * Returns Pearson r and a per-user breakdown.
     *
     * @param  array<int, float>  $userRevenues  Map of user_id => lifetime revenue
     */
    public function correlateWithLtv(int $creatorAppId, array $userRevenues): array
    {
        if (empty($userRevenues)) {
            return ['pearson_r' => null, 'data' => []];
        }

        $userIds = array_keys($userRevenues);

        $engagementCounts = AnalyticsEvent::where('creator_app_id', $creatorAppId)
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(*) as event_count')
            ->groupBy('user_id')
            ->get()
            ->pluck('event_count', 'user_id');

        $pairs = collect($userRevenues)->map(fn ($ltv, $userId) => [
            'user_id'        => $userId,
            'ltv'            => $ltv,
            'engagement'     => (int) ($engagementCounts[$userId] ?? 0),
        ])->values();

        return [
            'pearson_r' => $this->pearson(
                $pairs->pluck('engagement')->all(),
                $pairs->pluck('ltv')->all(),
            ),
            'data' => $pairs->all(),
        ];
    }

    /**
     * 15.3 — NDR correlation: for a set of creator apps at a given NDR value,
     * returns engagement breakdown that can inform which metrics track NDR best.
     *
     * @param  int[]   $creatorAppIds
     * @param  float   $ndrValue       Net Dollar Retention as a decimal (e.g. 1.12 = 112%)
     */
    public function correlateWithNdr(array $creatorAppIds, float $ndrValue): array
    {
        $summary = [];

        foreach ($creatorAppIds as $creatorAppId) {
            $eventCounts = AnalyticsEvent::where('creator_app_id', $creatorAppId)
                ->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->get()
                ->pluck('count', 'event_type');

            $summary[$creatorAppId] = [
                'ndr'          => $ndrValue,
                'event_counts' => $eventCounts,
            ];
        }

        return $summary;
    }

    /**
     * 15.3 — CAC efficiency: computes cost-per-active-user and cost-per-retained-user.
     *
     * @param  float  $marketingSpend   Total spend in the acquisition period
     * @param  int    $acquiredUsers    Users acquired in that period
     * @param  int    $retainedDay30    Users still active at day 30
     */
    public function cacEfficiency(int $creatorAppId, float $marketingSpend, int $acquiredUsers, int $retainedDay30): array
    {
        if ($acquiredUsers === 0) {
            return [
                'cac'                  => null,
                'cost_per_retained'    => null,
                'day_30_retention_pct' => 0.0,
            ];
        }

        $cac              = round($marketingSpend / $acquiredUsers, 2);
        $retentionPct     = round($retainedDay30 / $acquiredUsers, 4);
        $costPerRetained  = $retainedDay30 > 0 ? round($marketingSpend / $retainedDay30, 2) : null;

        return [
            'cac'                  => $cac,
            'cost_per_retained'    => $costPerRetained,
            'day_30_retention_pct' => $retentionPct,
        ];
    }

    /**
     * Pearson correlation coefficient for two equal-length arrays.
     * Returns null if variance is zero in either series.
     *
     * @param  int[]|float[]  $x
     * @param  int[]|float[]  $y
     */
    private function pearson(array $x, array $y): ?float
    {
        $n = count($x);

        if ($n < 2 || $n !== count($y)) {
            return null;
        }

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $num  = 0.0;
        $denX = 0.0;
        $denY = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dx    = $x[$i] - $meanX;
            $dy    = $y[$i] - $meanY;
            $num  += $dx * $dy;
            $denX += $dx * $dx;
            $denY += $dy * $dy;
        }

        $den = sqrt($denX * $denY);

        return $den > 0 ? round($num / $den, 4) : null;
    }
}
