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

export const CREATOR_APP_ID = parseInt(process.env.NEXT_PUBLIC_CREATOR_APP_ID ?? '1', 10)

// ---------------------------------------------------------------------------
// Token helpers — read/write from localStorage on the client only
// ---------------------------------------------------------------------------

export const TOKEN_KEY = 'ma_auth_token'

function getToken(): string | null {
  if (typeof window === 'undefined') return null
  try { return localStorage.getItem(TOKEN_KEY) } catch { return null }
}

function authHeaders(): Record<string, string> {
  const token = getToken()
  return token ? { Authorization: `Bearer ${token}` } : {}
}

// ---------------------------------------------------------------------------
// Base fetch helpers
// ---------------------------------------------------------------------------

async function request<T>(
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  path: string,
  body?: Record<string, unknown>,
  params: Record<string, string | number> = {},
): Promise<T> {
  const url = new URL(path, BASE)
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, String(v)))

  const res = await fetch(url.toString(), {
    method,
    headers: {
      ...authHeaders(),
      Accept: 'application/json',
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
    cache: 'no-store',
  })

  if (res.status === 401) {
    // Token expired or revoked — notify the app so AuthContext can clear state.
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new Event('auth:unauthorized'))
    }
    throw new Error('Unauthorized')
  }

  if (!res.ok) {
    const json = await res.json().catch(() => ({}))
    throw new Error((json as { message?: string }).message ?? `API ${res.status}: ${path}`)
  }

  if (res.status === 204) return undefined as T
  const json = await res.json()
  return json.data as T
}

const get  = <T>(path: string, params?: Record<string, string | number>) => request<T>('GET',   path, undefined, params)
const post = <T>(path: string, body: Record<string, unknown> = {})       => request<T>('POST',  path, body)
const patch = <T>(path: string, body: Record<string, unknown>)            => request<T>('PATCH', path, body)

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

export interface LoginResponse {
  token: string
  user: { id: number; name: string; email: string }
}

export const loginRequest  = (email: string, password: string) =>
  post<LoginResponse>('/api/auth/login', { email, password })

export const logoutRequest = () => post<void>('/api/auth/logout')

// ---------------------------------------------------------------------------
// User endpoints
// ---------------------------------------------------------------------------

export const USER_ID = 1  // kept as a type-level default; pages must not use it for real requests

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

// user_id is no longer sent — the backend derives it from the Bearer token
export const setBadgeVisibility = (userBadgeId: number, hidden: boolean) =>
  patch<EarnedBadge>(`/api/badges/${userBadgeId}/visibility`, { hidden })

export const setBadgeFeatured = (userBadgeId: number, unfeature = false) =>
  patch<EarnedBadge>(`/api/badges/${userBadgeId}/feature`, {
    ...(unfeature && { unfeature: true }),
  })

export interface CheckInResult {
  event_id: number
  local_date: string
  new_badges: EarnedBadge[]
}

export const recordCheckIn = (userId: number, creatorAppId: number) =>
  post<CheckInResult>('/api/events', {
    user_id: userId,
    creator_app_id: creatorAppId,
    event_type: 'habit_completed',
    event_timestamp_utc: new Date().toISOString(),
    user_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
  })

// ---------------------------------------------------------------------------
// Creator endpoints
// ---------------------------------------------------------------------------

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
