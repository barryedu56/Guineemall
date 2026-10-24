import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Bike, Lock, MapPin, Phone, ShoppingBag, Store } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'

import { Button, Empty, ErrorBox, Pager, Spinner, StatusBadge } from '../../components/ui'
import { useSiteConfig } from '../../context/settings'
import { useToast } from '../../context/toast'
import { api, errorMessage, formatDate, formatGNF, formatPhone } from '../../lib/api'
import type { Paginated, VendorOrderStatus, VendorSideOrder } from '../../lib/types'

export default function VendorOrdersPage() {
  // Onglet dans l'adresse (?statut=pending) : on peut y arriver directement depuis un lien
  const [params, setParams] = useSearchParams()
  const filter = params.get('statut') ?? ''
  const [page, setPage] = useState(1)
  const [highlight, setHighlight] = useState<number | null>(null)
  const { data: config } = useSiteConfig()

  function openTab(status: string, orderId: number | null = null) {
    setParams(status ? { statut: status } : {}, { replace: true })
    setPage(1)
    setHighlight(orderId)
  }
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-orders', filter, page],
    queryFn: async () =>
      (await api.get<Paginated<VendorSideOrder>>('/vendor/orders/', { params: { page, ...(filter ? { status: filter } : {}) } })).data,
    placeholderData: keepPreviousData,
  })
  // Filtres = statuts définis côté serveur
  const filters = [{ value: '', label: 'Toutes' }, ...(config?.choices.vendor_order_status ?? [])]

  return (
    <div className="space-y-4">
      <div className="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4">
        {filters.map((f) => (
          <button
            key={f.value}
            onClick={() => openTab(f.value)}
            className={`shrink-0 rounded-full px-4 py-2 text-sm font-medium transition ${filter === f.value ? 'bg-ink text-white' : 'bg-surface shadow-sm hover:bg-ink/5'}`}
          >
            {f.label}
          </button>
        ))}
      </div>
      {isLoading ? (
        <Spinner />
      ) : !data?.results.length ? (
        <Empty icon={<ShoppingBag />} title="Aucune commande ici" />
      ) : (
        <>
          {data.results.map((order) => (
            <VendorOrderCard
              key={order.id}
              order={order}
              highlighted={order.id === highlight}
              // Après une action, on ouvre l'onglet du nouveau statut avec la commande en évidence
              onMoved={(status) => openTab(status, order.id)}
            />
          ))}
          <Pager page={data.page} totalPages={data.total_pages} onChange={setPage} />
        </>
      )}
    </div>
  )
}

function VendorOrderCard({
  order,
  highlighted,
  onMoved,
}: {
  order: VendorSideOrder
  highlighted: boolean
  onMoved: (status: VendorOrderStatus) => void
}) {
  const toast = useToast()
  const ref = useRef<HTMLElement>(null)
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const actionLabel = (status: VendorOrderStatus) =>
    config?.choices.vendor_order_status.find((c) => c.value === status)?.action ?? status
  const [error, setError] = useState<string | null>(null)
  const update = useMutation({
    mutationFn: (status: VendorOrderStatus) => api.post(`/vendor/orders/${order.id}/status/`, { status }),
    onSuccess: (_, status) => {
      queryClient.invalidateQueries({ queryKey: ['vendor-orders'] })
      const label = config?.choices.vendor_order_status.find((c) => c.value === status)?.label ?? status
      toast(`Commande #${order.order_id} : ${label}`)
      onMoved(status)
    },
    onError: (e) => setError(errorMessage(e)),
  })
  useEffect(() => {
    if (highlighted) ref.current?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }, [highlighted])

  const d = order.delivery
  const mapLink = d.latitude && d.longitude ? `https://www.google.com/maps?q=${d.latitude},${d.longitude}` : null

  return (
    <article ref={ref} className={`card space-y-4 p-5 transition ${highlighted ? 'ring-2 ring-primary-dark' : ''}`}>
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <p className="font-semibold">
            Commande #{order.order_id} · {order.client_name}
          </p>
          <p className="text-sm text-ink/60">{formatDate(order.created_at)}</p>
        </div>
        <StatusBadge status={order.status} label={order.status_label} />
      </div>

      <ul className="text-sm">
        {order.items.map((item) => (
          <li key={item.id} className="flex justify-between gap-2">
            <span>
              {item.quantity} × {item.product_name}
            </span>
            <span>{formatGNF(item.subtotal)}</span>
          </li>
        ))}
        {order.vendor_discount > 0 && (
          <li className="flex justify-between text-green-700"><span>Votre code promo</span><span>-{formatGNF(order.vendor_discount)}</span></li>
        )}
        <li className="flex justify-between text-ink/60"><span>Livraison</span><span>{formatGNF(order.delivery_fee)}</span></li>
        <li className="mt-1 flex justify-between border-t border-ink/10 pt-1 font-semibold">
          <span>Payé par le client · {order.payment_method_label}</span>
          <span>{formatGNF(order.total_amount)}</span>
        </li>
        <li className="flex justify-between text-ink/60">
          <span>Vous touchez pour les articles (après commission)</span>
          <span className="font-semibold text-ink">{formatGNF(order.net_amount)}</span>
        </li>
      </ul>
      {order.payment_method !== 'cash_on_delivery' && !order.funds_released_at && order.status !== 'cancelled' && (
        <p className="flex items-center gap-2 text-sm text-ink/60">
          <Lock className="size-4" /> Payé en ligne : versé dans votre portefeuille après confirmation de réception par le client.
        </p>
      )}
      {order.delivery_method === 'pickup' && (
        <p className="flex items-center gap-2 text-sm font-medium text-primary-dark"><Store className="size-4" /> À déposer au point relais</p>
      )}
      {order.tracking && order.tracking.status !== 'open' && (
        <p className="flex items-center gap-2 text-sm"><Bike className="size-4 text-primary-dark" /> {order.tracking.status_label} · {order.tracking.rider_name} {formatPhone(order.tracking.rider_phone)}</p>
      )}

      <div className="space-y-1 rounded-2xl bg-background p-4 text-sm">
        <p className="flex items-start gap-2">
          <MapPin className="mt-0.5 size-4 shrink-0 text-primary-dark" />
          <span>
            <span className="font-medium">{d.name} · </span>
            {d.quartier}, {d.commune || d.city} — {d.landmark}
            {mapLink && (
              <a href={mapLink} target="_blank" rel="noreferrer" className="ml-1 text-primary-dark underline">
                Voir sur la carte
              </a>
            )}
          </span>
        </p>
        <a href={`tel:${d.phone}`} className="flex items-center gap-2 font-medium text-primary-dark">
          <Phone className="size-4" /> {formatPhone(d.phone)}
        </a>
      </div>

      <ErrorBox message={error} />
      {order.next_statuses.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {order.next_statuses.map((status) => (
            <Button
              key={status}
              variant={status === 'cancelled' ? 'outline' : 'primary'}
              className={status === 'cancelled' ? 'text-red-600' : ''}
              loading={update.isPending && update.variables === status}
              onClick={() => (status !== 'cancelled' || window.confirm('Annuler cette commande ? Le stock sera remis.')) && update.mutate(status)}
            >
              {actionLabel(status)}
            </Button>
          ))}
        </div>
      )}
    </article>
  )
}
