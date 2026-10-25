import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, Banknote, Bike, ChevronRight, Lock, MapPin, ShoppingBag, Star, Store, Truck, UserRound } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'

import { ContactButtons, Countdown, DeliveryChat, Timeline } from '../../components/Delivery'
import { Button, Empty, ErrorBox, Pager, Spinner, StatusBadge } from '../../components/ui'
import { useRealtime } from '../../context/realtime'
import { useSiteConfig } from '../../context/settings'
import { useToast } from '../../context/toast'
import { api, errorMessage, formatDate, formatGNF, formatPhone, timeAgo } from '../../lib/api'
import type { CandidateRider, Paginated, VendorOrderStatus, VendorSideOrder } from '../../lib/types'
import { useMyShop } from './useMyShop'

type Counts = Record<'a-traiter' | 'preparation' | 'livraison' | 'sans_livreur', number>

/** Ce que la boutique doit faire maintenant (affiché dans la liste). */
function nextStep(order: VendorSideOrder): { text: string; urgent: boolean } {
  const courier = order.courier
  switch (order.status) {
    case 'pending':
    case 'confirmed':
      return { text: 'À accepter', urgent: true }
    case 'preparing':
      return { text: 'À préparer, puis « Colis prêt »', urgent: false }
    case 'ready':
      if (order.delivery_method === 'pickup') return { text: 'À déposer au point relais', urgent: true }
      if (!courier || courier.status === 'open') return { text: 'Choisir un livreur', urgent: true }
      if (courier.status === 'offered') return { text: `En attente de la réponse de ${courier.rider?.name}`, urgent: false }
      if (courier.status === 'assigned') return { text: `${courier.rider?.name} vient chercher le colis`, urgent: false }
      return { text: `${courier.rider?.name} livre le client`, urgent: false }
    default:
      return { text: order.status_label, urgent: false }
  }
}

function useRefreshOnDeliveryNews() {
  const queryClient = useQueryClient()
  useRealtime((event) => {
    if (event.type === 'notification' && event.notification.link.startsWith('/vendeur/commandes')) {
      queryClient.invalidateQueries({ queryKey: ['vendor-orders'] })
    }
  })
}

export default function VendorOrdersPage() {
  // Onglet dans l'adresse (?etape=livraison) : on peut y arriver directement depuis un lien
  const [params, setParams] = useSearchParams()
  const { data: config } = useSiteConfig()
  const steps = config?.choices.vendor_order_step ?? []
  const step = params.get('etape') ?? steps[0]?.value ?? 'a-traiter'
  const [page, setPage] = useState(1)
  useRefreshOnDeliveryNews()

  const { data: counts } = useQuery({
    queryKey: ['vendor-orders', 'counts'],
    queryFn: async () => (await api.get<Counts>('/vendor/orders/counts/')).data,
  })
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-orders', step, page],
    queryFn: async () => (await api.get<Paginated<VendorSideOrder>>('/vendor/orders/', { params: { page, etape: step } })).data,
    placeholderData: keepPreviousData,
  })

  return (
    <div className="space-y-4">
      <div className="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4">
        {steps.map((s) => {
          const count = counts?.[s.value as keyof Counts]
          return (
            <button
              key={s.value}
              onClick={() => {
                setParams({ etape: s.value }, { replace: true })
                setPage(1)
              }}
              className={`flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition ${step === s.value ? 'bg-ink text-white' : 'bg-surface shadow-sm hover:bg-ink/5'}`}
            >
              {s.label}
              {!!count && (
                <span className={`rounded-full px-2 text-xs ${step === s.value ? 'bg-primary text-ink' : 'bg-primary-dark text-white'}`}>{count}</span>
              )}
            </button>
          )
        })}
      </div>

      {!!counts?.sans_livreur && step !== 'livraison' && (
        <button
          onClick={() => setParams({ etape: 'livraison' }, { replace: true })}
          className="flex w-full items-center gap-3 rounded-2xl bg-amber-100 p-4 text-left font-semibold text-amber-900"
        >
          <Bike className="size-6 shrink-0" />
          <span className="flex-1">{counts.sans_livreur} colis prêt{counts.sans_livreur > 1 ? 's attendent' : ' attend'} que vous choisissiez un livreur</span>
          <ChevronRight className="size-5" />
        </button>
      )}

      {isLoading ? (
        <Spinner />
      ) : !data?.results.length ? (
        <Empty icon={<ShoppingBag />} title="Aucune commande ici" />
      ) : (
        <>
          <div className="space-y-3">
            {data.results.map((order) => (
              <OrderRow key={order.id} order={order} />
            ))}
          </div>
          <Pager page={data.page} totalPages={data.total_pages} onChange={setPage} />
        </>
      )}
    </div>
  )
}

