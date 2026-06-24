<?php

use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\BadgeVisibilityController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\StreakController;
use App\Http\Controllers\Api\Creator\BadgeConfigController;
use App\Http\Controllers\Api\Creator\EngagementController;
use App\Http\Controllers\Api\Creator\EventAdminController;
use App\Http\Controllers\Api\Creator\StreakConfigController;
use Illuminate\Support\Facades\Route;

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
});
