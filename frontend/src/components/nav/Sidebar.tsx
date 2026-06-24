'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'

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

export default function Sidebar() {
  const pathname = usePathname()

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

      <div className="border-t border-gray-200 p-3 text-xs text-gray-400">
        <p>User {process.env.NEXT_PUBLIC_USER_ID ?? '–'}</p>
        <p>App {process.env.NEXT_PUBLIC_CREATOR_APP_ID ?? '–'}</p>
      </div>
    </aside>
  )
}