function OrderRow({ order }: { order: VendorSideOrder }) {
  const next = nextStep(order)
  const articles = order.items.reduce((n, i) => n + i.quantity, 0)
  return (
    <Link to={`/vendeur/commandes/${order.id}`} className="card card-hover flex items-center gap-4 p-4">
      <div className="min-w-0 flex-1 space-y-1">
        <div className="flex flex-wrap items-center gap-2">
          <p className="font-bold">#{order.order_id} · {order.client_name}</p>
          <StatusBadge status={order.status} label={order.status_label} />
        </div>
        <p className="truncate text-sm text-ink/60">
          {articles} article{articles > 1 ? 's' : ''} · {order.items.map((i) => i.product_name).join(', ')}
        </p>
        <p className={`flex items-center gap-1.5 text-sm font-semibold ${next.urgent ? 'text-amber-700' : 'text-ink/70'}`}>
          {next.urgent && <span className="size-2 animate-pulse rounded-full bg-amber-500" />}
          {next.text}
          {!!order.courier?.unread && <span className="rounded-full bg-primary-dark px-2 text-xs text-white">{order.courier.unread} message(s)</span>}
        </p>
      </div>
      <div className="shrink-0 text-right">
        <p className="font-bold">{formatGNF(order.total_amount)}</p>
        <p className="text-xs text-ink/50">{timeAgo(order.created_at)}</p>
      </div>
      <ChevronRight className="size-5 shrink-0 text-ink/30" />
    </Link>
  )
}

// ---------------------------------------------------------------- Page d'une commande

