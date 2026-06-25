'use client'

import { useState } from 'react'
import { setBadgeVisibility, setBadgeFeatured } from '@/lib/api'
import type { EarnedBadge } from '@/lib/types'

export default function BadgeCard({ badge: initial }: { badge: EarnedBadge }) {
  const [badge, setBadge] = useState(initial)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const toggleVisibility = async () => {
    setBusy(true)
    setError(null)
    try {
      const updated = await setBadgeVisibility(badge.user_badge_id, !badge.privacy_hidden)
      setBadge(updated)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Failed')
    } finally {
      setBusy(false)
    }
  }

  const toggleFeatured = async () => {
    setBusy(true)
    setError(null)
    try {
      const updated = await setBadgeFeatured(badge.user_badge_id, badge.is_featured)
      setBadge(updated)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Failed')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div
      className={`rounded-lg border border-gray-200 bg-white p-4 transition-opacity ${
        badge.privacy_hidden ? 'opacity-60' : ''
      }`}
    >
      <p className="text-2xl">{badge.icon || '🏅'}</p>
      <p className="mt-2 text-sm font-medium text-gray-900">{badge.name}</p>
      <p className="text-xs text-gray-500">{badge.badge_category}</p>
      <p className="mt-1 line-clamp-2 text-xs text-gray-400">{badge.description}</p>
      <p className="mt-2 text-xs text-gray-400">
        Earned {new Date(badge.earned_at).toLocaleDateString()}
      </p>

      {error && <p className="mt-2 text-xs text-red-500">{error}</p>}

      <div className="mt-3 flex gap-3">
        <button
          onClick={toggleVisibility}
          disabled={busy}
          className="text-xs text-gray-500 hover:text-gray-800 disabled:opacity-40"
        >
          {badge.privacy_hidden ? 'Show' : 'Hide'}
        </button>
        <button
          onClick={toggleFeatured}
          disabled={busy}
          className={`text-xs disabled:opacity-40 ${
            badge.is_featured
              ? 'text-amber-600 hover:text-gray-500'
              : 'text-gray-500 hover:text-amber-600'
          }`}
        >
          {badge.is_featured ? '★ Featured' : '☆ Feature'}
        </button>
      </div>
    </div>
  )
}
