import type { Streak } from '@/lib/types'

const statusColors: Record<string, string> = {
  active: 'bg-green-100 text-green-800',
  at_risk: 'bg-amber-100 text-amber-800',
  broken: 'bg-red-100 text-red-800',
}

const streakTypeLabels: Record<string, string> = {
  workout_completion: 'Workout',
  nutrition_log: 'Nutrition',
  habit_completion: 'Habit',
  community_participation: 'Community',
}

export default function StreakCard({ streak }: { streak: Streak }) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4">
      <div className="mb-3 flex items-center justify-between">
        <span className="text-xs font-medium text-gray-500">
          {streakTypeLabels[streak.streak_type] ?? streak.streak_type}
        </span>
        <span
          className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
            statusColors[streak.status] ?? 'bg-gray-100 text-gray-800'
          }`}
        >
          {streak.status_label}
        </span>
      </div>

      <p className="text-4xl font-bold text-gray-900">{streak.current_count}</p>
      <p className="mt-0.5 text-xs text-gray-500">days</p>

      {streak.next_milestone != null && (
        <div className="mt-3">
          <div className="mb-1 flex justify-between text-xs text-gray-400">
            <span>Next: {streak.next_milestone}d</span>
            <span>{streak.progress_percent}%</span>
          </div>
          <div className="h-1.5 w-full rounded-full bg-gray-100">
            <div
              className="h-1.5 rounded-full bg-gray-900"
              style={{ width: `${streak.progress_percent}%` }}
            />
          </div>
        </div>
      )}

      <div className="mt-3 space-y-0.5">
        {streak.last_completed_date && (
          <p className="text-xs text-gray-400">Last: {streak.last_completed_date}</p>
        )}
        <p className="text-xs text-gray-400">Best: {streak.longest_count}d</p>
      </div>
    </div>
  )
}
