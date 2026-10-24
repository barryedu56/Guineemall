import { createContext, useContext } from 'react'

export type Tone = 'success' | 'error' | 'info'

export const ToastContext = createContext<((message: string, tone?: Tone) => void) | null>(null)

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast doit être utilisé dans <ToastProvider>')
  return ctx
}