export function VendorOrderDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const { data: shop } = useMyShop()
  const [error, setError] = useState<string | null>(null)
  const key = ['vendor-orders', 'detail', id]
  useRefreshOnDeliveryNews()

  const { data: order, isLoading } = useQuery({
    queryKey: key,
    queryFn: async () => (await api.get<VendorSideOrder>(`/vendor/orders/${id}/`)).data,
  })
  const run = useMutation({
    mutationFn: async ({ path, body }: { path: string; body?: object }) =>
      (await api.post<VendorSideOrder>(`/vendor/orders/${id}/${path}/`, body ?? {})).data,
    onSuccess: (updated) => {
      setError(null)
      queryClient.setQueryData(key, updated)
      queryClient.invalidateQueries({ queryKey: ['vendor-orders'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })
  const setStatus = (status: VendorOrderStatus, code?: string) =>
    run.mutate(
      { path: 'status', body: code ? { status, code } : { status } },
      { onSuccess: (o) => toast(`Commande #${o.order_id} : ${o.status_label}`) },
    )
  const action = (status: VendorOrderStatus) => config?.choices.vendor_order_status.find((c) => c.value === status)?.action ?? status

  if (isLoading) return <Spinner />
  if (!order) return <p className="py-16 text-center">Commande introuvable.</p>

  const home = order.delivery_method === 'home'
  const d = order.delivery
  const busy = (path: string) => run.isPending && run.variables?.path === path

  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
      <div className="space-y-5">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 className="text-2xl font-extrabold">Commande #{order.order_id}</h2>
            <p className="text-sm text-ink/60">{order.client_name} · {formatDate(order.created_at)}</p>
          </div>
          <StatusBadge status={order.status} label={order.status_label} />
        </div>

        <ErrorBox message={error} />

        {/* Prochaine étape : un seul geste à faire à chaque fois */}
        {(order.status === 'pending' || order.status === 'confirmed') && (
          <Step title="Nouvelle commande" text="Vérifiez que vous avez les articles, puis acceptez : le client est prévenu et vous commencez la préparation.">
            <div className="flex flex-wrap gap-2">
              <Button size="lg" loading={busy('status')} onClick={() => setStatus('preparing')}>{action('preparing')}</Button>
              <Button variant="outline" className="text-red-600" onClick={() => window.confirm('Refuser cette commande ? Le client sera prévenu et le stock remis.') && setStatus('cancelled')}>
                {action('cancelled')}
              </Button>
            </div>
          </Step>
        )}

        {order.status === 'preparing' && (
          <Step
            title="Préparez le colis"
            text={home ? 'Emballez les articles. Quand le colis est prêt, vous choisirez le livreur qui viendra le chercher.' : `Emballez les articles, puis déposez le colis au point relais ${order.pickup_point?.name ?? ''}.`}
          >
            {home && shop && !shop.has_pickup_address ? (
              <p className="flex items-start gap-2 rounded-xl bg-amber-100 p-3 text-sm text-amber-900">
                <AlertTriangle className="size-5 shrink-0" />
                <span>
                  Indiquez d'abord où le livreur doit venir chercher vos colis.{' '}
                  <Link to="/vendeur/boutique" className="font-semibold underline">Renseigner l'adresse de retrait</Link>
                </span>
              </p>
            ) : (
              <div className="flex flex-wrap gap-2">
                <Button size="lg" loading={busy('status')} onClick={() => setStatus('ready')}>{action('ready')}</Button>
                <Button variant="ghost" className="text-red-600" onClick={() => window.confirm('Annuler cette commande ? Le client sera prévenu et remboursé, le stock remis.') && setStatus('cancelled')}>
                  Annuler (rupture de stock…)
                </Button>
              </div>
            )}
          </Step>
        )}

        {order.status === 'ready' && home && (
          <CourierStep order={order} busy={busy} run={(path, body) => run.mutate({ path, body })} onSelfDeliver={(code) => setStatus('delivered', code)} />
        )}

        {order.status === 'ready' && !home && (
          <Step title="Déposez le colis au point relais" text="Quand le client vient le récupérer, demandez-lui son code de livraison.">
            {order.pickup_point && (
              <div className="rounded-xl bg-background p-3 text-sm">
                <p className="font-semibold">{order.pickup_point.name}</p>
                <p className="text-ink/70">{order.pickup_point.address}, {order.pickup_point.commune} · {order.pickup_point.opening_hours}</p>
                <div className="mt-2"><ContactButtons phone={order.pickup_point.phone} /></div>
              </div>
            )}
            <CodeForm label={action('delivered')} loading={busy('status')} onSubmit={(code) => setStatus('delivered', code)} />
          </Step>
        )}

        {order.status === 'delivered' && order.payment_method === 'cash_on_delivery' && (
          <CashHandover order={order} loading={busy('cash-received')} onConfirm={() => run.mutate({ path: 'cash-received' })} />
        )}
        {order.status === 'delivered' && order.payment_method !== 'cash_on_delivery' && (
          <Step title="Commande livrée 🎉" text={order.funds_released_at ? 'Le paiement est dans votre portefeuille.' : 'Vous serez payé dès que le client confirme la réception.'}>
            <Link to="/vendeur/portefeuille" className="text-sm font-semibold text-primary-dark hover:underline">Voir mon portefeuille →</Link>
          </Step>
        )}

        {/* Client et adresse de livraison */}
        <section className="card space-y-3 p-5">
          <h3 className="flex items-center gap-2 font-bold"><UserRound className="size-5 text-primary-dark" /> Client</h3>
          <p className="text-sm">
            <span className="font-semibold">{d.name}</span> · {formatPhone(d.phone)}
          </p>
          {home ? (
            <p className="flex items-start gap-2 text-sm text-ink/70">
              <MapPin className="mt-0.5 size-4 shrink-0 text-primary-dark" />
              {d.quartier}, {d.commune || d.city} — {d.landmark}
            </p>
          ) : (
            <p className="flex items-center gap-2 text-sm text-ink/70"><Store className="size-4 text-primary-dark" /> Retrait au point relais {order.pickup_point?.name}</p>
          )}
          {order.status !== 'cancelled' && order.status !== 'delivered' && (
            <ContactButtons phone={d.phone} message={`Bonjour, c'est ${shop?.shop_name ?? 'la boutique'} pour votre commande #${order.order_id}.`} latitude={d.latitude} longitude={d.longitude} />
          )}
        </section>
      </div>

      <aside className="space-y-5">
        <section className="card p-5">
          <h3 className="mb-4 font-bold">Suivi</h3>
          <Timeline steps={order.timeline} />
        </section>
        <section className="card p-5">
          <h3 className="mb-3 font-bold">Articles</h3>
          <ul className="space-y-1 text-sm">
            {order.items.map((item) => (
              <li key={item.id} className="flex justify-between gap-2">
                <span>{item.quantity} × {item.product_name}</span>
                <span className="shrink-0">{formatGNF(item.subtotal)}</span>
              </li>
            ))}
            {order.vendor_discount > 0 && (
              <li className="flex justify-between text-green-700"><span>Votre code promo</span><span>-{formatGNF(order.vendor_discount)}</span></li>
            )}
            <li className="flex justify-between text-ink/60"><span>Livraison</span><span>{formatGNF(order.delivery_fee)}</span></li>
            <li className="flex justify-between border-t border-ink/10 pt-1 font-semibold">
              <span>Payé par le client</span><span>{formatGNF(order.total_amount)}</span>
            </li>
            <li className="text-xs text-ink/60">{order.payment_method_label}</li>
            <li className="flex justify-between pt-2 text-ink/70">
              <span>{order.payment_method === 'cash_on_delivery' ? 'Vous touchez (espèces, sans commission)' : 'Vous touchez (après commission)'}</span>
              <span className="font-bold text-ink">{formatGNF(order.net_amount)}</span>
            </li>
          </ul>
          {order.payment_method !== 'cash_on_delivery' && !order.funds_released_at && order.status !== 'cancelled' && (
            <p className="mt-3 flex gap-2 text-xs text-ink/60">
              <Lock className="size-4 shrink-0" /> Argent gardé par {config?.site.name} jusqu'à la confirmation de réception du client.
            </p>
          )}
        </section>
        <Button variant="ghost" onClick={() => navigate('/vendeur/commandes')}>← Toutes les commandes</Button>
      </aside>
    </div>
  )
}

/** Paiement à la livraison : l'argent du client passe du livreur à la boutique, sans GuinéeMall. */
function CashHandover({ order, loading, onConfirm }: { order: VendorSideOrder; loading: boolean; onConfirm: () => void }) {
  const courier = order.courier
  if (!courier?.rider || !courier.cash_for_shop) {
    return <Step title="Commande livrée 🎉" text="Payée en espèces à la livraison : l'argent est à vous, sans commission." />
  }
  if (courier.cash_handed_at) {
    return (
      <Step title="Commande terminée 🎉" text={`Vous avez reçu ${formatGNF(courier.cash_for_shop)} de ${courier.rider.name} le ${formatDate(courier.cash_handed_at)}.`} />
    )
  }
  return (
    <Step
      title={`${courier.rider.name} doit vous remettre ${formatGNF(courier.cash_for_shop)}`}
      text={`Le client a payé ${formatGNF(order.total_amount)} en espèces. Le livreur garde sa course (${formatGNF(courier.fee)}) et vous remet le reste. Confirmez quand vous l'avez reçu.`}
    >
      <div className="flex flex-wrap items-center gap-3">
        <Button size="lg" loading={loading} onClick={() => window.confirm(`Confirmer avoir reçu ${formatGNF(courier.cash_for_shop)} de ${courier.rider!.name} ?`) && onConfirm()}>
          J'ai reçu l'argent
        </Button>
        <ContactButtons phone={courier.rider.phone} message={`Bonjour ${courier.rider.name}, c'est pour l'argent de la commande #${order.order_id}.`} />
      </div>
    </Step>
  )
}

function Step({ title, text, children }: { title: string; text: string; children?: ReactNode }) {
  return (
    <section className="space-y-3 rounded-3xl bg-primary-light/60 p-5 ring-1 ring-primary-dark/15">
      <div>
        <h3 className="text-lg font-extrabold">{title}</h3>
        <p className="text-sm text-ink/70">{text}</p>
      </div>
      {children}
    </section>
  )
}

function CodeForm({ label, loading, onSubmit }: { label: string; loading: boolean; onSubmit: (code: string) => void }) {
  const [code, setCode] = useState('')
  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        onSubmit(code)
      }}
      className="flex flex-wrap items-end gap-3"
    >
      <label className="space-y-1">
        <span className="block text-sm font-semibold">Code de livraison du client</span>
        <input
          value={code}
          onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 4))}
          inputMode="numeric"
          placeholder="••••"
          className="w-40 rounded-2xl border border-ink/15 bg-surface px-4 py-2.5 text-center font-mono text-2xl tracking-[0.4em]"
        />
      </label>
      <Button type="submit" disabled={code.length !== 4} loading={loading}>{label}</Button>
    </form>
  )
}

