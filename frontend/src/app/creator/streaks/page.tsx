'use client'

import { useEffect, useState } from 'react'
import { getStreakConfigs, updateStreakConfig, CREATOR_APP_ID } from '@/lib/api'
import type { StreakConfig } from '@/lib/types'

const streakTypeLabels: Record<string, string> = {
  workout_completion: 'Workout Completion',
  nutrition_log: 'Nutrition Log',
  habit_completion: 'Habit Completion',
  community_participation: 'Community Participation',
}

function ConfigRow({ config: initial }: { config: StreakConfig }) {
  const [config, setConfig] = useState(initial)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const toggleEnabled = async () => {
    setSaving(true)
    setError(null)
    try {
      const updated = await updateStreakConfig({
        streak_type: config.streak_type,
        creator_app_id: CREATOR_APP_ID,
        enabled: !config.enabled,
      })
      setConfig(updated)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div
      className={`rounded-lg border border-gray-200 bg-white p-5 transition-opacity ${
        config.enabled ? '' : 'opacity-60'
      }`}
    >
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-gray-900">
            {streakTypeLabels[config.streak_type] ?? config.streak_type}
          </p>
          {config.qualifying_event_type && (
            <p className="mt-0.5 text-xs text-gray-400">
              Event: {config.qualifying_event_type}
            </p>
          )}
        </div>
        <button
          onClick={toggleEnabled}
          disabled={saving}
          className={`relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-50 ${
            config.enabled ? 'bg-gray-900' : 'bg-gray-300'
          }`}
        >
          <span
            className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
              config.enabled ? 'translate-x-6' : 'translate-x-1'
            }`}
          />
        </button>
      </div>

      <div className="mt-3 flex items-center justify-between text-xs text-gray-400">
        <span>Min threshold: {config.minimum_threshold}</span>
        {error && <span className="text-red-500">{error}</span>}
      </div>
    </div>
  )
}

export default function StreakConfigPage() {
  const [configs, setConfigs] = useState<StreakConfig[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    getStreakConfigs(CREATOR_APP_ID)
      .then(setConfigs)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="p-8 text-gray-500">Loading…</div>
  if (error) return <div className="p-8 text-red-500">{error}</div>

  return (
    <div className="space-y-6 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Streak Config</h1>

      {configs.length === 0 ? (
        <p className="text-sm text-gray-400">No streak configs found.</p>
      ) : (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {configs.map((c) => (
            <ConfigRow key={c.id} config={c} />
          ))}
        </div>
      )}
    </div>
  )
}
