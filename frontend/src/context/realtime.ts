import { createContext, useContext, useEffect, useEffectEvent } from 'react'

// Évènements envoyés par le serveur sur /ws/
export type RealtimeEvent =
  | { type: 'notification'; notification: { id: number; kind: string; title: string; body: string; link: string } }
  | { type: 'chat.message'; conversation: number; message: import('../lib/types').Message }
  | { type: 'chat.typing'; conversation: number }
  | { type: 'chat.read'; conversation: number }
  | { type: 'rider_location'; order_id: number; latitude: string; longitude: string }
  | { type: 'delivery.updated'; delivery: number; vendor_order?: number }
  | { type: 'delivery.message'; delivery: number; vendor_order: number }

type Listener = (event: RealtimeEvent) => void

export interface RealtimeContextValue {
  subscribe: (listener: Listener) => () => void
  connected: boolean
}

export const RealtimeContext = createContext<RealtimeContextValue | null>(null)

/** Écoute les évènements temps réel tant que le composant est affiché. */
export function useRealtime(handler: Listener) {
  const ctx = useContext(RealtimeContext)
  const onEvent = useEffectEvent(handler)
  useEffect(() => ctx?.subscribe((e) => onEvent(e)), [ctx])
}

export function useRealtimeStatus() {
  return useContext(RealtimeContext)?.connected ?? false
}
