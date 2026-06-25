'use client'

import { useEffect, useState } from 'react'
import { getStreaks, getBadges, CREATOR_APP_ID } from '@/lib/api'
import type { Streak, BadgesResponse } from '@/lib/types'
import StreakCard from '@/components/streaks/StreakCard'
import BadgeCard from '@/components/badges/BadgeCard'
import { useAuth } from '@/context/AuthContext'

export default function DashboardPage() {
  const { user } = useAuth()
  const [streaks, setStreaks] = useState<Streak[]>([])
  const [badges, setBadges] = useState<BadgesResponse | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!user) { setLoading(false); return }
    setLoading(true)
    Promise.all([getStreaks(user.id, CREATOR_APP_ID), getBadges(user.id, CREATOR_APP_ID)])
      .then(([s, b]) => { setStreaks(s); setBadges(b) })
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [user])

  if (!user) {
    return (
      <div className="flex h-full items-center justify-center p-8">
        <p className="text-sm text-gray-500">Sign in to view your dashboard.</p>
      </div>
    )
  }

  if (loading) return <div className="p-8 text-gray-500">Loading…</div>
  if (error) return <div className="p-8 text-red-500">{error}</div>

  return (
    <div className="space-y-8 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Dashboard</h1>

      <section>
        <h2 className="mb-4 text-base font-medium text-gray-700">
          Streaks ({streaks.length})
        </h2>
        {streaks.length === 0 ? (
          <p className="text-sm text-gray-400">No streaks yet.</p>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {streaks.map((streak) => (
              <StreakCard key={streak.id} streak={streak} />
            ))}
          </div>
        )}
      </section>

      <section>
        <h2 className="mb-4 text-base font-medium text-gray-700">
          Earned Badges ({badges?.earned.length ?? 0})
        </h2>
        {badges?.earned.length === 0 ? (
          <p className="text-sm text-gray-400">No badges earned yet.</p>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {badges?.earned.map((badge) => (
              <BadgeCard key={badge.user_badge_id} badge={badge} />
            ))}
          </div>
        )}
      </section>

      {badges?.locked && badges.locked.length > 0 && (
        <section>
          <h2 className="mb-4 text-base font-medium text-gray-700">
            Locked Badges ({badges.locked.length})
          </h2>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {badges.locked.map((badge) => (
              <div
                key={badge.badge_id}
                className="rounded-lg border border-gray-200 bg-white p-4 opacity-50"
              >
                <p className="text-2xl">🔒</p>
                <p className="mt-2 text-sm font-medium text-gray-900">{badge.name}</p>
                <p className="text-xs text-gray-500">{badge.badge_category}</p>
                <p className="mt-1 text-xs text-gray-400">{badge.description}</p>
              </div>
            ))}
          </div>
        </section>
      )}
    </div>
  )
}
