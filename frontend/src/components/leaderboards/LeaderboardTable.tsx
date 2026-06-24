import type { LeaderboardEntry } from '@/lib/types'

const rankLabel = (rank: number) => {
  if (rank === 1) return '🥇'
  if (rank === 2) return '🥈'
  if (rank === 3) return '🥉'
  return `#${rank}`
}

export default function LeaderboardTable({
  entries,
  currentUserId,
}: {
  entries: LeaderboardEntry[]
  currentUserId?: number
}) {
  if (entries.length === 0) {
    return <p className="py-4 text-sm text-gray-400">No entries yet.</p>
  }

  return (
    <div className="overflow-hidden rounded-lg border border-gray-200">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
              Rank
            </th>
            <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
              Nickname
            </th>
            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
              Score
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100 bg-white">
          {entries.map((entry) => (
            <tr
              key={entry.user_id}
              className={entry.user_id === currentUserId ? 'bg-gray-50 font-medium' : ''}
            >
              <td className="px-4 py-3 text-sm text-gray-600">{rankLabel(entry.rank)}</td>
              <td className="px-4 py-3 text-sm text-gray-900">
                {entry.nickname ?? (
                  <span className="italic text-gray-400">Anonymous</span>
                )}
                {entry.user_id === currentUserId && (
                  <span className="ml-2 text-xs text-gray-400">(you)</span>
                )}
              </td>
              <td className="px-4 py-3 text-right font-mono text-sm text-gray-900">
                {entry.score}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
