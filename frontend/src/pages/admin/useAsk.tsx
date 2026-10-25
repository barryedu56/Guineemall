import { useState } from 'react'

import { Button, Overlay, Textarea } from '../../components/ui'

type AskOptions = { title: string; label: string; confirm: string; danger?: boolean; required?: boolean }

/** Fenêtre de saisie (motif, référence de transfert…) : `const value = await ask({...})`. */
export function useAsk() {
  const [state, setState] = useState<(AskOptions & { resolve: (v: string | null) => void }) | null>(null)
  const [value, setValue] = useState('')
  const ask = (options: AskOptions) =>
    new Promise<string | null>((resolve) => {
      setValue('')
      setState({ ...options, resolve })
    })
  const close = (result: string | null) => {
    state?.resolve(result)
    setState(null)
  }
  const dialog = state && (
    <Overlay>
      <div className="fixed inset-0 z-[80] flex items-end justify-center bg-ink/40 p-4 sm:items-center" role="dialog" aria-modal>
        <form onSubmit={(e) => { e.preventDefault(); close(value.trim()) }} className="card w-full max-w-md animate-fade-up space-y-4 p-6">
          <h2 className="text-lg font-bold">{state.title}</h2>
          <Textarea label={state.label} value={value} onChange={(e) => setValue(e.target.value)} required={state.required} autoFocus />
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={() => close(null)}>Annuler</Button>
            <Button type="submit" variant={state.danger ? 'danger' : 'primary'} disabled={state.required && !value.trim()}>{state.confirm}</Button>
          </div>
        </form>
      </div>
    </Overlay>
  )
  return [ask, dialog] as const
}
