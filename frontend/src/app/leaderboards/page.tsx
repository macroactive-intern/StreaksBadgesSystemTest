'use client'

import { useEffect, useState } from 'react'
import { getLeaderboard, CREATOR_APP_ID } from '@/lib/api'
import type { Leaderboard, LeaderboardType } from '@/lib/types'
import LeaderboardTable from '@/components/leaderboards/LeaderboardTable'
import { useAuth } from '@/context/AuthContext'

const TYPES: { value: LeaderboardType; label: string }[] = [
  { value: 'weekly_workout', label: 'Weekly Workout' },
  { value: 'monthly_streak', label: 'Monthly Streak' },
  { value: 'volume_lifted', label: 'Volume Lifted' },
]

export default function LeaderboardsPage() {
  const { user } = useAuth()
  const [activeType, setActiveType] = useState<LeaderboardType>('weekly_workout')
  const [board, setBoard] = useState<Leaderboard | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    setLoading(true)
    setError(null)
    getLeaderboard(CREATOR_APP_ID, activeType)
      .then(setBoard)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [activeType])

  return (
    <div className="space-y-6 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Leaderboards</h1>

      <div className="flex flex-wrap gap-2">
        {TYPES.map(({ value, label }) => (
          <button
            key={value}
            onClick={() => setActiveType(value)}
            className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
              activeType === value
                ? 'bg-gray-900 text-white'
                : 'border border-gray-200 bg-white text-gray-600 hover:border-gray-400'
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {loading && <p className="text-gray-500">Loading…</p>}
      {error && <p className="text-red-500">{error}</p>}
      {!loading && !error && board && (
        <>
          <p className="text-sm text-gray-500">{board.label}</p>
          <LeaderboardTable entries={board.entries} currentUserId={user?.id} />
        </>
      )}
    </div>
  )
}
