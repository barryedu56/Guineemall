import { useQueryClient } from '@tanstack/react-query'
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react'

import { tokens } from '../lib/api'
import { useAuth } from './auth'
import { RealtimeContext, type RealtimeEvent } from './realtime'

/**
 * Connexion WebSocket unique tant que l'utilisateur est connecté.
 * Se reconnecte seule (connexions mobiles instables) avec un délai croissant.
 */
export function RealtimeProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const listeners = useRef(new Set<(e: RealtimeEvent) => void>())
  const [connected, setConnected] = useState(false)

  useEffect(() => {
    if (!user) return
    let socket: WebSocket | null = null
    let retry = 0
    let timer: ReturnType<typeof setTimeout>
    let closed = false

    function connect() {
      const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws'
      socket = new WebSocket(`${protocol}://${window.location.host}/ws/?token=${tokens.access}`)
      socket.onopen = () => {
        retry = 0
        setConnected(true)
      }
      socket.onmessage = (msg) => {
        const event = JSON.parse(msg.data) as RealtimeEvent
        if (event.type === 'notification') queryClient.invalidateQueries({ queryKey: ['notifications'] })
        if (event.type === 'chat.message' || event.type === 'chat.read') {
          queryClient.invalidateQueries({ queryKey: ['conversations'] })
        }
        listeners.current.forEach((l) => l(event))
      }
      socket.onclose = () => {
        setConnected(false)
        if (closed) return
        retry += 1
        timer = setTimeout(connect, Math.min(30_000, 1000 * 2 ** retry))
      }
    }
    connect()
    return () => {
      closed = true
      clearTimeout(timer)
      socket?.close()
    }
  }, [user, queryClient])

  const value = useMemo(
    () => ({
      connected,
      subscribe: (listener: (e: RealtimeEvent) => void) => {
        listeners.current.add(listener)
        return () => {
          listeners.current.delete(listener)
        }
      },
    }),
    [connected],
  )

  return <RealtimeContext.Provider value={value}>{children}</RealtimeContext.Provider>
}