/** Colis prêt, livraison à domicile : choisir le livreur, le suivre, lui écrire. */
function CourierStep({
  order,
  busy,
  run,
  onSelfDeliver,
}: {
  order: VendorSideOrder
  busy: (path: string) => boolean
  run: (path: string, body?: object) => void
  onSelfDeliver: (code: string) => void
}) {
  const queryClient = useQueryClient()
  const { data: shop } = useMyShop()
  const hasPickupAddress = shop?.has_pickup_address ?? true
  const [choosing, setChoosing] = useState(false)
  const [self, setSelf] = useState(false)
  const courier = order.courier
  const rider = courier?.rider
  const open = !courier || courier.status === 'open'

  if (open || choosing) {
    return (
      <Step title="Choisissez votre livreur" text="La course est proposée au livreur choisi : il accepte ou refuse. Lui seul la voit.">
        {!hasPickupAddress ? (
          <p className="flex items-start gap-2 rounded-xl bg-amber-100 p-3 text-sm text-amber-900">
            <AlertTriangle className="size-5 shrink-0" />
            <span>
              Indiquez d'abord où le livreur doit venir chercher vos colis.{' '}
              <Link to="/vendeur/boutique" className="font-semibold underline">Renseigner l'adresse de retrait</Link>
            </span>
          </p>
        ) : (
          <RiderPicker order={order} loading={busy('assign')} onPick={(riderId) => { run('assign', { rider: riderId }); setChoosing(false) }} />
        )}
        {choosing && <Button variant="ghost" onClick={() => setChoosing(false)}>Garder {rider?.name}</Button>}
        <SelfDelivery open={self} onToggle={() => setSelf(!self)} loading={busy('status')} onSubmit={onSelfDeliver} />
      </Step>
    )
  }

  const titles: Record<string, [string, string]> = {
    offered: ['Course proposée', `En attente de la réponse de ${rider?.name}.`],
    assigned: [`${rider?.name} arrive`, 'Il a accepté et vient chercher le colis. Préparez-le à l’accueil.'],
    picked_up: [`${rider?.name} livre le client`, 'Le colis est en route. Le client donnera son code au livreur.'],
  }
  const [title, text] = titles[courier.status] ?? [courier.status_label, '']
  return (
    <Step title={title} text={text}>
      {rider && (
        <div className="flex flex-wrap items-center gap-3 rounded-2xl bg-surface p-3">
          <Avatar src={rider.avatar} />
          <div className="min-w-0 flex-1">
            <p className="font-bold">{rider.name}</p>
            <p className="text-sm text-ink/60">
              {formatPhone(rider.phone)}
              {rider.vehicle_plate && ` · moto ${rider.vehicle_plate}`}
              {rider.rating && <> · <Star className="inline size-3.5 fill-primary text-primary" /> {rider.rating}</>}
            </p>
            {courier.status === 'offered' && courier.offer_expires_at && (
              <p className="text-sm font-semibold text-amber-700">
                Réponse attendue : <Countdown until={courier.offer_expires_at} onEnd={() => queryClient.invalidateQueries({ queryKey: ['vendor-orders'] })} />
              </p>
            )}
          </div>
          <ContactButtons phone={rider.phone} message={`Bonjour ${rider.name}, c'est pour la commande #${order.order_id}.`} />
        </div>
      )}
      <div>
        <p className="mb-2 text-sm font-semibold">Discussion avec {rider?.name}</p>
        <DeliveryChat deliveryId={courier.id} partner={rider?.name ?? 'le livreur'} closed={false} />
      </div>
      {courier.status !== 'picked_up' && (
        <div className="flex flex-wrap gap-2">
          {courier.status === 'offered' && <Button variant="outline" onClick={() => setChoosing(true)}>Choisir quelqu'un d'autre</Button>}
          <Button
            variant="ghost"
            className="text-red-600"
            loading={busy('withdraw')}
            onClick={() => window.confirm(`Retirer la course à ${rider?.name} ? Il sera prévenu.`) && run('withdraw')}
          >
            Retirer la course
          </Button>
        </div>
      )}
    </Step>
  )
}

