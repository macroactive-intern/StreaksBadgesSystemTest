'use client'

import { useEffect } from 'react'
import type { Streak } from '@/lib/types'

interface Props {
  streaks: Streak[]
  onCheckIn: () => void
  onDismiss: () => void
  loading?: boolean
}

export default function CheckInNotification({ streaks, onCheckIn, onDismiss, loading }: Props) {
  useEffect(() => {
    const handleCheckinComplete = () => onDismiss()
    window.addEventListener('checkin:complete', handleCheckinComplete)
    return () => window.removeEventListener('checkin:complete', handleCheckinComplete)
  }, [onDismiss])

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center p-4">
      <div
        className="absolute inset-0 bg-black/30"
        onClick={onDismiss}
        aria-hidden="true"
      />
      <div className="relative z-10 w-full max-w-sm rounded-xl bg-white shadow-xl border border-gray-200 p-6">
        <div className="mb-4">
          <p className="text-base font-semibold text-gray-900">Time to check in!</p>
          <p className="mt-1 text-sm text-gray-500">
            {streaks.length === 1
              ? `Your "${streaks[0].streak_type}" streak is waiting.`
              : `${streaks.length} streaks are waiting for today's activity.`}
          </p>
        </div>

        {streaks.length > 1 && (
          <ul className="mb-4 space-y-1">
            {streaks.map((s) => (
              <li key={s.id} className="flex items-center gap-2 text-sm text-gray-700">
                <span className="text-orange-500">●</span>
                <span className="capitalize">{s.streak_type.replace(/_/g, ' ')}</span>
                <span className="ml-auto text-xs text-gray-400">{s.current_streak} day streak</span>
              </li>
            ))}
          </ul>
        )}

        <div className="flex gap-3">
          <button
            onClick={onCheckIn}
            disabled={loading}
            className="flex-1 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
          >
            {loading ? 'Checking in…' : 'Check In Now'}
          </button>
          <button
            onClick={onDismiss}
            className="rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-500 hover:text-gray-900"
          >
            Later
          </button>
        </div>
      </div>
    </div>
  )
}
