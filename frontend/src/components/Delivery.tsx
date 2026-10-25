import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, ImagePlus, MessageCircle, Navigation, Phone, Send } from 'lucide-react'
import { useEffect, useEffectEvent, useRef, useState, type FormEvent } from 'react'

import { api, directionsUrl, errorMessage, formatTime, whatsappUrl } from '../lib/api'
import type { DeliveryMessage, TimelineStep } from '../lib/types'
import { ErrorBox } from './ui'
import { useVoiceRecorder } from '../lib/useVoiceRecorder'
import { MicButton, RecordingBar } from './VoiceRecorder'

/** Étapes de la commande, les mêmes pour le client, la boutique et le livreur. */
export function Timeline({ steps }: { steps: TimelineStep[] }) {
  const current = steps.findIndex((s) => !s.done)
  return (
    <ol className="space-y-0">
      {steps.map((step, i) => {
        const active = i === current
        const cancelled = step.key === 'cancelled'
        return (
          <li key={step.key} className="relative flex gap-3 pb-4 last:pb-0">
            {i < steps.length - 1 && (
              <span className={`absolute left-[11px] top-6 h-[calc(100%-1rem)] w-0.5 ${step.done ? 'bg-primary-dark' : 'bg-ink/10'}`} />
            )}
            <span
              className={`relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full ${
                cancelled ? 'bg-red-600 text-white' : step.done ? 'bg-primary-dark text-white' : active ? 'bg-primary ring-4 ring-primary/30' : 'bg-ink/10'
              }`}
            >
              {step.done && <Check className="size-3.5" strokeWidth={3} />}
            </span>
            <div className="min-w-0">
              <p className={`text-sm ${step.done ? 'font-semibold' : active ? 'font-semibold text-ink' : 'text-ink/50'}`}>{step.label}</p>
              {step.at && <p className="text-xs text-ink/50">{formatTime(step.at)} · {new Date(step.at).toLocaleDateString('fr-FR')}</p>}
              {active && !step.at && <p className="text-xs font-medium text-primary-dark">Étape en cours</p>}
            </div>
          </li>
        )
      })}
    </ol>
  )
}

/** Appeler, écrire sur WhatsApp, itinéraire. */
export function ContactButtons({
  phone,
  whatsapp,
  message = '',
  latitude,
  longitude,
}: {
  phone?: string | null
  whatsapp?: string | null
  message?: string
  latitude?: string | null
  longitude?: string | null
}) {
  const wa = whatsappUrl(whatsapp ?? phone, message)
  const route = directionsUrl(latitude, longitude)
  const button = 'inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-semibold transition'
  return (
    <div className="flex flex-wrap gap-2">
      {phone && (
        <a href={`tel:${phone}`} className={`${button} bg-ink text-white hover:bg-ink/85`}>
          <Phone className="size-4" /> Appeler
        </a>
      )}
      {wa && (
        <a href={wa} target="_blank" rel="noreferrer" className={`${button} bg-[#25D366] text-white hover:opacity-90`}>
          <MessageCircle className="size-4" /> WhatsApp
        </a>
      )}
      {route && (
        <a href={route} target="_blank" rel="noreferrer" className={`${button} bg-surface ring-1 ring-ink/15 hover:bg-ink/5`}>
          <Navigation className="size-4" /> Itinéraire
        </a>
      )}
    </div>
  )
}

/** Temps restant avant l'expiration d'une proposition de course. */
export function Countdown({ until, onEnd }: { until: string; onEnd?: () => void }) {
  const [now, setNow] = useState(() => Date.now())
  const end = new Date(until).getTime()
  const left = Math.max(0, end - now)
  const ended = left === 0
  const finished = useEffectEvent(() => onEnd?.())
  useEffect(() => {
    if (ended) return finished()
    const timer = setInterval(() => setNow(Date.now()), 1000)
    return () => clearInterval(timer)
  }, [ended])
  const minutes = Math.floor(left / 60000)
  const seconds = Math.floor((left % 60000) / 1000)
  return <span className="font-mono tabular-nums">{ended ? 'expirée' : `${minutes}:${String(seconds).padStart(2, '0')}`}</span>
}