function SelfDelivery({ open, onToggle, loading, onSubmit }: { open: boolean; onToggle: () => void; loading: boolean; onSubmit: (code: string) => void }) {
  return (
    <div className="border-t border-ink/10 pt-3">
      <button onClick={onToggle} className="flex items-center gap-2 text-sm font-semibold text-ink/70 hover:text-ink">
        <Truck className="size-4" /> Je livre moi-même
      </button>
      {open && (
        <div className="mt-3">
          <CodeForm label="Remis au client" loading={loading} onSubmit={onSubmit} />
        </div>
      )}
    </div>
  )
}

function Avatar({ src }: { src: string | null }) {
  return src ? (
    <img src={src} alt="" className="size-12 shrink-0 rounded-full object-cover" />
  ) : (
    <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-light text-primary-dark"><Bike className="size-6" /></span>
  )
}

function RiderPicker({ order, loading, onPick }: { order: VendorSideOrder; loading: boolean; onPick: (riderId: number) => void }) {
  const [picked, setPicked] = useState<number | null>(null)
  const { data: riders, isLoading } = useQuery({
    queryKey: ['vendor-orders', 'riders', order.id],
    queryFn: async () => (await api.get<CandidateRider[]>(`/vendor/orders/${order.id}/riders/`)).data,
    refetchInterval: 30_000,
  })
  if (isLoading) return <Spinner />
  if (!riders?.length) {
    return (
      <p className="rounded-xl bg-surface p-4 text-sm text-ink/70">
        Aucun livreur disponible dans votre zone pour le moment. La liste se met à jour toute seule ; vous pouvez aussi livrer vous-même.
      </p>
    )
  }
  return (
    <div className="space-y-2">
      {order.cash_to_collect > 0 && (
        <p className="flex items-center gap-2 text-sm text-ink/70">
          <Banknote className="size-4" /> Paiement à la livraison : le livreur encaisse {formatGNF(order.cash_to_collect)}, garde sa course
          ({formatGNF(order.delivery_fee)}) et vous remet {formatGNF(order.cash_to_collect - order.delivery_fee)}.
        </p>
      )}
      {riders.map((r) => {
        const blocked = r.cash_blocked
        return (
          <div key={r.id} className={`flex flex-wrap items-center gap-3 rounded-2xl bg-surface p-3 ring-1 ${picked === r.id ? 'ring-2 ring-primary-dark' : 'ring-ink/10'} ${blocked ? 'opacity-60' : ''}`}>
            <Avatar src={r.avatar} />
            <div className="min-w-0 flex-1">
              <p className="font-bold">
                {r.name}
                {r.done_for_shop > 0 && <span className="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">{r.done_for_shop} livraison(s) pour vous</span>}
              </p>
              <p className="text-sm text-ink/60">
                {r.rating ? <><Star className="inline size-3.5 fill-primary text-primary" /> {r.rating} · </> : 'Nouveau · '}
                {r.done} course(s) faite(s)
                {r.busy > 0 && ` · ${r.busy} en cours`}
                {r.communes && ` · ${r.communes}`}
              </p>
              {r.declined && <p className="text-xs font-semibold text-amber-700">A déjà refusé cette course</p>}
              {blocked && <p className="text-xs font-semibold text-red-700">Doit d'abord reverser ses espèces</p>}
            </div>
            <div className="flex items-center gap-2">
              <a href={`tel:${r.phone}`} className="rounded-full p-2 text-ink/60 hover:bg-ink/5" aria-label={`Appeler ${r.name}`}>
                <span className="text-sm font-semibold">{formatPhone(r.phone)}</span>
              </a>
              <Button
                size="sm"
                disabled={blocked}
                loading={loading && picked === r.id}
                onClick={() => {
                  setPicked(r.id)
                  onPick(r.id)
                }}
              >
                Proposer la course
              </Button>
            </div>
          </div>
        )
      })}
    </div>
  )
}
