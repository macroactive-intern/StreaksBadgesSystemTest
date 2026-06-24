<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pilot Program Configuration (13.3)
    |--------------------------------------------------------------------------
    |
    | List the creator_app_ids enrolled in the pilot program and the control
    | group IDs used for cohort comparison.
    |
    | Retention windows (days) define which day cohorts are measured at.
    |
    */
    'pilot_creator_ids'   => env('ANALYTICS_PILOT_CREATOR_IDS', ''),   // comma-separated
    'control_creator_ids' => env('ANALYTICS_CONTROL_CREATOR_IDS', ''), // comma-separated

    'retention_windows' => [7, 30],

    /*
    |--------------------------------------------------------------------------
    | Metric defaults
    |--------------------------------------------------------------------------
    */
    'dau_lookback_hours'  => 24,
    'earn_rate_days'      => 30,
];
