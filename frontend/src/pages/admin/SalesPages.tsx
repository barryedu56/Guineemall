import { useQuery } from '@tanstack/react-query'
import { Banknote, KeyRound, Package, RotateCcw, ShieldCheck, Wallet } from 'lucide-react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import Price from '../../components/Price'
import { Badge, Button, PageHeader, Spinner, StatusBadge } from '../../components/ui'
import { useSiteConfig } from '../../context/settings'
import { api, formatDate, formatPhone } from '../../lib/api'
import { BO, useAdminAction, useAdminList } from './hooks'
import { ListView, Toolbar } from './kit'
import { useAsk } from './useAsk'
import type { AdminCreditRefund, AdminDeposit, AdminOrder, AdminPayout, AdminPayoutAccount, AdminVendorOrder } from './types'

export function AdminOrders() {
  const { data: config } = useSiteConfig()
  const navigate = useNavigate()
  const query = useAdminList<AdminOrder>('orders', ['status', 'payment_status', 'payment_method', 'delivery_method'])
  return (
    <>
      <PageHeader title="Commandes" subtitle="Toutes les commandes de la plateforme." />
      <Toolbar
        searchPlaceholder="N° de commande, client, téléphone"
        filters={[
          { key: 'status', label: 'Statut', options: config?.choices.order_status ?? [] },
          { key: 'payment_status', label: 'Paiement', options: config?.choices.payment_status ?? [] },
          { key: 'payment_method', label: 'Moyen', options: config?.choices.payment_method ?? [] },
        ]}
      />
      <ListView
        query={query}
        emptyIcon={<Package />}
        emptyTitle="Aucune commande"
        onRowClick={(o) => navigate(`/administration/commandes/${o.id}`)}
        columns={[
          { label: 'N°', render: (o) => <span className="font-bold">#{o.id}</span> },
          { label: 'Date', render: (o) => formatDate(o.created_at) },
          { label: 'Client', render: (o) => <><p className="font-medium">{o.client.name}</p><p className="text-xs text-ink/50">{formatPhone(o.client.phone)}</p></> },
          { label: 'Boutiques', render: (o) => o.shops.join(', ') },
          { label: 'Total', render: (o) => <Price amount={o.total_amount} className="font-semibold" />, className: 'text-right' },
          { label: 'Paiement', render: (o) => <><p>{o.payment_method_label}</p><StatusBadge status={o.payment_status === 'paid' ? 'delivered' : o.payment_status === 'refund_pending' ? 'cancelled' : 'pending'} label={o.payment_status_label} /></> },
          { label: 'Statut', render: (o) => <StatusBadge status={o.status} label={o.status_label} /> },
        ]}
      />
    </>
  )
}

export function AdminOrderDetail() {
  const { id } = useParams()
  const { data: config } = useSiteConfig()
  const statusLabel = (value: string) => config?.choices.order_status.find((c) => c.value === value)?.label ?? value
  const action = useAdminAction('orders')
  const [ask, dialog] = useAsk()
  const { data: order, isLoading } = useQuery({
    queryKey: ['backoffice', 'orders', 'detail', id],
    queryFn: async () => (await api.get<AdminOrder>(`${BO}/orders/${id}/`)).data,
  })
  if (isLoading) return <Spinner />
  if (!order) return <p>Commande introuvable.</p>

  async function refunded() {
    const reference = await ask({ title: `Remboursement de la commande #${order!.id}`, label: 'Référence du remboursement (Orange Money, MTN…)', confirm: 'Remboursement effectué', required: true })
    if (reference) action.mutate({ id: order!.id, action: 'refunded', body: { reference }, success: 'Remboursement enregistré, client prévenu' })
  }

  return (
    <div className="space-y-4">
      {dialog}
      <PageHeader title={`Commande #${order.id}`} subtitle={formatDate(order.created_at)}>
        <StatusBadge status={order.status} label={order.status_label} />
      </PageHeader>
      {order.payment_status === 'refund_pending' && (
        <div className="flex flex-wrap items-center gap-3 rounded-2xl bg-red-50 p-4 text-red-800">
          <Banknote className="size-6" />
          <p className="flex-1 font-semibold">Le client a payé en ligne et sa commande a été annulée : il doit être remboursé ({order.payment_method_label}).</p>
          <Button variant="danger" onClick={refunded}>Marquer comme remboursé</Button>
        </div>
      )}
      <div className="grid gap-4 lg:grid-cols-2">
        <section className="card space-y-1 p-5 text-sm">
          <h2 className="mb-2 font-bold">Client et livraison</h2>
          <p className="font-semibold">{order.client.name} · {order.client.email}</p>
          {order.delivery_name && <p>Destinataire : {order.delivery_name}</p>}
          <p>{order.delivery_quartier}, {order.delivery_commune || order.delivery_city} — {order.delivery_landmark}</p>
          <p>Tél. : <a href={`tel:${order.delivery_phone}`} className="text-primary-dark">{formatPhone(order.delivery_phone)}</a></p>
        </section>
        <section className="card space-y-1 p-5 text-sm">
          <h2 className="mb-2 font-bold">Paiement · {order.payment_method_label}</h2>
          <p className="flex justify-between"><span>Articles</span><Price amount={order.subtotal} /></p>
          <p className="flex justify-between"><span>Livraison</span><Price amount={order.delivery_fee} /></p>
          {order.discount_amount > 0 && <p className="flex justify-between text-green-700"><span>Réduction</span><span>-<Price amount={order.discount_amount} /></span></p>}
          <p className="flex justify-between border-t border-ink/10 pt-1 text-base font-bold"><span>Total</span><Price amount={order.total_amount} /></p>
          <p className="text-ink/60">{order.payment_status_label}</p>
        </section>
      </div>
      {order.vendor_orders?.map((vo) => (
        <section key={vo.id} className="card space-y-3 p-5">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h2 className="font-bold">{vo.shop_name}</h2>
            <div className="flex items-center gap-2">
              {vo.funds_released_at && <Badge tone="success">Boutique payée</Badge>}
              <StatusBadge status={vo.status} label={vo.status_label} />
            </div>
          </div>
          <ul className="text-sm">
            {vo.items.map((i, n) => <li key={n} className="flex justify-between"><span>{i.quantity} × {i.name}</span><Price amount={i.quantity * i.unit_price} /></li>)}
          </ul>
          {vo.rider && <p className="text-sm">Livreur : <strong>{vo.rider.name}</strong> ({formatPhone(vo.rider.phone)}) · {vo.rider.status}</p>}
          {vo.code_blocked && <BlockedDeliveryPanel order={order} vo={vo} ask={ask} action={action} />}
        </section>
      ))}
      {!!order.history?.length && (
        <section className="card p-5">
          <h2 className="mb-3 font-bold">Historique</h2>
          <ol className="space-y-2 text-sm">
            {order.history.map((h, i) => (
              <li key={i} className="flex flex-wrap gap-x-3"><span className="text-ink/50">{formatDate(h.at)}</span><span className="font-medium">{h.note || statusLabel(h.status)}</span>{h.by && <span className="text-ink/50">par {h.by}</span>}</li>
            ))}
          </ol>
        </section>
      )}
      <Link to="/administration/commandes" className="inline-block text-sm font-semibold text-primary-dark hover:underline">← Toutes les commandes</Link>
    </div>
  )
}

export function AdminPayouts() {
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminPayout>('payouts', ['status', 'method'])
  const action = useAdminAction('payouts')
  const [ask, dialog] = useAsk()

  async function pay(p: AdminPayout) {
    const reference = await ask({ title: `Payer ${p.user.name}`, label: `Après l'envoi de l'argent (${p.method_label} ${formatPhone(p.phone)}), saisissez la référence du transfert`, confirm: 'Retrait payé', required: true })
    if (reference) action.mutate({ id: p.id, action: 'pay', body: { reference }, success: 'Retrait marqué payé, bénéficiaire prévenu' })
  }
  async function reject(p: AdminPayout) {
    const note = await ask({ title: `Refuser le retrait de ${p.user.name}`, label: 'Motif (envoyé au bénéficiaire)', confirm: 'Refuser', danger: true, required: true })
    if (note) action.mutate({ id: p.id, action: 'reject', body: { note }, success: 'Retrait refusé' })
  }

  return (
    <>
      {dialog}
      <PageHeader title="Retraits" subtitle="Demandes de retrait des boutiques, livreurs et ambassadeurs : envoyez l'argent, puis saisissez la référence." />
      <Toolbar searchPlaceholder="Nom, email, numéro, référence" filters={[{ key: 'status', label: 'Statut', options: config?.choices.payout_status ?? [] }]} />
      <ListView
        query={query}
        emptyIcon={<Wallet />}
        emptyTitle="Aucune demande de retrait"
        columns={[
          { label: 'Date', render: (p) => formatDate(p.created_at) },
          { label: 'Bénéficiaire', render: (p) => <><p className="font-medium">{p.user.name}</p><p className="text-xs text-ink/50">{p.user.email}</p></> },
          { label: 'Montant', render: (p) => <Price amount={p.amount} className="font-bold" /> },
          { label: 'Vers', render: (p) => <>{p.method_label}<p className="text-xs text-ink/50">{formatPhone(p.phone)}</p></> },
          { label: 'Solde', render: (p) => <Price amount={p.balance} className="text-ink/60" /> },
          { label: 'Statut', render: (p) => <><StatusBadge status={p.status === 'paid' ? 'delivered' : p.status === 'rejected' ? 'cancelled' : p.status === 'sending' ? 'processing' : 'pending'} label={p.status_label} />{p.transaction_reference && <p className="text-xs text-ink/50">réf. {p.transaction_reference}</p>}</> },
          { label: 'Actions', render: (p) => p.status === 'pending' && (
            <div className="flex gap-2"><Button size="sm" onClick={() => pay(p)}>Payé</Button><Button size="sm" variant="ghost" onClick={() => reject(p)}>Refuser</Button></div>
          ) },
        ]}
      />
    </>
  )
}

export function AdminPayoutAccounts() {
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminPayoutAccount>('payout-accounts', ['status', 'method'])
  const action = useAdminAction('payout-accounts')
  const [ask, dialog] = useAsk()

  async function reject(a: AdminPayoutAccount) {
    const note = await ask({ title: `Refuser le numéro de ${a.user.name}`, label: 'Motif (envoyé à la personne)', confirm: 'Refuser', danger: true, required: true })
    if (note) action.mutate({ id: a.id, action: 'reject', body: { note }, success: 'Numéro refusé, personne prévenue' })
  }

  return (
    <>
      {dialog}
      <PageHeader
        title="Numéros de retrait"
        subtitle="Avant le premier retrait, vérifiez que le numéro appartient bien à la personne : dans l'application Orange Money ou MoMo, commencez un transfert vers ce numéro (sans le valider) — le nom du titulaire s'affiche. Il doit correspondre."
      />
      <Toolbar searchPlaceholder="Nom, email, numéro, titulaire" filters={[{ key: 'status', label: 'Statut', options: config?.choices.payout_account_status ?? [] }]} />
      <ListView
        query={query}
        emptyIcon={<ShieldCheck />}
        emptyTitle="Aucun numéro à vérifier"
        columns={[
          { label: 'Modifié le', render: (a) => formatDate(a.updated_at) },
          { label: 'Compte', render: (a) => <><p className="font-medium">{a.user.name}</p><p className="text-xs text-ink/50">{a.role_label} · {a.user.email}</p></> },
          { label: 'Numéro', render: (a) => <>{a.method_label}<p className="font-semibold">{formatPhone(a.phone)}</p></> },
          { label: 'Titulaire déclaré', render: (a) => <span className="font-semibold">{a.holder_name}</span> },
          { label: 'Statut', render: (a) => <><StatusBadge status={a.status === 'verified' ? 'delivered' : a.status === 'rejected' ? 'cancelled' : 'pending'} label={a.status_label} />{a.hold_until && <p className="text-xs text-ink/50">changé : retraits après le {formatDate(a.hold_until)}</p>}</> },
          { label: 'Actions', render: (a) => a.status === 'pending' && (
            <div className="flex gap-2">
              <Button size="sm" onClick={() => action.mutate({ id: a.id, action: 'verify', success: 'Numéro vérifié, personne prévenue' })}>Le nom correspond</Button>
              <Button size="sm" variant="ghost" onClick={() => reject(a)}>Refuser</Button>
            </div>
          ) },
        ]}
      />
    </>
  )
}

export function AdminCreditRefunds() {
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminCreditRefund>('credit-refunds', ['status', 'method'])
  const action = useAdminAction('credit-refunds')
  const [ask, dialog] = useAsk()

  async function pay(r: AdminCreditRefund) {
    const reference = await ask({ title: `Rembourser ${r.user.name}`, label: `Après l'envoi de l'argent (${r.method_label} ${formatPhone(r.phone)}), saisissez la référence du transfert`, confirm: 'Remboursement envoyé', required: true })
    if (reference) action.mutate({ id: r.id, action: 'pay', body: { reference }, success: 'Remboursement enregistré, client prévenu' })
  }
  async function reject(r: AdminCreditRefund) {
    const note = await ask({ title: `Refuser la demande de ${r.user.name}`, label: "Motif (envoyé au client, son avoir lui est rendu)", confirm: 'Refuser', danger: true, required: true })
    if (note) action.mutate({ id: r.id, action: 'reject', body: { note }, success: 'Demande refusée, avoir rendu' })
  }

  return (
    <>
      {dialog}
      <PageHeader title="Avoirs clients" subtitle="Clients qui demandent leur avoir (commandes remboursées) sur Orange Money ou MTN. Le montant est déjà réservé sur leur avoir." />
      <Toolbar searchPlaceholder="Nom, email, numéro, référence" filters={[{ key: 'status', label: 'Statut', options: config?.choices.credit_refund_status ?? [] }]} />
      <ListView
        query={query}
        emptyIcon={<RotateCcw />}
        emptyTitle="Aucune demande"
        columns={[
          { label: 'Date', render: (r) => formatDate(r.created_at) },
          { label: 'Client', render: (r) => <><p className="font-medium">{r.user.name}</p><p className="text-xs text-ink/50">{r.user.email}</p></> },
          { label: 'Montant', render: (r) => <Price amount={r.amount} className="font-bold" /> },
          { label: 'Vers', render: (r) => <>{r.method_label}<p className="text-xs text-ink/50">{formatPhone(r.phone)}</p></> },
          { label: 'Avoir restant', render: (r) => <Price amount={r.credit_balance} className="text-ink/60" /> },
          { label: 'Statut', render: (r) => <><StatusBadge status={r.status === 'paid' ? 'delivered' : r.status === 'rejected' ? 'cancelled' : r.status === 'sending' ? 'processing' : 'pending'} label={r.status_label} />{r.transaction_reference && <p className="text-xs text-ink/50">réf. {r.transaction_reference}</p>}</> },
          { label: 'Actions', render: (r) => r.status === 'pending' && (
            <div className="flex gap-2"><Button size="sm" onClick={() => pay(r)}>Remboursé</Button><Button size="sm" variant="ghost" onClick={() => reject(r)}>Refuser</Button></div>
          ) },
        ]}
      />
    </>
  )
}

export function AdminDeposits() {
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminDeposit>('deposits', ['status', 'method'])
  const action = useAdminAction('deposits')
  const [ask, dialog] = useAsk()

  async function reject(d: AdminDeposit) {
    const note = await ask({ title: 'Versement non reconnu', label: 'Motif (envoyé à la personne)', confirm: 'Refuser', danger: true, required: true })
    if (note) action.mutate({ id: d.id, action: 'reject', body: { note }, success: 'Versement refusé' })
  }

  return (
    <>
      {dialog}
      <PageHeader title="Versements d'espèces" subtitle="Argent reversé par les livreurs (espèces encaissées) et les boutiques (commissions). Vérifiez la référence avant de confirmer." />
      <Toolbar searchPlaceholder="Nom, email, référence" filters={[{ key: 'status', label: 'Statut', options: config?.choices.deposit_status ?? [] }]} />
      <ListView
        query={query}
        emptyIcon={<Banknote />}
        emptyTitle="Aucun versement"
        columns={[
          { label: 'Date', render: (d) => formatDate(d.created_at) },
          { label: 'Qui', render: (d) => <><p className="font-medium">{d.user.name}</p><p className="text-xs text-ink/50">{d.user.email}</p></> },
          { label: 'Montant', render: (d) => <Price amount={d.amount} className="font-bold" /> },
          { label: 'Comment', render: (d) => <>{d.method_label}<p className="text-xs text-ink/50">réf. {d.reference}</p></> },
          { label: 'Statut', render: (d) => <StatusBadge status={d.status === 'confirmed' ? 'delivered' : d.status === 'rejected' ? 'cancelled' : 'pending'} label={d.status_label} /> },
          { label: 'Actions', render: (d) => d.status === 'pending' && (
            <div className="flex gap-2">
              <Button size="sm" onClick={() => action.mutate({ id: d.id, action: 'confirm', success: 'Versement confirmé' })}>Reçu</Button>
              <Button size="sm" variant="ghost" onClick={() => reject(d)}>Refuser</Button>
            </div>
          ) },
        ]}
      />
    </>
  )
}

type AdminOrderVO = AdminVendorOrder

/** Livraison bloquée : historique des codes, coordonnées, et trois décisions motivées (après appel du client). */
function BlockedDeliveryPanel({ order, vo, ask, action }: {
  order: AdminOrder
  vo: AdminOrderVO
  ask: ReturnType<typeof useAsk>[0]
  action: ReturnType<typeof useAdminAction>
}) {
  const decisions = [
    { value: 'received', label: 'Le client a reçu le colis', help: 'La commande passe en livrée sans code, la boutique peut être payée.', variant: 'primary' as const },
    { value: 'retry', label: 'Redonner des essais', help: "Le client s'était trompé : le code peut être saisi à nouveau.", variant: 'outline' as const },
    { value: 'cancel', label: 'Annuler la livraison', help: "Le client n'a rien reçu : stock remis, remboursement en avoir si payé en ligne.", variant: 'danger' as const },
  ]
  async function decide(d: (typeof decisions)[number]) {
    const note = await ask({ title: d.label, label: `${d.help} Motif (ce que le client a dit au téléphone) — envoyé au client et à la boutique`, confirm: 'Confirmer', danger: d.value === 'cancel', required: true })
    if (note) action.mutate({ id: order.id, action: `delivery-decision/${vo.id}`, body: { decision: d.value, note }, success: 'Décision enregistrée, client et boutique prévenus' })
  }
  return (
    <div className="space-y-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-950">
      <p className="flex items-center gap-2 font-bold"><KeyRound className="size-5" /> Livraison bloquée : {vo.delivery_code_attempts} codes faux</p>
      {vo.client_report && (
        <p className={`rounded-xl p-2 font-semibold ${vo.client_report === 'not_received' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>{vo.client_report_label}</p>
      )}
      <p className="font-medium">Appelez d'abord le client, puis la boutique :</p>
      <div className="flex flex-wrap gap-4">
        <a href={`tel:${order.delivery_phone}`} className="font-semibold text-primary-dark">Client {order.client.name} · {formatPhone(order.delivery_phone)}</a>
        <a href={`tel:${vo.shop_phone}`} className="font-semibold text-primary-dark">{vo.shop_name} · {formatPhone(vo.shop_phone)}</a>
        {vo.rider && <a href={`tel:${vo.rider.phone}`} className="font-semibold text-primary-dark">Livreur {vo.rider.name} · {formatPhone(vo.rider.phone)}</a>}
      </div>
      <details>
        <summary className="cursor-pointer font-medium">Codes saisis ({vo.code_attempts.length})</summary>
        <ol className="mt-2 space-y-1">
          {vo.code_attempts.map((a, i) => (
            <li key={i} className="flex flex-wrap gap-x-3">
              <span className="text-ink/50">{formatDate(a.at)}</span>
              <span className={a.success ? 'text-green-700' : 'text-red-700'}>{a.success ? 'bon code' : 'code faux'}</span>
              <span>par {a.by ?? 'compte supprimé'}{a.role && ` (${a.role})`}</span>
            </li>
          ))}
        </ol>
      </details>
      <div className="flex flex-wrap gap-2">
        {decisions.map((d) => <Button key={d.value} size="sm" variant={d.variant} onClick={() => decide(d)}>{d.label}</Button>)}
      </div>
    </div>
  )
}
