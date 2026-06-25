'use client'

import { useEffect, useState } from 'react'
import { getBadgeConfigs, updateBadgeConfig, CREATOR_APP_ID } from '@/lib/api'
import type { BadgeDefinition } from '@/lib/types'

function BadgeRow({ badge: initial }: { badge: BadgeDefinition }) {
  const [badge, setBadge] = useState(initial)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const toggleEnabled = async () => {
    setSaving(true)
    setError(null)
    try {
      const updated = await updateBadgeConfig({
        badge_id: badge.id,
        creator_app_id: CREATOR_APP_ID,
        enabled: !badge.enabled,
      })
      setBadge(updated)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div
      className={`rounded-lg border border-gray-200 bg-white p-5 transition-opacity ${
        badge.enabled ? '' : 'opacity-60'
      }`}
    >
      <div className="flex items-start justify-between gap-4">
        <div className="flex items-start gap-3">
          <span className="text-2xl leading-none">{badge.icon || '🏅'}</span>
          <div>
            <p className="text-sm font-medium text-gray-900">{badge.name}</p>
            <p className="text-xs text-gray-500">{badge.badge_category}</p>
          </div>
        </div>
        {badge.creator_app_id !== null ? (
          <button
            onClick={toggleEnabled}
            disabled={saving}
            className={`relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-50 ${
              badge.enabled ? 'bg-gray-900' : 'bg-gray-300'
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
                badge.enabled ? 'translate-x-6' : 'translate-x-1'
              }`}
            />
          </button>
        ) : (
          <span className="text-xs text-gray-400">Platform</span>
        )}
      </div>

      <p className="mt-3 line-clamp-2 text-xs text-gray-400">{badge.description}</p>

      <div className="mt-3 flex items-center justify-between">
        <span className="text-xs text-gray-400">Rule: {badge.rule_type}</span>
        {error && <span className="text-xs text-red-500">{error}</span>}
      </div>
    </div>
  )
}

export default function BadgeConfigPage() {
  const [badges, setBadges] = useState<BadgeDefinition[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    getBadgeConfigs(CREATOR_APP_ID)
      .then(setBadges)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="p-8 text-gray-500">Loading…</div>
  if (error) return <div className="p-8 text-red-500">{error}</div>

  return (
    <div className="space-y-6 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Badge Config</h1>

      {badges.length === 0 ? (
        <p className="text-sm text-gray-400">No badge definitions found.</p>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {badges.map((b) => (
            <BadgeRow key={b.id} badge={b} />
          ))}
        </div>
      )}
    </div>
  )
}
