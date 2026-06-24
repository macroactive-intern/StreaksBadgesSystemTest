import type {
  Streak,
  BadgesResponse,
  EarnedBadge,
  Leaderboard,
  LeaderboardType,
  UserPreferences,
  StreakConfig,
  BadgeDefinition,
  EngagementSummary,
  AnalyticsSummary,
  ModerationItem,
  ModerationStatus,
} from './types'

const BASE = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000'

export const USER_ID = parseInt(process.env.NEXT_PUBLIC_USER_ID ?? '1', 10)
export const CREATOR_APP_ID = parseInt(process.env.NEXT_PUBLIC_CREATOR_APP_ID ?? '1', 10)

async function get<T>(path: string, params: Record<string, string | number> = {}): Promise<T> {
  const url = new URL(path, BASE)
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, String(v)))
  const res = await fetch(url.toString(), { cache: 'no-store' })
  if (!res.ok) throw new Error(`API ${res.status}: ${path}`)
  const json = await res.json()
  return json.data as T
}

async function patch<T>(path: string, body: Record<string, unknown>): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(body),
  })
  if (!res.ok) throw new Error(`API ${res.status}: ${path}`)
  const json = await res.json()
  return json.data as T
}

// User endpoints
export const getStreaks = (userId: number, creatorAppId: number) =>
  get<Streak[]>('/api/streaks', { user_id: userId, creator_app_id: creatorAppId })

export const getBadges = (userId: number, creatorAppId: number) =>
  get<BadgesResponse>('/api/badges', { user_id: userId, creator_app_id: creatorAppId })

export const getLeaderboard = (creatorAppId: number, type: LeaderboardType) =>
  get<Leaderboard>('/api/leaderboards', { creator_app_id: creatorAppId, type })

export const getPreferences = (userId: number, creatorAppId: number) =>
  get<UserPreferences>('/api/user/preferences', { user_id: userId, creator_app_id: creatorAppId })

export const updatePreferences = (
  userId: number,
  creatorAppId: number,
  data: Partial<Omit<UserPreferences, 'user_id' | 'creator_app_id'>>,
) =>
  patch<UserPreferences>('/api/user/preferences', {
    ...data,
    user_id: userId,
    creator_app_id: creatorAppId,
  })

export const setBadgeVisibility = (userBadgeId: number, userId: number, hidden: boolean) =>
  patch<EarnedBadge>(`/api/badges/${userBadgeId}/visibility`, { user_id: userId, hidden })

export const setBadgeFeatured = (userBadgeId: number, userId: number, unfeature = false) =>
  patch<EarnedBadge>(`/api/badges/${userBadgeId}/feature`, {
    user_id: userId,
    ...(unfeature && { unfeature: true }),
  })

// Creator endpoints
export const getStreakConfigs = (creatorAppId: number) =>
  get<StreakConfig[]>('/api/creator/streak-config', { creator_app_id: creatorAppId })

export const updateStreakConfig = (data: Record<string, unknown>) =>
  patch<StreakConfig>('/api/creator/streak-config', data)

export const getBadgeConfigs = (creatorAppId: number) =>
  get<BadgeDefinition[]>('/api/creator/badge-config', { creator_app_id: creatorAppId })

export const updateBadgeConfig = (data: Record<string, unknown>) =>
  patch<BadgeDefinition>('/api/creator/badge-config', data)

export const getEngagement = (creatorAppId: number) =>
  get<EngagementSummary>('/api/creator/engagement', { creator_app_id: creatorAppId })

export const getAnalytics = (creatorAppId: number, date?: string) =>
  get<AnalyticsSummary>(
    '/api/creator/analytics',
    date ? { creator_app_id: creatorAppId, date } : { creator_app_id: creatorAppId },
  )

export const getModerationQueue = (creatorAppId: number, status: ModerationStatus = 'pending') =>
  get<ModerationItem[]>('/api/creator/moderation/queue', { creator_app_id: creatorAppId, status })

export const reviewModerationItem = (itemId: number, data: Record<string, unknown>) =>
  patch<ModerationItem>(`/api/creator/moderation/${itemId}`, data)
