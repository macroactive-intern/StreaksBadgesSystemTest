export type StreakType =
  | 'workout_completion'
  | 'nutrition_log'
  | 'habit_completion'
  | 'community_participation'

export type StreakStatus = 'active' | 'at_risk' | 'broken'

export type LeaderboardType = 'weekly_workout' | 'monthly_streak' | 'volume_lifted'

export type EventType =
  | 'workout_completed'
  | 'nutrition_logged'
  | 'habit_completed'
  | 'community_comment_posted'
  | 'program_completed'
  | 'challenge_completed'

export type ModerationStatus = 'pending' | 'resolved' | 'dismissed'

export interface Streak {
  id: number
  streak_type: StreakType
  current_count: number
  longest_count: number
  status: StreakStatus
  status_label: string
  last_completed_date: string | null
  next_milestone: number | null
  progress_percent: number
}

export interface EarnedBadge {
  user_badge_id: number
  badge_id: number
  name: string
  description: string
  icon: string
  badge_category: string
  earned_at: string
  awarded_by: number | null
  privacy_hidden: boolean
  is_featured: boolean
}

export interface LockedBadge {
  badge_id: number
  name: string
  description: string
  icon: string
  badge_category: string
}

export interface BadgesResponse {
  earned: EarnedBadge[]
  locked: LockedBadge[]
}

export interface LeaderboardEntry {
  rank: number
  user_id: number
  nickname: string | null
  score: number
}

export interface Leaderboard {
  type: LeaderboardType
  label: string
  entries: LeaderboardEntry[]
}

export interface UserPreferences {
  user_id: number
  creator_app_id: number
  leaderboard_nickname: string | null
  leaderboard_visible: boolean
}

export interface StreakConfig {
  id: number
  creator_app_id: number
  streak_type: StreakType
  enabled: boolean
  qualifying_event_type: string | null
  freeze_grants_per_month: number
  at_risk_grace_hours: number
  reward_config: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export interface BadgeDefinition {
  id: number
  creator_app_id: number
  name: string
  description: string
  icon: string
  badge_category: string
  rule_type: string
  rule_config: Record<string, unknown>
  enabled: boolean
  created_at: string
  updated_at: string
}

export interface EngagementSummary {
  top_engaged_members: Array<{ user_id: number; event_count: number }>
  users_with_active_streaks: Array<{
    user_id: number
    streak_type: StreakType
    current_count: number
    longest_count: number
    last_completed_date: string
  }>
  users_at_risk: Array<{
    user_id: number
    streak_type: StreakType
    current_count: number
    last_completed_date: string
  }>
  recently_broken_streaks: Array<{
    user_id: number
    streak_type: StreakType
    longest_count: number
    last_evaluated_date: string
  }>
  recent_badge_earns: Array<{
    user_id: number
    badge_definition_id: number
    earned_at: string
    awarded_by: number | null
  }>
}

export interface AnalyticsSummary {
  date: string
  daily_active_users: number
  habit_completion_rate: number
  community_participation_rate: number
  active_streak_percentage: number
  badge_earn_rate: number
}

export interface ModerationItem {
  id: number
  user_id: number
  detection_type: string
  severity: string
  payload: Record<string, unknown>
  status: ModerationStatus
  created_at: string
}
