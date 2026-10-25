import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ShieldCheck, Smartphone } from 'lucide-react'
import { useState } from 'react'

import { useToast } from '../context/toast'
import { api, errorMessage } from '../lib/api'
import { Button, ErrorBox } from './ui'

function CodeInput({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  return (
    <input
      value={value}
      onChange={(e) => onChange(e.target.value.replace(/\D/g, '').slice(0, 6))}
      inputMode="numeric"
      autoComplete="one-time-code"
      placeholder="••••••"
      aria-label="Code à 6 chiffres"
      className="w-48 rounded-2xl border border-ink/15 bg-surface px-4 py-2.5 text-center font-mono text-2xl tracking-[0.4em]"
    />
  )
}

/**
 * Activation / désactivation de la double authentification (application type Google Authenticator).
 * Obligatoire pour l'équipe, facultative (et recommandée) pour les boutiques et les clients.
 */
export default function TwoFactor({ onEnabled }: { onEnabled?: () => void }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [code, setCode] = useState('')
  const { data: status } = useQuery({
    queryKey: ['2fa'],
    queryFn: async () => (await api.get<{ enabled: boolean; required: boolean }>('/auth/2fa/')).data,
  })
  const setup = useMutation({
    mutationFn: async () => (await api.post<{ secret: string; qr: string }>('/auth/2fa/setup/')).data,
  })
  const done = () => {
    setCode('')
    setup.reset()
    queryClient.invalidateQueries({ queryKey: ['2fa'] })
    queryClient.invalidateQueries({ queryKey: ['me'] })
  }
  const enable = useMutation({
    mutationFn: () => api.post('/auth/2fa/enable/', { code }),
    onSuccess: () => {
      done()
      toast('Double authentification activée 🔒')
      onEnabled?.()
    },
  })
  const disable = useMutation({
    mutationFn: () => api.post('/auth/2fa/disable/', { code }),
    onSuccess: () => {
      done()
      toast('Double authentification désactivée', 'info')
    },
  })

  if (!status) return null

  return (
    <section className="card space-y-4 p-6">
      <h2 className="flex items-center gap-2 text-lg font-bold">
        <ShieldCheck className="size-5 text-primary-dark" /> Double authentification
        {status.enabled && <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-bold text-green-800">Activée</span>}
      </h2>

      {status.enabled ? (
        status.required ? (
          <p className="text-sm text-ink/70">Obligatoire pour les comptes de l'équipe : elle ne peut pas être désactivée.</p>
        ) : (
          <form onSubmit={(e) => { e.preventDefault(); disable.mutate() }} className="flex flex-wrap items-center gap-3">
            <p className="w-full text-sm text-ink/70">Pour la désactiver, saisissez le code actuel de votre application.</p>
            <CodeInput value={code} onChange={setCode} />
            <Button type="submit" variant="outline" disabled={code.length !== 6} loading={disable.isPending}>Désactiver</Button>
            <ErrorBox message={disable.error ? errorMessage(disable.error) : null} />
          </form>
        )
      ) : !setup.data ? (
        <div className="space-y-3">
          <p className="text-sm text-ink/70">
            En plus du mot de passe, un code à 6 chiffres affiché par une application sur votre téléphone
            (Google Authenticator, Microsoft Authenticator, Authy…) sera demandé à chaque connexion.
            Même si quelqu'un vole votre mot de passe, il ne pourra pas entrer.
            {status.required && <strong> Obligatoire pour accéder à l'administration.</strong>}
          </p>
          <Button onClick={() => setup.mutate()} loading={setup.isPending}><Smartphone className="size-4" /> Activer</Button>
          <ErrorBox message={setup.error ? errorMessage(setup.error) : null} />
        </div>
      ) : (
        <form onSubmit={(e) => { e.preventDefault(); enable.mutate() }} className="grid gap-5 sm:grid-cols-[auto_1fr]">
          <img src={setup.data.qr} alt="QR code à scanner avec l'application d'authentification" className="size-44 rounded-2xl border border-ink/10" />
          <div className="space-y-3 text-sm">
            <p><strong>1.</strong> Dans l'application, ajoutez un compte en scannant ce QR code.</p>
            <p className="text-ink/60">
              Impossible de scanner ? Saisissez cette clé : <code className="break-all rounded bg-ink/5 px-1.5 py-0.5 font-mono">{setup.data.secret}</code>
            </p>
            <p><strong>2.</strong> Saisissez le code à 6 chiffres affiché :</p>
            <div className="flex flex-wrap items-center gap-3">
              <CodeInput value={code} onChange={setCode} />
              <Button type="submit" disabled={code.length !== 6} loading={enable.isPending}>Confirmer</Button>
            </div>
            <ErrorBox message={enable.error ? errorMessage(enable.error) : null} />
          </div>
        </form>
      )}
    </section>
  )
}
