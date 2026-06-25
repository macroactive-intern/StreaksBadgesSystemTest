'use client'

import { useEffect, useState } from 'react'
import { getPreferences, updatePreferences, CREATOR_APP_ID } from '@/lib/api'
import type { UserPreferences } from '@/lib/types'
import { useAuth } from '@/context/AuthContext'

export default function PreferencesPage() {
  const { user } = useAuth()
  const [prefs, setPrefs] = useState<UserPreferences | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  useEffect(() => {
    if (!user) { setLoading(false); return }
    getPreferences(user.id, CREATOR_APP_ID)
      .then(setPrefs)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [user])

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!prefs || !user) return
    setSaving(true)
    setSaved(false)
    setError(null)
    try {
      const updated = await updatePreferences(user.id, CREATOR_APP_ID, {
        leaderboard_nickname: prefs.leaderboard_nickname,
        leaderboard_visible: prefs.leaderboard_visible,
      })
      setPrefs(updated)
      setSaved(true)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  if (!user) {
    return (
      <div className="flex h-full items-center justify-center p-8">
        <p className="text-sm text-gray-500">Sign in to manage your preferences.</p>
      </div>
    )
  }

  if (loading) return <div className="p-8 text-gray-500">Loading…</div>
  if (error && !prefs) return <div className="p-8 text-red-500">{error}</div>

  return (
    <div className="max-w-lg space-y-6 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Preferences</h1>

      <form
        onSubmit={handleSubmit}
        className="space-y-5 rounded-lg border border-gray-200 bg-white p-6"
      >
        <div>
          <label htmlFor="nickname" className="mb-1 block text-sm font-medium text-gray-700">
            Leaderboard Nickname
          </label>
          <input
            id="nickname"
            type="text"
            value={prefs?.leaderboard_nickname ?? ''}
            onChange={(e) =>
              setPrefs((p) => (p ? { ...p, leaderboard_nickname: e.target.value } : p))
            }
            placeholder="Your public nickname"
            maxLength={30}
            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
          />
        </div>

        <div className="flex items-center justify-between">
          <span className="text-sm font-medium text-gray-700">Show on leaderboards</span>
          <button
            type="button"
            aria-pressed={prefs?.leaderboard_visible}
            aria-label={prefs?.leaderboard_visible ? 'Hide from leaderboards' : 'Show on leaderboards'}
            onClick={() =>
              setPrefs((p) => (p ? { ...p, leaderboard_visible: !p.leaderboard_visible } : p))
            }
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              prefs?.leaderboard_visible ? 'bg-gray-900' : 'bg-gray-300'
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
                prefs?.leaderboard_visible ? 'translate-x-6' : 'translate-x-1'
              }`}
            />
          </button>
        </div>

        <div className="flex items-center gap-3 pt-2">
          <button
            type="submit"
            disabled={saving}
            className="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
          >
            {saving ? 'Saving…' : 'Save preferences'}
          </button>
          {saved && <span className="text-sm text-green-600">Saved!</span>}
          {error && <span className="text-sm text-red-500">{error}</span>}
        </div>
      </form>
    </div>
  )
}
