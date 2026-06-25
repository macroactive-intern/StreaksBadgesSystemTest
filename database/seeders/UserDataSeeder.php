<?php

namespace Database\Seeders;

use App\Models\ActivityEvent;
use App\Models\AnalyticsEvent;
use App\Models\BadgeDefinition;
use App\Models\ModerationItem;
use App\Models\StreakConfig;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserPreference;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserDataSeeder extends Seeder
{
    private int $appId = 1;

    public function run(): void
    {
        $userIds = User::whereIn('email', [
            'alice@example.com',
            'ben@example.com',
            'carla@example.com',
            'david@example.com',
        ])->pluck('id')->all();

        $this->clearUserData($userIds);
        $this->seedStreakConfigs();

        $users = User::whereIn('id', $userIds)->get()->keyBy('email');

        $this->seedAlice($users['alice@example.com']->id);
        $this->seedBen($users['ben@example.com']->id);
        $this->seedCarla($users['carla@example.com']->id);
        $this->seedDavid($users['david@example.com']->id);

        $this->seedModerationItems($userIds);
    }

    // -------------------------------------------------------------------------
    // Clear
    // -------------------------------------------------------------------------

    private function clearUserData(array $userIds): void
    {
        DB::table('user_streaks')->whereIn('user_id', $userIds)->delete();
        DB::table('user_badges')->whereIn('user_id', $userIds)->delete();
        DB::table('user_preferences')->whereIn('user_id', $userIds)->delete();
        DB::table('activity_events')->whereIn('user_id', $userIds)->delete();
        DB::table('analytics_events')->whereIn('user_id', $userIds)->delete();
        DB::table('moderation_items')->whereIn('user_id', $userIds)->delete();
    }

    // -------------------------------------------------------------------------
    // Streak configs
    // -------------------------------------------------------------------------

    private function seedStreakConfigs(): void
    {
        foreach ([
            ['streak_type' => 'workout_completion',      'qualifying_event_type' => 'workout_completed'],
            ['streak_type' => 'nutrition_log',           'qualifying_event_type' => 'nutrition_logged'],
            ['streak_type' => 'habit_completion',        'qualifying_event_type' => 'habit_completed'],
            ['streak_type' => 'community_participation', 'qualifying_event_type' => 'community_comment_posted'],
        ] as $c) {
            StreakConfig::updateOrCreate(
                ['creator_app_id' => $this->appId, 'streak_type' => $c['streak_type']],
                ['qualifying_event_type' => $c['qualifying_event_type'], 'enabled' => true],
            );
        }
    }

    // -------------------------------------------------------------------------
    // Alice — consistent mid-tier performer, 45-day workout streak
    // -------------------------------------------------------------------------

    private function seedAlice(int $uid): void
    {
        $this->streak($uid, 'workout_completion',      45, 52, 'active',  1);
        $this->streak($uid, 'nutrition_log',           15, 21, 'active',  1);
        $this->streak($uid, 'habit_completion',        8,  14, 'active',  1);
        $this->streak($uid, 'community_participation', 3,  9,  'at_risk', 2);

        $this->pref($uid, 'AliceN', true);

        // Activity — 45 days of workouts, 15 nutrition logs, 7 habit completions this week
        for ($i = 0; $i < 45; $i++) {
            $this->event($uid, 'workout_completed',  $i, "aw-{$uid}-{$i}", ['volume_lifted' => 1800 + rand(-200, 300)]);
        }
        for ($i = 0; $i < 15; $i++) {
            $this->event($uid, 'nutrition_logged', $i, "an-{$uid}-{$i}");
        }
        for ($i = 0; $i < 8; $i++) {
            $this->event($uid, 'habit_completed', $i, "ah-{$uid}-{$i}");
        }
        for ($i = 0; $i < 12; $i++) {
            $this->event($uid, 'community_comment_posted', $i + 3, "ac-{$uid}-{$i}");
        }

        // Analytics
        $this->analytics($uid, AnalyticsEvent::STREAK_STARTED,    60, ['streak_type' => 'workout_completion']);
        $this->analytics($uid, AnalyticsEvent::STREAK_CONTINUED,  30, ['streak_type' => 'workout_completion', 'day' => 15]);
        $this->analytics($uid, AnalyticsEvent::BADGE_EARNED,      20, ['badge' => '7-Day Consistency']);
        $this->analytics($uid, AnalyticsEvent::STREAK_BROKEN,     55, ['streak_type' => 'workout_completion']);
        $this->analytics($uid, AnalyticsEvent::STREAK_STARTED,    44, ['streak_type' => 'workout_completion']);

        $this->badges($uid, [
            '7-Day Consistency',
            'Challenge Complete',
            'Nutrition Milestone',
            'Habit Champion',
            '5-Day Challenge Finisher',
        ], [30, 20, 14, 10, 5]);
    }

    // -------------------------------------------------------------------------
    // Ben — improving; broke nutrition streak, strong habit streak
    // -------------------------------------------------------------------------

    private function seedBen(int $uid): void
    {
        $this->streak($uid, 'workout_completion', 12, 28, 'active', 1);
        $this->streak($uid, 'nutrition_log',       0,  7, 'broken', 6);
        $this->streak($uid, 'habit_completion',   22, 22, 'active', 1);

        $this->pref($uid, 'BenC', true);

        for ($i = 0; $i < 12; $i++) {
            $this->event($uid, 'workout_completed', $i, "bw-{$uid}-{$i}", ['volume_lifted' => 1200 + rand(-100, 200)]);
        }
        for ($i = 0; $i < 22; $i++) {
            $this->event($uid, 'habit_completed', $i, "bh-{$uid}-{$i}");
        }
        for ($i = 7; $i < 14; $i++) {
            $this->event($uid, 'nutrition_logged', $i, "bn-{$uid}-{$i}");
        }
        for ($i = 0; $i < 5; $i++) {
            $this->event($uid, 'community_comment_posted', $i + 1, "bc-{$uid}-{$i}");
        }

        $this->analytics($uid, AnalyticsEvent::STREAK_STARTED,    25, ['streak_type' => 'habit_completion']);
        $this->analytics($uid, AnalyticsEvent::STREAK_BROKEN,     6,  ['streak_type' => 'nutrition_log', 'lost_days' => 7]);
        $this->analytics($uid, AnalyticsEvent::BADGE_EARNED,      15, ['badge' => '7-Day Consistency']);

        $this->badges($uid, [
            '7-Day Consistency',
            'Challenge Complete',
            'Transformation Champion',
            'Habit Champion',
        ], [15, 12, 8, 4]);
    }

    // -------------------------------------------------------------------------
    // Carla — beginner, short streaks, just getting started
    // -------------------------------------------------------------------------

    private function seedCarla(int $uid): void
    {
        $this->streak($uid, 'workout_completion', 5,  14, 'at_risk', 2);
        $this->streak($uid, 'habit_completion',   2,   2, 'active',  1);

        $this->pref($uid, 'CarlaR', true);

        for ($i = 2; $i < 7; $i++) {
            $this->event($uid, 'workout_completed', $i, "cw-{$uid}-{$i}", ['volume_lifted' => 600 + rand(-50, 100)]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->event($uid, 'habit_completed', $i, "ch-{$uid}-{$i}");
        }
        for ($i = 0; $i < 3; $i++) {
            $this->event($uid, 'community_comment_posted', $i + 1, "cc-{$uid}-{$i}");
        }

        $this->analytics($uid, AnalyticsEvent::STREAK_STARTED, 14, ['streak_type' => 'workout_completion']);
        $this->analytics($uid, AnalyticsEvent::STREAK_BROKEN,  8,  ['streak_type' => 'workout_completion', 'lost_days' => 14]);
        $this->analytics($uid, AnalyticsEvent::STREAK_STARTED, 4,  ['streak_type' => 'workout_completion']);

        $this->badges($uid, [
            'Challenge Complete',
        ], [3]);
    }

    // -------------------------------------------------------------------------
    // David — elite performer, 90-day streak, top of every board
    // -------------------------------------------------------------------------

    private function seedDavid(int $uid): void
    {
        $this->streak($uid, 'workout_completion',      90, 90, 'active', 1);
        $this->streak($uid, 'nutrition_log',           45, 45, 'active', 1);
        $this->streak($uid, 'habit_completion',        60, 60, 'active', 1);
        $this->streak($uid, 'community_participation', 30, 30, 'active', 1);

        $this->pref($uid, 'DavidK', true);

        for ($i = 0; $i < 60; $i++) {
            $this->event($uid, 'workout_completed', $i, "dw-{$uid}-{$i}", ['volume_lifted' => 2800 + rand(-200, 400)]);
        }
        for ($i = 0; $i < 45; $i++) {
            $this->event($uid, 'nutrition_logged', $i, "dn-{$uid}-{$i}");
        }
        for ($i = 0; $i < 60; $i++) {
            $this->event($uid, 'habit_completed', $i, "dh-{$uid}-{$i}");
        }
        for ($i = 0; $i < 30; $i++) {
            $this->event($uid, 'community_comment_posted', $i, "dc-{$uid}-{$i}");
        }

        $this->analytics($uid, AnalyticsEvent::STREAK_STARTED,    91, ['streak_type' => 'workout_completion']);
        $this->analytics($uid, AnalyticsEvent::STREAK_CONTINUED,  60, ['streak_type' => 'workout_completion', 'day' => 30]);
        $this->analytics($uid, AnalyticsEvent::STREAK_CONTINUED,  30, ['streak_type' => 'workout_completion', 'day' => 60]);
        $this->analytics($uid, AnalyticsEvent::BADGE_EARNED,      30, ['badge' => '30-Day Machine']);
        $this->analytics($uid, AnalyticsEvent::BADGE_EARNED,       1, ['badge' => '90-Day Elite']);

        $this->badges($uid, [
            '7-Day Consistency',
            '30-Day Machine',
            '90-Day Elite',
            'Challenge Complete',
            '5-Day Challenge Finisher',
            'Transformation Champion',
            'Program Completion Certificate',
            'Phase Completion Badge',
            'Accountability Leader',
            'Habit Champion',
            'Nutrition Milestone',
        ], [89, 60, 1, 50, 40, 35, 20, 18, 25, 55, 44]);
    }

    // -------------------------------------------------------------------------
    // Moderation items
    // -------------------------------------------------------------------------

    private function seedModerationItems(array $userIds): void
    {
        $items = [
            ['user_id' => $userIds[1], 'detection_type' => 'rapid_event_burst',   'severity' => 'medium', 'status' => 'pending',   'days_ago' => 1,  'payload' => ['events_in_window' => 42, 'window_minutes' => 5]],
            ['user_id' => $userIds[2], 'detection_type' => 'impossible_volume',   'severity' => 'high',   'status' => 'pending',   'days_ago' => 2,  'payload' => ['reported_volume_kg' => 99999]],
            ['user_id' => $userIds[0], 'detection_type' => 'duplicate_source_id', 'severity' => 'low',    'status' => 'resolved',  'days_ago' => 10, 'payload' => ['source_id' => 'aw-1-0', 'count' => 2]],
            ['user_id' => $userIds[3], 'detection_type' => 'rapid_event_burst',   'severity' => 'low',    'status' => 'dismissed', 'days_ago' => 5,  'payload' => ['events_in_window' => 18, 'window_minutes' => 10]],
            ['user_id' => $userIds[1], 'detection_type' => 'impossible_volume',   'severity' => 'medium', 'status' => 'pending',   'days_ago' => 0,  'payload' => ['reported_volume_kg' => 8500]],
        ];

        foreach ($items as $item) {
            ModerationItem::create([
                'user_id'        => $item['user_id'],
                'creator_app_id' => $this->appId,
                'detection_type' => $item['detection_type'],
                'severity'       => $item['severity'],
                'payload'        => $item['payload'],
                'status'         => $item['status'],
                'created_at'     => now()->subDays($item['days_ago']),
                'updated_at'     => now()->subDays($item['days_ago']),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function streak(int $uid, string $type, int $current, int $longest, string $status, int $lastDaysAgo): void
    {
        UserStreak::create([
            'user_id'             => $uid,
            'creator_app_id'      => $this->appId,
            'streak_type'         => $type,
            'current_count'       => $current,
            'longest_count'       => $longest,
            'status'              => $status,
            'last_completed_date' => now()->subDays($lastDaysAgo),
            'last_evaluated_date' => now(),
        ]);
    }

    private function pref(int $uid, string $nickname, bool $visible): void
    {
        UserPreference::create([
            'user_id'              => $uid,
            'creator_app_id'       => $this->appId,
            'leaderboard_nickname' => $nickname,
            'leaderboard_visible'  => $visible,
        ]);
    }

    private function event(int $uid, string $type, int $daysAgo, string $sourceId, array $metadata = []): void
    {
        $date = Carbon::today()->subDays($daysAgo);
        ActivityEvent::create([
            'user_id'             => $uid,
            'creator_app_id'      => $this->appId,
            'event_type'          => $type,
            'event_timestamp_utc' => $date->copy()->setHour(rand(7, 20)),
            'user_timezone'       => 'UTC',
            'local_event_date'    => $date->toDateString(),
            'metadata'            => $metadata,
            'source_type'         => 'seed',
            'source_id'           => $sourceId,
        ]);
    }

    private function analytics(int $uid, string $type, int $daysAgo, array $payload = []): void
    {
        AnalyticsEvent::create([
            'creator_app_id' => $this->appId,
            'user_id'        => $uid,
            'event_type'     => $type,
            'payload'        => $payload,
            'occurred_at'    => now()->subDays($daysAgo),
        ]);
    }

    private function badges(int $uid, array $names, array $daysAgoEach): void
    {
        foreach ($names as $i => $name) {
            $def = BadgeDefinition::where('name', $name)->first();
            if (!$def) continue;
            UserBadge::create([
                'user_id'              => $uid,
                'creator_app_id'       => $this->appId,
                'badge_definition_id'  => $def->id,
                'earned_at'            => now()->subDays($daysAgoEach[$i] ?? 1),
                'privacy_hidden'       => false,
                'is_featured'          => $i === 0,
            ]);
        }
    }
}
