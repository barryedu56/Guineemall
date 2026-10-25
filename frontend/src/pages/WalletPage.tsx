import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, Clock, Lock, RotateCcw, ShieldAlert, Wallet as WalletIcon } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import Price from '../components/Price'
import { AmountInput, Badge, Button, ErrorBox, Input, PhoneInput, Select, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { useToast } from '../context/toast'
import { api, errorMessage, formatDate, formatPhone } from '../lib/api'
import type { CashDeposit, Payout, PayoutAccount, StoreCredit, Wallet } from '../lib/types'

/** Portefeuille : vendeurs (ventes), ambassadeurs (commissions), livreurs (courses). */
export default function WalletPage() {
  const { data: config } = useSiteConfig()
  const queryClient = useQueryClient()
  const [amount, setAmount] = useState('')
  const [error, setError] = useState<string | null>(null)
  const { data: account } = usePayoutAccount()
  const ready = accountReady(account)

  const { data: wallet, isLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => (await api.get<Wallet>('/wallet/')).data,
  })
  const { data: payouts = [] } = useQuery({
    queryKey: ['payouts'],
    queryFn: async () => (await api.get<Payout[]>('/wallet/payouts/')).data,
  })

  const withdraw = useMutation({
    mutationFn: () => api.post('/wallet/payouts/', { amount: Number(amount) }),
    onSuccess: () => {
      setAmount('')
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
      <StoreCreditSection />
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

      <PayoutAccountCard />

      <form onSubmit={submit} className="space-y-3 card p-6">
        <h2 className="text-lg font-semibold">Retirer mon argent</h2>
        {ready && account ? (
          <>
            <div className="flex flex-wrap items-end gap-3">
              <AmountInput label="Montant (GNF)" value={amount} onValueChange={setAmount} required />
              <p className="pb-2.5 text-sm text-ink/70">vers {account.method_label} <strong>{formatPhone(account.phone)}</strong> ({account.holder_name})</p>
            </div>
            <ErrorBox message={error} />
            <Button type="submit" loading={withdraw.isPending} disabled={wallet.available <= 0}>Demander le retrait</Button>
            <p className="text-xs text-ink/60">Vous êtes prévenu dès que l'argent est envoyé.</p>
          </>
        ) : (
          <p className="text-sm text-ink/60">Les retraits s'ouvrent dès que votre numéro de retrait est vérifié (ci-dessus).</p>
        )}
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
                  <Badge tone={p.status === 'paid' ? 'success' : p.status === 'rejected' ? 'danger' : p.status === 'sending' ? 'info' : 'warning'}>{p.status_label}</Badge>
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

/** Avoir du client (commandes remboursées) : dépensé à la commande ou rendu sur Mobile Money. */
function StoreCreditSection() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const [open, setOpen] = useState(false)
  const [amount, setAmount] = useState('')
  const { data: account } = usePayoutAccount()
  const { data: credit } = useQuery({
    queryKey: ['store-credit'],
    queryFn: async () => (await api.get<StoreCredit>('/wallet/credit/')).data,
  })
  const cashOut = useMutation({
    mutationFn: () => api.post('/wallet/credit/cash-out/', { amount: Number(amount) }),
    onSuccess: () => {
      toast("Demande envoyée : vous êtes prévenu dès que l'argent est envoyé")
      setOpen(false)
      setAmount('')
      queryClient.invalidateQueries({ queryKey: ['store-credit'] })
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
  if (!credit || (credit.balance === 0 && credit.entries.length === 0)) return null

  return (
    <section className="card space-y-4 p-6">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="flex items-center gap-2 text-ink/60"><RotateCcw className="size-5" /> Mon avoir {config?.site.name}</p>
          <Price amount={credit.balance} className="mt-1 block text-3xl font-extrabold" />
          <p className="text-sm text-ink/60">Argent de vos commandes annulées. Il se déduit automatiquement de votre prochaine commande.</p>
        </div>
        {credit.balance > 0 && !open && (
          <Button variant="outline" onClick={() => { setAmount(String(credit.balance)); setOpen(true) }}>
            Le recevoir sur mon Mobile Money
          </Button>
        )}
      </div>
      {open && (
        <form onSubmit={(e) => { e.preventDefault(); cashOut.mutate() }} className="space-y-3 rounded-2xl bg-background p-4">
          {accountReady(account) && account ? (
            <div className="flex flex-wrap items-end gap-3">
              <AmountInput label="Montant (GNF)" value={amount} onValueChange={setAmount} required />
              <p className="pb-2.5 text-sm text-ink/70">vers {account.method_label} <strong>{formatPhone(account.phone)}</strong></p>
            </div>
          ) : (
            <p className="text-sm text-ink/70">Enregistrez d'abord votre numéro de retrait ci-dessous ; l'équipe le vérifie, puis vous pourrez recevoir votre avoir.</p>
          )}
          <div className="flex gap-2">
            <Button type="submit" loading={cashOut.isPending} disabled={!accountReady(account)}>Demander le remboursement</Button>
            <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Annuler</Button>
          </div>
        </form>
      )}
      {credit.refunds.length > 0 && (
        <ul className="divide-y divide-ink/5 text-sm">
          {credit.refunds.map((r) => (
            <li key={r.id} className="flex items-center justify-between gap-2 py-2">
              <span>{formatDate(r.created_at)} · {r.method_label} {formatPhone(r.phone)}{r.admin_note && ` · ${r.admin_note}`}</span>
              <span className="flex items-center gap-2">
                <Price amount={r.amount} />
                <Badge tone={r.status === 'paid' ? 'success' : r.status === 'rejected' ? 'danger' : 'warning'}>{r.status_label}</Badge>
              </span>
            </li>
          ))}
        </ul>
      )}
      <details className="text-sm">
        <summary className="cursor-pointer font-semibold">Historique de l'avoir</summary>
        <ul className="mt-2 divide-y divide-ink/5">
          {credit.entries.map((e) => (
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
      </details>
    </section>
  )
}

function usePayoutAccount() {
  return useQuery({
    queryKey: ['payout-account'],
    queryFn: async () => (await api.get<PayoutAccount | null>('/wallet/account/')).data,
  })
}

function accountReady(account: PayoutAccount | null | undefined) {
  return !!account?.ready
}

/** Numéro qui reçoit l'argent : enregistré une fois, vérifié par l'équipe, modifiable avec le mot de passe. */
function PayoutAccountCard() {
  const { user } = useAuth()
  const toast = useToast()
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const { data: account, isLoading } = usePayoutAccount()
  const [editing, setEditing] = useState(false)
  const [form, setForm] = useState({ method: '', phone: '', holder_name: '', password: '' })
  const save = useMutation({
    mutationFn: async () => (await api.put<PayoutAccount>('/wallet/account/', { ...form, method: form.method || config?.choices.payout_method[0]?.value })).data,
    onSuccess: (data) => {
      queryClient.setQueryData(['payout-account'], data)
      setEditing(false)
      toast("Numéro enregistré : l'équipe le vérifie")
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
  if (isLoading) return null

  function edit() {
    setForm({ method: account?.method ?? '', phone: account?.phone ?? user?.phone ?? '', holder_name: account?.holder_name ?? user?.name ?? '', password: '' })
    setEditing(true)
  }

  if (editing || !account) {
    if (!editing && !account) {
      return (
        <section className="card flex flex-wrap items-center gap-4 p-6">
          <ShieldAlert className="size-8 text-amber-600" />
          <div className="flex-1">
            <p className="font-semibold">Où voulez-vous recevoir votre argent ?</p>
            <p className="text-sm text-ink/60">Enregistrez une fois votre numéro Orange Money ou MTN. Pour votre sécurité, l'argent ne partira que vers ce numéro.</p>
          </div>
          <Button onClick={edit}>Enregistrer mon numéro</Button>
        </section>
      )
    }
    return (
      <form onSubmit={(e) => { e.preventDefault(); save.mutate() }} className="card space-y-3 p-6">
        <h2 className="text-lg font-semibold">Numéro de retrait</h2>
        <div className="grid gap-3 sm:grid-cols-3">
          <Select label="Opérateur" value={form.method || config?.choices.payout_method[0]?.value} onChange={(e) => setForm({ ...form, method: e.target.value })}>
            {config?.choices.payout_method.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
          </Select>
          <PhoneInput label="Numéro" value={form.phone} onValueChange={(phone) => setForm({ ...form, phone })} required />
          <Input label="Nom du titulaire" value={form.holder_name} onChange={(e) => setForm({ ...form, holder_name: e.target.value })} required hint="Tel qu'enregistré chez l'opérateur" />
        </div>
        <Input label="Votre mot de passe" type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required autoComplete="current-password" hint="Redemandé pour vérifier que c'est bien vous" />
        {account?.status === 'verified' && (
          <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
            Après un changement, le nouveau numéro est revérifié et les retraits sont bloqués 48 h par sécurité.
          </p>
        )}
        <div className="flex gap-2">
          <Button type="submit" loading={save.isPending}>Enregistrer</Button>
          <Button type="button" variant="ghost" onClick={() => setEditing(false)}>Annuler</Button>
        </div>
      </form>
    )
  }

  const onHold = account.status === 'verified' && !account.ready
  return (
    <section className="card flex flex-wrap items-center gap-4 p-6">
      {account.status === 'verified' && !onHold ? (
        <BadgeCheck className="size-8 text-green-600" />
      ) : account.status === 'rejected' ? (
        <ShieldAlert className="size-8 text-red-600" />
      ) : (
        <Clock className="size-8 text-amber-600" />
      )}
      <div className="flex-1">
        <p className="font-semibold">{account.method_label} · {formatPhone(account.phone)} · {account.holder_name}</p>
        <p className="text-sm text-ink/60">
          {account.status === 'rejected'
            ? `Refusé${account.admin_note ? ` : ${account.admin_note}` : ''}. Corrigez votre numéro.`
            : account.status === 'pending'
              ? "En cours de vérification par l'équipe (nom du titulaire). Vous serez prévenu."
              : onHold
                ? `Numéro vérifié. Par sécurité, retraits possibles à partir du ${formatDate(account.hold_until!)}.`
                : 'Numéro vérifié : votre argent est envoyé ici.'}
        </p>
      </div>
      <Button variant="outline" size="sm" onClick={edit}>Changer</Button>
    </section>
  )
}