/** Discussion boutique ↔ livreur d'une course. */
export function DeliveryChat({ deliveryId, partner, closed }: { deliveryId: number; partner: string; closed: boolean }) {
  const queryClient = useQueryClient()
  const key = ['delivery-chat', deliveryId]
  const [text, setText] = useState('')
  const [error, setError] = useState<string | null>(null)
  const bottom = useRef<HTMLDivElement>(null)
  const { data: messages = [] } = useQuery({
    queryKey: key,
    queryFn: async () => (await api.get<DeliveryMessage[]>(`/delivery/${deliveryId}/messages/`)).data,
  })
  const send = useMutation({
    mutationFn: async (payload: { content?: string; image?: File; audio?: File }) => {
      const file = payload.image ? ['image', payload.image] as const : payload.audio ? ['audio', payload.audio] as const : null
      if (file) {
        const data = new FormData()
        data.append(file[0], file[1])
        return api.post(`/delivery/${deliveryId}/messages/`, data)
      }
      return api.post(`/delivery/${deliveryId}/messages/`, payload)
    },
    onSuccess: () => {
      setText('')
      setError(null)
      queryClient.invalidateQueries({ queryKey: key })
    },
    onError: (e) => setError(errorMessage(e)),
  })
  const voice = useVoiceRecorder((audio) => send.mutate({ audio }), setError)
  useEffect(() => {
    bottom.current?.scrollIntoView({ block: 'nearest' })
  }, [messages.length])
  // Messages lus en ouvrant la discussion : les compteurs de non-lus se remettent à zéro
  useEffect(() => {
    queryClient.invalidateQueries({ queryKey: ['vendor-orders'] })
    queryClient.invalidateQueries({ queryKey: ['rider-deliveries'] })
  }, [messages.length, queryClient])

  function submit(e: FormEvent) {
    e.preventDefault()
    if (text.trim()) send.mutate({ content: text.trim() })
  }

  return (
    <div className="flex flex-col overflow-hidden rounded-2xl bg-background ring-1 ring-ink/10">
      <div className="max-h-80 min-h-32 space-y-2 overflow-y-auto p-3">
        {messages.length === 0 && (
          <p className="py-6 text-center text-sm text-ink/50">
            Écrivez ou envoyez un vocal à {partner} : où récupérer le colis, à qui le remettre, un retard…
          </p>
        )}
        {messages.map((m) => (
          <div key={m.id} className={`flex ${m.mine ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[80%] rounded-2xl px-3 py-2 text-sm ${m.mine ? 'bg-primary-dark text-white' : 'bg-surface shadow-sm'}`}>
              {m.image && (
                <a href={m.image} target="_blank" rel="noreferrer">
                  <img src={m.image} alt="Photo envoyée" className="mb-1 max-h-48 rounded-xl object-cover" />
                </a>
              )}
              {m.audio && <audio controls src={m.audio} className="h-10 max-w-60" />}
              {m.content && <p className="whitespace-pre-wrap break-words">{m.content}</p>}
              <p className={`mt-0.5 text-right text-[11px] ${m.mine ? 'text-white/70' : 'text-ink/45'}`}>
                {formatTime(m.created_at)}
                {m.mine && m.read_at && ' · lu'}
              </p>
            </div>
          </div>
        ))}
        <div ref={bottom} />
      </div>
      {closed ? (
        <p className="border-t border-ink/10 p-3 text-center text-xs text-ink/50">Course terminée : discussion fermée.</p>
      ) : (
        <form onSubmit={submit} className="flex items-center gap-2 border-t border-ink/10 bg-surface p-2">
          {voice.recording ? (
            <RecordingBar seconds={voice.seconds} onCancel={voice.cancel} onSend={voice.send} />
          ) : (
            <>
              <label className="cursor-pointer rounded-full p-2 text-ink/60 hover:bg-ink/5" aria-label="Envoyer une photo">
                <ImagePlus className="size-5" />
                <input
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={(e) => {
                    const file = e.target.files?.[0]
                    if (file) send.mutate({ image: file })
                    e.target.value = ''
                  }}
                />
              </label>
              <input
                value={text}
                onChange={(e) => setText(e.target.value)}
                placeholder="Votre message…"
                maxLength={1000}
                className="min-w-0 flex-1 rounded-full bg-background px-4 py-2 text-sm outline-none ring-1 ring-ink/10 focus:ring-primary-dark"
              />
              {text.trim() ? (
                <button type="submit" disabled={send.isPending} className="rounded-full bg-primary-dark p-2.5 text-white disabled:opacity-40" aria-label="Envoyer">
                  <Send className="size-4" />
                </button>
              ) : (
                <MicButton onClick={voice.start} disabled={send.isPending} />
              )}
            </>
          )}
        </form>
      )}
      <ErrorBox message={error} />
    </div>
  )
}
