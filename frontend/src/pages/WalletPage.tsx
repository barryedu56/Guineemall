import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Lock, Wallet as WalletIcon } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import Price from '../components/Price'
import { Badge, Button, ErrorBox, Input, Select, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { api, errorMessage, formatDate } from '../lib/api'
import type { Payout, Wallet } from '../lib/types'

/** Portefeuille : vendeurs (ventes), ambassadeurs (commissions), livreurs (courses). */
export default function WalletPage() {
  const { user } = useAuth()
  const { data: config } = useSiteConfig()
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ amount: '', method: 'orange_money', phone: user?.phone ?? '' })
  const [error, setError] = useState<string | null>(null)

  const { data: wallet, isLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => (await api.get<Wallet>('/wallet/')).data,
  })
  const { data: payouts = [] } = useQuery({
    queryKey: ['payouts'],
    queryFn: async () => (await api.get<Payout[]>('/wallet/payouts/')).data,
  })

  const withdraw = useMutation({
    mutationFn: () => api.post('/wallet/payouts/', { ...form, amount: Number(form.amount) }),
    onSuccess: () => {
      setForm({ ...form, amount: '' })
      queryClient.invalidateQueries({ queryKey: ['wallet'] })
      queryClient.invalidateQueries({ queryKey: ['payouts'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  if (isLoading || !wallet) return <Spinner />

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    withdraw.mutate()
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="grid gap-3 sm:grid-cols-2">
        <div className="rounded-3xl bg-ink p-6 text-white">
          <p className="flex items-center gap-2 text-white/70"><WalletIcon className="size-5" /> Solde disponible</p>
          <Price amount={Math.max(0, wallet.available)} className="mt-2 block text-3xl font-extrabold text-primary" />
          {wallet.pending_payouts > 0 && <p className="mt-1 text-sm text-white/70">Retrait en cours : <Price amount={wallet.pending_payouts} /></p>}
          {wallet.balance < 0 && (
            <p className="mt-2 text-sm text-red-300">
              Vous devez <Price amount={-wallet.balance} /> à {config?.site.name} (commissions ou espèces encaissées).
            </p>
          )}
        </div>
        {wallet.escrow > 0 && (
          <div className="card p-6">
            <p className="flex items-center gap-2 text-ink/60"><Lock className="size-5" /> En séquestre</p>
            <Price amount={wallet.escrow} className="mt-2 block text-2xl font-bold" />
            <p className="mt-1 text-sm text-ink/60">Versé dès que vos clients confirment la réception.</p>
          </div>
        )}
      </div>

      <form onSubmit={submit} className="space-y-3 card p-6">
        <h2 className="text-lg font-semibold">Retirer mon argent</h2>
        <div className="grid gap-3 sm:grid-cols-3">
          <Input label="Montant (GNF)" type="number" min={1000} value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} required />
          <Select label="Vers" value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}>
            {config?.choices.payout_method.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
          </Select>
          <Input label="Numéro" type="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} required />
        </div>
        <ErrorBox message={error} />
        <Button type="submit" loading={withdraw.isPending} disabled={wallet.available <= 0}>Demander le retrait</Button>
        <p className="text-xs text-ink/60">Les retraits sont vérifiés puis envoyés par l'équipe {config?.site.name}. Vous êtes prévenu dès que c'est fait.</p>
      </form>

      {payouts.length > 0 && (
        <section className="card p-6">
          <h2 className="mb-2 font-semibold">Mes retraits</h2>
          <ul className="divide-y divide-ink/5 text-sm">
            {payouts.map((p) => (
              <li key={p.id} className="flex items-center justify-between gap-2 py-2">
                <span>{formatDate(p.created_at)} · {config?.choices.payout_method.find((c) => c.value === p.method)?.label} {p.phone}</span>
                <span className="flex items-center gap-2">
                  <Price amount={p.amount} />
                  <Badge tone={p.status === 'paid' ? 'success' : p.status === 'rejected' ? 'danger' : 'warning'}>{p.status_label}</Badge>
                </span>
              </li>
            ))}
          </ul>
        </section>
      )}

      <section className="card p-6">
        <h2 className="mb-2 font-semibold">Mouvements</h2>
        {wallet.entries.length ? (
          <ul className="divide-y divide-ink/5 text-sm">
            {wallet.entries.map((e) => (
              <li key={e.id} className="flex items-center justify-between gap-2 py-2">
                <span>
                  <span className="block font-medium">{e.kind_label}</span>
                  <span className="text-ink/60">{e.note} · {formatDate(e.created_at)}</span>
                </span>
                <span className={`shrink-0 font-semibold ${e.amount < 0 ? 'text-red-600' : 'text-green-700'}`}>
                  {e.amount > 0 ? '+' : '-'}<Price amount={Math.abs(e.amount)} />
                </span>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-sm text-ink/60">Aucun mouvement pour le moment.</p>
        )}
      </section>
    </div>
  )
}
