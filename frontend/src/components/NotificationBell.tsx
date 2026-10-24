import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Bell } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'

import { api, timeAgo } from '../lib/api'
import { useNotifications } from '../lib/queries'

export default function NotificationBell() {
  const { data } = useNotifications()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  const readAll = useMutation({
    mutationFn: () => api.post('/notifications/read_all/'),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  })

  useEffect(() => {
    const close = (e: MouseEvent) => ref.current && !ref.current.contains(e.target as Node) && setOpen(false)
    document.addEventListener('mousedown', close)
    return () => document.removeEventListener('mousedown', close)
  }, [])

  const unread = data?.unread ?? 0

  return (
    <div ref={ref} className="relative">
      <button
        onClick={() => {
          setOpen(!open)
          if (!open && unread) readAll.mutate()
        }}
        className="relative rounded-full p-2 hover:bg-ink/5"
        aria-label="Notifications"
      >
        <Bell className="size-6" />
        {unread > 0 && (
          <span className="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white">
            {unread}
          </span>
        )}
      </button>
      {open && (
        <div className="absolute right-0 top-12 z-50 max-h-[70vh] w-80 max-w-[calc(100vw-2rem)] overflow-y-auto rounded-2xl bg-surface shadow-xl ring-1 ring-ink/10">
          <p className="border-b border-ink/5 px-4 py-3 font-semibold">Notifications</p>
          {data?.results.length ? (
            data.results.map((n) => (
              <button
                key={n.id}
                onClick={() => {
                  setOpen(false)
                  if (n.link) navigate(n.link)
                }}
                className={`block w-full border-b border-ink/5 px-4 py-3 text-left hover:bg-ink/5 ${n.read_at ? '' : 'bg-primary-light/30'}`}
              >
                <p className="text-sm font-semibold">{n.title}</p>
                {n.body && <p className="line-clamp-2 text-sm text-ink/70">{n.body}</p>}
                <p className="mt-0.5 text-xs text-ink/50">{timeAgo(n.created_at)}</p>
              </button>
            ))
          ) : (
            <p className="px-4 py-6 text-center text-sm text-ink/60">Aucune notification</p>
          )}
        </div>
      )}
    </div>
  )
}
