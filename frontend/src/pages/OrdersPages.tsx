import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bike, CheckCircle2, Clock, MapPin, Package, Phone, Star, XCircle } from 'lucide-react'
import { useEffect, useEffectEvent, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'

import Price from '../components/Price'
import { Button, Empty, ErrorBox, PageHeader, Pager, PhoneInput, Spinner, StatusBadge } from '../components/ui'
import { useAuth } from '../context/auth'
import { useRealtime } from '../context/realtime'
import { useSiteConfig } from '../context/settings'
import { api, errorMessage, formatDate, formatPhone } from '../lib/api'
import { startPayment } from '../lib/payment'
import type { Order, Paginated } from '../lib/types'

export function OrdersPage() {
  const [page, setPage] = useState(1)
  const { data, isLoading } = useQuery({
    queryKey: ['orders', 'page', page],
    queryFn: async () => (await api.get<Paginated<Order>>('/orders/', { params: { page } })).data,
    placeholderData: keepPreviousData,
  })

  if (isLoading) return <Spinner />
  if (!data?.results.length) {
    return (
      <Empty icon={<Package />} title="Aucune commande pour le moment">
        <Link to="/produits" className="font-medium text-primary-dark hover:underline">Commencer mes achats</Link>
      </Empty>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-3">
      <PageHeader title="Mes commandes" subtitle={`${data.count} commande${data.count > 1 ? 's' : ''}`} />
      {data.results.map((order) => (
        <Link key={order.id} to={`/commandes/${order.id}`} className="card card-hover flex items-center justify-between gap-3 p-5">
          <div>
            <p className="font-bold">Commande #{order.id}</p>
            <p className="text-sm text-ink/60">
              {formatDate(order.created_at)} · {order.vendor_orders.reduce((n, vo) => n + vo.items.length, 0)} article(s)
            </p>
            {order.awaiting_payment && <p className="text-sm font-medium text-amber-700">En attente de paiement</p>}
          </div>
          <div className="text-right">
            <Price amount={order.total_amount} className="block font-bold" />
            <StatusBadge status={order.status} label={order.status_label} />
          </div>
        </Link>
      ))}
      <Pager page={data.page} totalPages={data.total_pages} onChange={setPage} />
    </div>
  )
}

/** Après un paiement : vérifie le statut toutes les 3 s tant qu'il est en attente. */
function PaymentBanner({ reference, onDone }: { reference: string; onDone: () => void }) {
  const { data } = useQuery({
    queryKey: ['payment', reference],
    queryFn: async () => (await api.get<{ status: string }>(`/payments/${reference}/`)).data,
    refetchInterval: (q) => (q.state.data?.status === 'pending' ? 3000 : false),
  })
  const status = data?.status
  const finished = useEffectEvent(onDone)
  useEffect(() => {
    if (status && status !== 'pending') finished()
  }, [status])
  if (!data) return null
  if (data.status === 'pending') {
    return (
      <div className="flex items-center gap-3 rounded-2xl bg-amber-50 p-4 text-amber-900">
        <Clock className="size-7 shrink-0 animate-pulse" />
        <p>Paiement en cours de vérification… Gardez cette page ouverte.</p>
      </div>
    )
  }
  return data.status === 'success' ? (
    <div className="flex items-center gap-3 rounded-2xl bg-green-50 p-4 text-green-800">
      <CheckCircle2 className="size-8 shrink-0" />
      <div>
        <p className="font-semibold">Paiement reçu, merci !</p>
        <p className="text-sm">Votre argent est protégé jusqu'à ce que vous confirmiez la réception.</p>
      </div>
    </div>
  ) : (
    <div className="flex items-center gap-3 rounded-2xl bg-red-50 p-4 text-red-700">
      <XCircle className="size-8 shrink-0" />
      <p>Le paiement n'a pas abouti. Vous pouvez réessayer ci-dessous.</p>
    </div>
  )
}

export function OrderDetailPage() {
  const { id } = useParams()
  const { user } = useAuth()
  const { data: config } = useSiteConfig()
  const [params] = useSearchParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(params.get('erreur'))
  const [phone, setPhone] = useState(user?.phone ?? '')
  const [riderPositions, setRiderPositions] = useState<Record<number, { latitude: string; longitude: string }>>({})
  const key = ['orders', id]

  const { data: order, isLoading } = useQuery({
    queryKey: key,
    queryFn: async () => (await api.get<Order>(`/orders/${id}/`)).data,
  })

  useRealtime((event) => {
    if (event.type === 'rider_location' && String(event.order_id) === id) {
      setRiderPositions((p) => ({ ...p, [event.order_id]: { latitude: event.latitude, longitude: event.longitude } }))
    }
    if (event.type === 'notification' && event.notification.link === `/commandes/${id}`) {
      queryClient.invalidateQueries({ queryKey: key })
    }
  })

  const action = useMutation({
    mutationFn: async (kind: 'cancel' | 'confirm' | 'pay') => {
      if (kind === 'pay') return startPayment(Number(id), phone, navigate)
      const url = kind === 'cancel' ? `/orders/${id}/cancel/` : `/orders/${id}/confirm-reception/`
      queryClient.setQueryData(key, (await api.post<Order>(url)).data)
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['orders'], exact: true }),
    onError: (e) => setError(errorMessage(e)),
  })

  if (isLoading) return <Spinner />
  if (!order) return <p className="py-16 text-center">Commande introuvable.</p>

  const cancellable = order.status !== 'cancelled' && order.vendor_orders.some((vo) => vo.status === 'pending' || vo.status === 'confirmed')
  const reference = params.get('paiement')

  return (
    <div className="mx-auto max-w-3xl space-y-4">
      {params.get('nouvelle') && (
        <div className="flex items-center gap-3 rounded-2xl bg-green-50 p-4 text-green-800">
          <CheckCircle2 className="size-8 shrink-0" />
          <div>
            <p className="font-semibold">Merci ! Votre commande est enregistrée.</p>
            <p className="text-sm">Les boutiques vont la confirmer et vous appeler pour la livraison.</p>
          </div>
        </div>
      )}
      {params.get('message') && <p className="rounded-2xl bg-blue-50 p-4 text-blue-900">{params.get('message')}</p>}
      {reference && reference !== 'annule' && (
        <PaymentBanner reference={reference} onDone={() => queryClient.invalidateQueries({ queryKey: key })} />
      )}

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-extrabold">Commande #{order.id}</h1>
          <p className="text-sm text-ink/60">{formatDate(order.created_at)}</p>
        </div>
        <StatusBadge status={order.status} label={order.status_label} />
      </div>

      {order.awaiting_payment && (
        <section className="space-y-3 rounded-2xl bg-amber-50 p-4">
          <p className="font-semibold text-amber-900">
            Paiement {order.payment_method_label} en attente
            {config ? ` – la commande sera annulée si elle n'est pas payée sous ${config.unpaid_timeout_minutes} minutes.` : '.'}
          </p>
          {order.payment_method === 'mtn_momo' && (
            <PhoneInput label="Numéro MTN qui paie" value={phone} onValueChange={setPhone} />
          )}
          <Button loading={action.isPending && action.variables === 'pay'} onClick={() => action.mutate('pay')}>
            Payer <Price amount={order.total_amount} />
          </Button>
        </section>
      )}

      {order.can_confirm_reception && (
        <section className="space-y-3 rounded-2xl bg-primary-light/50 p-4">
          <p className="font-semibold">Avez-vous bien reçu votre colis ?</p>
          <p className="text-sm text-ink/70">En confirmant, vous autorisez le paiement de la boutique.</p>
          <Button loading={action.isPending && action.variables === 'confirm'} onClick={() => action.mutate('confirm')}>
            Oui, j'ai bien reçu ma commande
          </Button>
        </section>
      )}

      {order.vendor_orders.map((vo) => {
        const position = riderPositions[order.id] ?? (vo.tracking?.latitude ? { latitude: vo.tracking.latitude, longitude: vo.tracking.longitude! } : null)
        return (
          <section key={vo.id} className="card p-5">
            <div className="mb-2 flex items-center justify-between">
              <Link to={`/boutique/${vo.shop_slug}`} className="font-semibold hover:underline">{vo.shop_name}</Link>
              <StatusBadge status={vo.status} label={vo.status_label} />
            </div>
            <ul className="divide-y divide-ink/5 text-sm">
              {vo.items.map((item) => (
                <li key={item.id} className="flex items-center justify-between gap-2 py-2">
                  <span>
                    {item.quantity} × {item.product_slug ? <Link to={`/produit/${item.product_slug}`} className="hover:underline">{item.product_name}</Link> : item.product_name}
                    {vo.status === 'delivered' && item.product_slug && (
                      <Link to={`/produit/${item.product_slug}#avis`} className="ml-2 inline-flex items-center gap-1 text-xs font-medium text-primary-dark hover:underline">
                        <Star className="size-3" /> Donner mon avis
                      </Link>
                    )}
                  </span>
                  <Price amount={item.subtotal} className="shrink-0" />
                </li>
              ))}
            </ul>
            {vo.tracking && vo.tracking.status !== 'open' && (
              <div className="mt-3 flex flex-wrap items-center gap-3 rounded-xl bg-background p-3 text-sm">
                <Bike className="size-5 text-primary-dark" />
                <span className="font-medium">{vo.tracking.status_label}</span>
                {vo.tracking.rider_name && <span>· {vo.tracking.rider_name}</span>}
                {vo.tracking.rider_phone && (
                  <a href={`tel:${vo.tracking.rider_phone}`} className="flex items-center gap-1 text-primary-dark"><Phone className="size-4" /> Appeler</a>
                )}
                {position && vo.tracking.status === 'picked_up' && (
                  <a href={`https://www.google.com/maps?q=${position.latitude},${position.longitude}`} target="_blank" rel="noreferrer" className="flex items-center gap-1 text-primary-dark underline">
                    <MapPin className="size-4" /> Voir où est le livreur
                  </a>
                )}
              </div>
            )}
          </section>
        )
      })}

      <section className="grid gap-4 card p-5 sm:grid-cols-2">
        <div>
          <h2 className="mb-1 text-sm font-semibold text-ink/60">{order.delivery_method === 'pickup' ? 'Retrait en point relais' : 'Livraison'}</h2>
          {order.delivery_name && <p className="font-medium">Pour : {order.delivery_name}</p>}
          <p>{order.pickup_point_name ?? `${order.delivery_quartier}, ${order.delivery_commune || order.delivery_city}`}</p>
          <p className="text-sm text-ink/70">{order.delivery_landmark}</p>
          <p className="text-sm text-ink/70">{formatPhone(order.delivery_phone)}</p>
        </div>
        <dl className="space-y-1 text-sm">
          <h2 className="mb-1 font-semibold text-ink/60">Paiement · {order.payment_method_label}</h2>
          <div className="flex justify-between"><dt>Articles</dt><dd><Price amount={order.subtotal} /></dd></div>
          <div className="flex justify-between"><dt>Livraison</dt><dd><Price amount={order.delivery_fee} /></dd></div>
          {order.discount_amount > 0 && (
            <div className="flex justify-between text-green-700"><dt>Réduction {order.coupon_code}</dt><dd>-<Price amount={order.discount_amount} /></dd></div>
          )}
          <div className="flex justify-between pt-1 text-lg font-bold"><dt>Total</dt><dd><Price amount={order.total_amount} /></dd></div>
          <p className="text-ink/60">{order.payment_status_label}</p>
        </dl>
      </section>

      {order.status_history.length > 0 && (
        <section className="card p-5">
          <h2 className="mb-4 font-bold">Suivi</h2>
          <ol className="relative space-y-4 border-l-2 border-ink/10 pl-6">
            {order.status_history.map((h, i) => (
              <li key={i} className="relative">
                <span className={`absolute -left-[31px] top-1 size-4 rounded-full ring-4 ring-surface ${i === order.status_history.length - 1 ? 'bg-primary-dark' : 'bg-ink/20'}`} />
                <p className="font-semibold">{h.note || config?.choices.order_status.find((c) => c.value === h.status)?.label || h.status}</p>
                <p className="text-sm text-ink/55">{formatDate(h.created_at)}</p>
              </li>
            ))}
          </ol>
        </section>
      )}

      <ErrorBox message={error} />
      {cancellable && (
        <Button
          variant="outline"
          className="w-full text-red-600"
          loading={action.isPending && action.variables === 'cancel'}
          onClick={() => window.confirm('Annuler cette commande ?') && action.mutate('cancel')}
        >
          Annuler la commande
        </Button>
      )}
    </div>
  )
}
