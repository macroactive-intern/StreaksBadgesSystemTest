'use client'

import { useState, useEffect, useRef } from 'react'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import { useAuth } from '@/context/AuthContext'
import LoginModal from '@/components/auth/LoginModal'
import CheckInNotification from '@/components/checkin/CheckInNotification'
import { getStreaks, recordCheckIn, CREATOR_APP_ID } from '@/lib/api'
import type { Streak } from '@/lib/types'

const userLinks = [
  { href: '/dashboard', label: 'Dashboard' },
  { href: '/leaderboards', label: 'Leaderboards' },
  { href: '/preferences', label: 'Preferences' },
]

const creatorLinks = [
  { href: '/creator/analytics', label: 'Analytics' },
  { href: '/creator/streaks', label: 'Streak Config' },
  { href: '/creator/badges', label: 'Badge Config' },
  { href: '/creator/moderation', label: 'Moderation' },
]

function needsCheckInToday(streak: Streak): boolean {
  const today = new Date().toLocaleDateString('en-CA')
  return streak.last_completed_date !== today
}

export default function Sidebar() {
  const pathname = usePathname()
  const { user, logout } = useAuth()
  const [showLogin, setShowLogin] = useState(false)
  const [pendingStreaks, setPendingStreaks] = useState<Streak[]>([])
  const [checkInLoading, setCheckInLoading] = useState(false)
  const checkedUserRef = useRef<number | null>(null)

  useEffect(() => {
    if (!user || checkedUserRef.current === user.id) return
    checkedUserRef.current = user.id
    getStreaks(user.id, CREATOR_APP_ID)
      .then((streaks) => {
        const due = streaks.filter(needsCheckInToday)
        if (due.length > 0) setPendingStreaks(due)
      })
      .catch(() => {})
  }, [user])

  useEffect(() => {
    if (!user) {
      checkedUserRef.current = null
      setPendingStreaks([])
    }
  }, [user])

  const handleCheckIn = async () => {
    if (!user) return
    setCheckInLoading(true)
    try {
      await recordCheckIn(user.id, CREATOR_APP_ID)
      window.dispatchEvent(new Event('checkin:complete'))
      setPendingStreaks([])
    } catch {
      // dismiss silently; user can retry on dashboard
      setPendingStreaks([])
    } finally {
      setCheckInLoading(false)
    }
  }

  const linkClass = (href: string) =>
    `flex items-center rounded-md px-3 py-2 text-sm transition-colors ${
      pathname === href || pathname.startsWith(href + '/')
        ? 'bg-gray-900 text-white'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
    }`

  return (
    <aside className="flex w-56 flex-col border-r border-gray-200 bg-white">
      <div className="flex h-14 items-center border-b border-gray-200 px-4">
        <span className="text-sm font-semibold text-gray-900">MacroActive</span>
      </div>

      <nav className="flex flex-1 flex-col gap-6 overflow-y-auto p-3">
        <section>
          <p className="mb-1 px-3 text-xs font-medium uppercase tracking-wider text-gray-400">
            User
          </p>
          <ul className="space-y-0.5">
            {userLinks.map(({ href, label }) => (
              <li key={href}>
                <Link href={href} className={linkClass(href)}>
                  {label}
                </Link>
              </li>
            ))}
          </ul>
        </section>

        <section>
          <p className="mb-1 px-3 text-xs font-medium uppercase tracking-wider text-gray-400">
            Creator
          </p>
          <ul className="space-y-0.5">
            {creatorLinks.map(({ href, label }) => (
              <li key={href}>
                <Link href={href} className={linkClass(href)}>
                  {label}
                </Link>
              </li>
            ))}
          </ul>
        </section>
      </nav>

      <div className="border-t border-gray-200 p-3">
        {user ? (
          <div className="space-y-1">
            <p className="truncate text-xs font-medium text-gray-700">{user.name}</p>
            <p className="truncate text-xs text-gray-400">{user.email}</p>
            <button
              onClick={() => logout()}
              className="mt-2 text-xs text-gray-500 hover:text-gray-900"
            >
              Sign out
            </button>
          </div>
        ) : (
          <button
            onClick={() => setShowLogin(true)}
            className="w-full rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white hover:bg-gray-700"
          >
            Sign in
          </button>
        )}
      </div>

      {showLogin && <LoginModal onClose={() => setShowLogin(false)} />}

      {pendingStreaks.length > 0 && (
        <CheckInNotification
          streaks={pendingStreaks}
          onCheckIn={handleCheckIn}
          onDismiss={() => setPendingStreaks([])}
          loading={checkInLoading}
        />
      )}
    </aside>
  )
}
