'use client'

import { useEffect, useState, useCallback } from 'react'
import { getModerationQueue, reviewModerationItem, CREATOR_APP_ID } from '@/lib/api'
import type { ModerationItem, ModerationStatus } from '@/lib/types'

const TABS: { value: ModerationStatus; label: string }[] = [
  { value: 'pending', label: 'Pending' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'dismissed', label: 'Dismissed' },
]

const severityColors: Record<string, string> = {
  low: 'bg-blue-100 text-blue-700',
  medium: 'bg-amber-100 text-amber-700',
  high: 'bg-red-100 text-red-700',
}

function ModerationRow({
  item,
  onReviewed,
}: {
  item: ModerationItem
  onReviewed: (id: number) => void
}) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const review = async (action: 'resolved' | 'dismissed') => {
    setBusy(true)
    setError(null)
    try {
      await reviewModerationItem(item.id, {
        creator_app_id: CREATOR_APP_ID,
        status: action,
      })
      onReviewed(item.id)
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Action failed')
      setBusy(false)
    }
  }

  return (
    <tr>
      <td className="px-4 py-3 text-sm text-gray-900">#{item.user_id}</td>
      <td className="px-4 py-3 text-sm text-gray-600">{item.detection_type}</td>
      <td className="px-4 py-3">
        <span
          className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
            severityColors[item.severity] ?? 'bg-gray-100 text-gray-700'
          }`}
        >
          {item.severity}
        </span>
      </td>
      <td className="px-4 py-3 text-sm text-gray-500">
        {new Date(item.created_at).toLocaleDateString()}
      </td>
      <td className="px-4 py-3 text-right">
        {item.status === 'pending' ? (
          <div className="flex items-center justify-end gap-3">
            {error && <span className="text-xs text-red-500">{error}</span>}
            <button
              onClick={() => review('resolved')}
              disabled={busy}
              className="text-xs font-medium text-green-700 hover:text-green-900 disabled:opacity-40"
            >
              Resolve
            </button>
            <button
              onClick={() => review('dismissed')}
              disabled={busy}
              className="text-xs font-medium text-gray-500 hover:text-gray-800 disabled:opacity-40"
            >
              Dismiss
            </button>
          </div>
        ) : (
          <span className="text-xs text-gray-400">{item.status}</span>
        )}
      </td>
    </tr>
  )
}

export default function ModerationPage() {
  const [tab, setTab] = useState<ModerationStatus>('pending')
  const [items, setItems] = useState<ModerationItem[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const load = useCallback(() => {
    setLoading(true)
    setError(null)
    getModerationQueue(CREATOR_APP_ID, tab)
      .then(setItems)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false))
  }, [tab])

  useEffect(() => { load() }, [load])

  const removeItem = (id: number) =>
    setItems((prev) => prev.filter((i) => i.id !== id))

  return (
    <div className="space-y-6 p-8">
      <h1 className="text-2xl font-semibold text-gray-900">Moderation</h1>

      <div className="flex gap-2">
        {TABS.map(({ value, label }) => (
          <button
            key={value}
            onClick={() => setTab(value)}
            className={`rounded-full px-4 py-1.5 text-sm font-medium transition-colors ${
              tab === value
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

      {!loading && !error && items.length === 0 && (
        <p className="text-sm text-gray-400">No {tab} items.</p>
      )}

      {!loading && !error && items.length > 0 && (
        <div className="overflow-hidden rounded-lg border border-gray-200">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Severity</th>
                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 bg-white">
              {items.map((item) => (
                <ModerationRow key={item.id} item={item} onReviewed={removeItem} />
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
