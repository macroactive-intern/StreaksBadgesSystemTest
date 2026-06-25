<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\BadgeVisibilityController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\StreakController;
use App\Http\Controllers\Api\Creator\AdvancedAnalyticsController;
use App\Http\Controllers\Api\Creator\AnalyticsController;
use App\Http\Controllers\Api\Creator\BadgeConfigController;
use App\Http\Controllers\Api\Creator\EngagementController;
use App\Http\Controllers\Api\Creator\EventAdminController;
use App\Http\Controllers\Api\Creator\ModerationController;
use App\Http\Controllers\Api\Creator\StreakConfigController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\UserPreferencesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| End-User Endpoints
|--------------------------------------------------------------------------
*/

// 8.1 Get user streak summary
Route::get('/streaks', [StreakController::class, 'index']);

// 8.2 Get user badges
Route::get('/badges', [BadgeController::class, 'index']);

// 8.3 Record qualifying event
Route::post('/events', [EventController::class, 'store']);

// 8.4 Use streak freeze
Route::post('/streaks/{streak}/freeze', [StreakController::class, 'freeze']);

// 12.1 Badge visibility controls
Route::patch('/badges/{badge}/visibility', [BadgeVisibilityController::class, 'setVisibility']);
Route::patch('/badges/{badge}/feature', [BadgeVisibilityController::class, 'setFeatured']);

// 15.1 Leaderboards
Route::get('/leaderboards', [LeaderboardController::class, 'index']);
Route::get('/leaderboards/challenge/{challenge}', [LeaderboardController::class, 'challenge']);

// 15.1 User preferences (nickname, opt-in visibility)
Route::get('/user/preferences', [UserPreferencesController::class, 'show']);
Route::patch('/user/preferences', [UserPreferencesController::class, 'update']);

/*
|--------------------------------------------------------------------------
| Creator / Admin Endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('creator')->group(function () {
    // 8.5 Get creator streak config
    Route::get('/streak-config', [StreakConfigController::class, 'index']);

    // 8.6 Update creator streak config
    Route::patch('/streak-config', [StreakConfigController::class, 'update']);

    // 8.7 Get creator badge config
    Route::get('/badge-config', [BadgeConfigController::class, 'index']);

    // 8.8 Update creator badge config
    Route::patch('/badge-config', [BadgeConfigController::class, 'update']);

    // 8.9 Manually award badge to a user
    Route::post('/users/{user}/badges', [BadgeConfigController::class, 'awardBadge']);

    // 8.10 Revoke a badge from a user
    Route::delete('/users/{user}/badges/{badge}', [BadgeConfigController::class, 'revokeBadge']);

    // 8.11 Get engagement summary
    Route::get('/engagement', [EngagementController::class, 'index']);

    // 11.3 Revoke activity events when source content is deleted
    Route::delete('/events/source', [EventAdminController::class, 'revokeBySource']);

    // 13.2 Engagement metrics summary
    Route::get('/analytics', [AnalyticsController::class, 'summary']);

    // 13.3 Pilot cohort comparison report
    Route::get('/pilot/report', [AnalyticsController::class, 'pilotReport']);

    // 15.3 Multi-window cohort retention
    Route::get('/analytics/cohort', [AdvancedAnalyticsController::class, 'cohortRetention']);

    // 15.3 LTV correlation (caller supplies revenue data)
    Route::post('/analytics/ltv', [AdvancedAnalyticsController::class, 'ltvCorrelation']);

    // 15.3 Data warehouse batch export
    Route::get('/analytics/export', [AdvancedAnalyticsController::class, 'exportBatch']);

    // 15.4 Moderation review queue
    Route::get('/moderation/queue', [ModerationController::class, 'index']);
    Route::patch('/moderation/{item}', [ModerationController::class, 'review']);
});
