<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
|
| streaks:evaluate-daily — runs hourly so each timezone "midnight window"
|   is caught within 60 minutes of the local day rolling over.
|
| badges:evaluate-daily — runs once per day after most streak evaluations
|   have completed (03:00 UTC covers all positive-offset timezones).
|
| notifications:process — runs every 15 minutes to send pending triggers
|   with minimal latency.
|
*/

Schedule::command('streaks:evaluate-daily')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('badges:evaluate-daily')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('notifications:process')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
