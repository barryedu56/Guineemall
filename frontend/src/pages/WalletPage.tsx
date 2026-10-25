import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Lock, Wallet as WalletIcon } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import Price from '../components/Price'
import { AmountInput, Badge, Button, ErrorBox, Input, PhoneInput, Select, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { useToast } from '../context/toast'
import { api, errorMessage, formatDate, formatPhone } from '../lib/api'
import type { CashDeposit, Payout, Wallet } from '../lib/types'

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

      {wallet.debt > 0 && <DepositForm debt={wallet.debt} />}

      <form onSubmit={submit} className="space-y-3 card p-6">
        <h2 className="text-lg font-semibold">Retirer mon argent</h2>
        <div className="grid gap-3 sm:grid-cols-3">
          <AmountInput label="Montant (GNF)" value={form.amount} onValueChange={(amount) => setForm({ ...form, amount })} required />
          <Select label="Vers" value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}>
            {config?.choices.payout_method.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
          </Select>
          <PhoneInput label="Numéro" value={form.phone} onValueChange={(phone) => setForm({ ...form, phone })} required />
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
                <span>{formatDate(p.created_at)} · {config?.choices.payout_method.find((c) => c.value === p.method)?.label} {formatPhone(p.phone)}</span>
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

/** Le livreur (ou la boutique) déclare avoir reversé l'argent dû ; l'équipe confirme à réception. */
function DepositForm({ debt }: { debt: number }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const { data: config } = useSiteConfig()
  const [form, setForm] = useState({ amount: String(debt), method: '', reference: '' })
  const method = form.method || config?.choices.deposit_method[0]?.value || ''
  const { data: deposits = [] } = useQuery({
    queryKey: ['deposits'],
    queryFn: async () => (await api.get<CashDeposit[]>('/wallet/deposits/')).data,
  })
  const declare = useMutation({
    mutationFn: () => api.post('/wallet/deposits/', { ...form, method, amount: Number(form.amount) }),
    onSuccess: () => {
      toast("Versement déclaré : l'équipe le vérifie")
      setForm({ ...form, reference: '' })
      queryClient.invalidateQueries({ queryKey: ['deposits'] })
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  return (
    <section className="card space-y-4 border-2 border-amber-300 p-6">
      <div>
        <h2 className="text-lg font-bold">Argent à reverser : <Price amount={debt} /></h2>
        <p className="text-sm text-ink/60">Espèces encaissées chez les clients ou commissions des ventes payées à la livraison.</p>
      </div>
      <form onSubmit={(e) => { e.preventDefault(); declare.mutate() }} className="grid gap-3 sm:grid-cols-3">
        <AmountInput label="Montant reversé (GNF)" value={form.amount} onValueChange={(amount) => setForm({ ...form, amount })} required />
        <Select label="Comment" value={method} onChange={(e) => setForm({ ...form, method: e.target.value })}>
          {config?.choices.deposit_method.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
        </Select>
        <Input label="Référence du transfert / n° de reçu" value={form.reference} onChange={(e) => setForm({ ...form, reference: e.target.value })} required />
        <Button type="submit" loading={declare.isPending} className="w-fit">J'ai reversé cet argent</Button>
      </form>
      {deposits.length > 0 && (
        <ul className="divide-y divide-ink/5 text-sm">
          {deposits.map((d) => (
            <li key={d.id} className="flex items-center justify-between gap-2 py-2">
              <span>{formatDate(d.created_at)} · {d.method_label} · réf. {d.reference}</span>
              <span className="flex items-center gap-2">
                <Price amount={d.amount} />
                <Badge tone={d.status === 'confirmed' ? 'success' : d.status === 'rejected' ? 'danger' : 'warning'}>{d.status_label}</Badge>
              </span>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
