'use client'

import { useEffect, useState } from 'react'
import { getAnalytics, getEngagement, CREATOR_APP_ID } from '@/lib/api'
import type { AnalyticsSummary, EngagementSummary } from '@/lib/types'

function MetricCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4">
      <p className="text-xs font-medium uppercase tracking-wider text-gray-500">{label}</p>
      <p className="mt-2 text-2xl font-semibold text-gray-900">{value}</p>
    </div>
  )
}

const pct = (n: number) => `${(n * 100).toFixed(1)}%`

export default function AnalyticsPage() {
  const [analytics, setAnalytics] = useState<AnalyticsSummary | null>(null)
  const [engagement, setEngagement] = useState<EngagementSummary | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    Promise.all([getAnalytics(CREATOR_APP_ID), getEngagement(CREATOR_APP_ID)])
      .then(([a, e]) => {
        setAnalytics(a)
        setEngagement(e)
      })
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="p-8 text-gray-500">Loading…</div>
  if (error) return <div className="p-8 text-red-500">{error}</div>

  return (
    <div className="space-y-8 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Analytics</h1>

      {analytics && (
        <section>
          <h2 className="mb-4 text-base font-medium text-gray-700">{analytics.date}</h2>
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <MetricCard label="Daily Active Users" value={analytics.daily_active_users.toLocaleString()} />
            <MetricCard label="Habit Completion" value={pct(analytics.habit_completion_rate)} />
            <MetricCard label="Community Rate" value={pct(analytics.community_participation_rate)} />
            <MetricCard label="Active Streaks" value={pct(analytics.active_streak_percentage)} />
            <MetricCard label="Badge Earn Rate" value={pct(analytics.badge_earn_rate)} />
          </div>
        </section>
      )}

      {engagement && engagement.users_at_risk.length > 0 && (
        <section>
          <h2 className="mb-4 text-base font-medium text-gray-700">
            At-Risk Streaks ({engagement.users_at_risk.length})
          </h2>
          <div className="overflow-hidden rounded-lg border border-gray-200">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Streak</th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Days</th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Last Active</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 bg-white">
                {engagement.users_at_risk.map((u) => (
                  <tr key={`${u.user_id}-${u.streak_type}`}>
                    <td className="px-4 py-3 text-sm text-gray-900">#{u.user_id}</td>
                    <td className="px-4 py-3 text-sm text-gray-600">{u.streak_type}</td>
                    <td className="px-4 py-3 text-right font-mono text-sm text-gray-900">{u.current_count}</td>
                    <td className="px-4 py-3 text-right text-sm text-gray-500">{u.last_completed_date}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {engagement && engagement.top_engaged_members.length > 0 && (
        <section>
          <h2 className="mb-4 text-base font-medium text-gray-700">Top Engaged Members</h2>
          <div className="overflow-hidden rounded-lg border border-gray-200">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Events</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 bg-white">
                {engagement.top_engaged_members.map((m) => (
                  <tr key={m.user_id}>
                    <td className="px-4 py-3 text-sm text-gray-900">#{m.user_id}</td>
                    <td className="px-4 py-3 text-right font-mono text-sm text-gray-900">{m.event_count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}
    </div>
  )
}
