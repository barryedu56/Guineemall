import { CheckCircle2, Info, XCircle } from 'lucide-react'
import { useCallback, useMemo, useState, type ReactNode } from 'react'

import { ToastContext, type Tone } from './toast'

type Toast = { id: number; message: string; tone: Tone }

/** Messages de confirmation éphémères (« Ajouté au panier », erreurs…). */
export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const show = useCallback((message: string, tone: Tone = 'success') => {
    const id = Date.now() + Math.random()
    setToasts((t) => [...t.slice(-2), { id, message, tone }])
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 3200)
  }, [])

  const icons = { success: CheckCircle2, error: XCircle, info: Info }
  const colors = { success: 'text-green-400', error: 'text-red-400', info: 'text-primary' }

  return (
    <ToastContext.Provider value={useMemo(() => show, [show])}>
      {children}
      <div className="pointer-events-none fixed inset-x-0 bottom-20 z-[60] flex flex-col items-center gap-2 px-4 md:bottom-6" aria-live="polite">
        {toasts.map((t) => {
          const Icon = icons[t.tone]
          return (
            <div key={t.id} className="flex max-w-md animate-toast items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-sm font-medium text-white shadow-lift">
              <Icon className={`size-5 shrink-0 ${colors[t.tone]}`} />
              {t.message}
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}
