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
  const [editing, setEditing] = useState(false)
  const [draft, setDraft] = useState({ ...initial })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const toggleEnabled = async () => {
    setSaving(true)
    setError(null)
    try {
      const updated = await updateStreakConfig({
        id: config.id,
        creator_app_id: CREATOR_APP_ID,
        enabled: !config.enabled,
      })
      setConfig(updated)
      setDraft(updated)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const saveEdit = async () => {
    setSaving(true)
    setError(null)
    try {
      const updated = await updateStreakConfig({
        id: config.id,
        creator_app_id: CREATOR_APP_ID,
        freeze_grants_per_month: draft.freeze_grants_per_month,
        at_risk_grace_hours: draft.at_risk_grace_hours,
      })
      setConfig(updated)
      setDraft(updated)
      setEditing(false)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="rounded-lg border border-gray-200 bg-white p-5">
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

      {editing ? (
        <div className="mt-4 space-y-3">
          <div className="flex gap-4">
            <div className="flex-1">
              <label className="mb-1 block text-xs font-medium text-gray-600">
                Freeze grants / month
              </label>
              <input
                type="number"
                min={0}
                value={draft.freeze_grants_per_month}
                onChange={(e) =>
                  setDraft((d) => ({ ...d, freeze_grants_per_month: parseInt(e.target.value, 10) || 0 }))
                }
                className="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
              />
            </div>
            <div className="flex-1">
              <label className="mb-1 block text-xs font-medium text-gray-600">
                At-risk grace (hours)
              </label>
              <input
                type="number"
                min={1}
                value={draft.at_risk_grace_hours}
                onChange={(e) =>
                  setDraft((d) => ({ ...d, at_risk_grace_hours: parseInt(e.target.value, 10) || 1 }))
                }
                className="block w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
              />
            </div>
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={saveEdit}
              disabled={saving}
              className="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-700 disabled:opacity-50"
            >
              {saving ? 'Saving…' : 'Save'}
            </button>
            <button
              onClick={() => { setEditing(false); setDraft(config) }}
              disabled={saving}
              className="text-xs text-gray-500 hover:text-gray-800"
            >
              Cancel
            </button>
            {error && <span className="text-xs text-red-500">{error}</span>}
          </div>
        </div>
      ) : (
        <div className="mt-3 flex items-center justify-between text-xs text-gray-400">
          <span>
            {config.freeze_grants_per_month} freeze/mo · {config.at_risk_grace_hours}h grace
          </span>
          <button
            onClick={() => setEditing(true)}
            className="text-gray-500 hover:text-gray-800"
          >
            Edit
          </button>
        </div>
      )}
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
