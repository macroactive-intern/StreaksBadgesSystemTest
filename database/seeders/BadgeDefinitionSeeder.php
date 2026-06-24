<?php

namespace Database\Seeders;

use App\Models\BadgeDefinition;
use Illuminate\Database\Seeder;

class BadgeDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->badges() as $badge) {
            BadgeDefinition::updateOrCreate(
                ['name' => $badge['name'], 'creator_app_id' => null],
                $badge,
            );
        }
    }

    private function badges(): array
    {
        return [

            // ------------------------------------------------------------------
            // Consistency badges — streak based
            // ------------------------------------------------------------------
            [
                'creator_app_id' => null,
                'name'           => '7-Day Consistency',
                'description'    => 'Complete your workout for 7 consecutive days.',
                'badge_category' => 'consistency',
                'icon'           => 'badge-7-day-consistency',
                'rule_type'      => 'streak',
                'rule_config'    => ['streak_type' => 'workout_completion', 'min_streak_days' => 7],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => '30-Day Machine',
                'description'    => 'Maintain a workout streak for 30 consecutive days.',
                'badge_category' => 'consistency',
                'icon'           => 'badge-30-day-machine',
                'rule_type'      => 'streak',
                'rule_config'    => ['streak_type' => 'workout_completion', 'min_streak_days' => 30],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => '90-Day Elite',
                'description'    => 'Sustain a 90-day workout streak — elite-level dedication.',
                'badge_category' => 'consistency',
                'icon'           => 'badge-90-day-elite',
                'rule_type'      => 'streak',
                'rule_config'    => ['streak_type' => 'workout_completion', 'min_streak_days' => 90],
                'enabled'        => true,
            ],

            // ------------------------------------------------------------------
            // Milestone badges — cumulative event counts / metrics
            // ------------------------------------------------------------------
            [
                'creator_app_id' => null,
                'name'           => '100 Workouts Completed',
                'description'    => 'Log 100 completed workouts.',
                'badge_category' => 'milestone',
                'icon'           => 'badge-100-workouts',
                'rule_type'      => 'milestone',
                'rule_config'    => ['event_type' => 'workout_completed', 'count' => 100],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Iron Will',
                'description'    => 'Lift a cumulative total of 100,000 kg across all workouts.',
                'badge_category' => 'milestone',
                'icon'           => 'badge-iron-will',
                'rule_type'      => 'milestone',
                'rule_config'    => ['event_type' => 'workout_completed', 'metric' => 'volume_lifted', 'min_total' => 100000],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Nutrition Milestone',
                'description'    => 'Log 30 nutrition entries.',
                'badge_category' => 'milestone',
                'icon'           => 'badge-nutrition-milestone',
                'rule_type'      => 'milestone',
                'rule_config'    => ['event_type' => 'nutrition_logged', 'count' => 30],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Habit Champion',
                'description'    => 'Complete 30 habit check-ins.',
                'badge_category' => 'milestone',
                'icon'           => 'badge-habit-champion',
                'rule_type'      => 'milestone',
                'rule_config'    => ['event_type' => 'habit_completed', 'count' => 30],
                'enabled'        => true,
            ],

            // ------------------------------------------------------------------
            // Challenge badges — challenge completion counts
            // ------------------------------------------------------------------
            [
                'creator_app_id' => null,
                'name'           => 'Challenge Complete',
                'description'    => 'Finish your first challenge.',
                'badge_category' => 'challenge',
                'icon'           => 'badge-challenge-complete',
                'rule_type'      => 'challenge',
                'rule_config'    => ['event_type' => 'challenge_completed', 'count' => 1],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => '5-Day Challenge Finisher',
                'description'    => 'Complete 5 challenges in total.',
                'badge_category' => 'challenge',
                'icon'           => 'badge-5-day-challenge-finisher',
                'rule_type'      => 'challenge',
                'rule_config'    => ['event_type' => 'challenge_completed', 'count' => 5],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Transformation Champion',
                'description'    => 'Complete 3 or more challenges, demonstrating full commitment to transformation.',
                'badge_category' => 'challenge',
                'icon'           => 'badge-transformation-champion',
                'rule_type'      => 'challenge',
                'rule_config'    => ['event_type' => 'challenge_completed', 'count' => 3],
                'enabled'        => true,
            ],

            // ------------------------------------------------------------------
            // Certification badges — program completion
            // ------------------------------------------------------------------
            [
                'creator_app_id' => null,
                'name'           => 'Program Completion Certificate',
                'description'    => 'Successfully complete a full program.',
                'badge_category' => 'certification',
                'icon'           => 'badge-program-certificate',
                'rule_type'      => 'certification',
                'rule_config'    => ['event_type' => 'program_completed', 'count' => 1],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Phase Completion Badge',
                'description'    => 'Complete a structured phase within a program.',
                'badge_category' => 'certification',
                'icon'           => 'badge-phase-completion',
                'rule_type'      => 'certification',
                'rule_config'    => ['event_type' => 'program_completed', 'count' => 1],
                'enabled'        => true,
            ],

            // ------------------------------------------------------------------
            // Community status badges
            // ------------------------------------------------------------------
            [
                'creator_app_id' => null,
                'name'           => '50 Comments Posted',
                'description'    => 'Contribute 50 comments to the community.',
                'badge_category' => 'community',
                'icon'           => 'badge-50-comments',
                'rule_type'      => 'community',
                'rule_config'    => ['event_type' => 'community_comment_posted', 'count' => 50, 'unique_sources' => true],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Top Contributor',
                'description'    => 'Post 100 community comments.',
                'badge_category' => 'community',
                'icon'           => 'badge-top-contributor',
                'rule_type'      => 'community',
                'rule_config'    => ['event_type' => 'community_comment_posted', 'count' => 100, 'unique_sources' => true],
                'enabled'        => true,
            ],
            [
                'creator_app_id' => null,
                'name'           => 'Accountability Leader',
                'description'    => 'Stay active in the community for 7 consecutive days.',
                'badge_category' => 'community',
                'icon'           => 'badge-accountability-leader',
                'rule_type'      => 'streak',
                'rule_config'    => ['streak_type' => 'community_participation', 'min_streak_days' => 7],
                'enabled'        => true,
            ],

        ];
    }
}
