import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { MapPin, Phone, ShoppingBag } from 'lucide-react'
import { useState } from 'react'

import { Button, Empty, ErrorBox, Spinner, StatusBadge } from '../../components/ui'
import { api, errorMessage, formatDate, formatGNF } from '../../lib/api'
import type { Paginated, VendorOrderStatus, VendorSideOrder } from '../../lib/types'

const FILTERS: { value: string; label: string }[] = [
  { value: '', label: 'Toutes' },
  { value: 'pending', label: 'À confirmer' },
  { value: 'confirmed', label: 'Confirmées' },
  { value: 'preparing', label: 'En préparation' },
  { value: 'ready', label: 'Prêtes' },
  { value: 'delivered', label: 'Livrées' },
  { value: 'cancelled', label: 'Annulées' },
]

const ACTIONS: Record<VendorOrderStatus, string> = {
  pending: '',
  confirmed: 'Confirmer',
  preparing: 'Commencer la préparation',
  ready: 'Marquer prête',
  delivered: 'Marquer livrée',
  cancelled: 'Refuser / annuler',
}

const PAYMENT_LABELS = { cash_on_delivery: 'À la livraison', orange_money: 'Orange Money', mtn_momo: 'MTN MoMo' }

export default function VendorOrdersPage() {
  const [filter, setFilter] = useState('')
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-orders', filter],
    queryFn: async () =>
      (await api.get<Paginated<VendorSideOrder>>('/vendor/orders/', { params: filter ? { status: filter } : {} })).data,
  })

  return (
    <div className="space-y-4">
      <div className="-mx-4 flex gap-2 overflow-x-auto px-4">
        {FILTERS.map((f) => (
          <button
            key={f.value}
            onClick={() => setFilter(f.value)}
            className={`shrink-0 rounded-full px-3 py-1.5 text-sm ${filter === f.value ? 'bg-primary font-semibold' : 'bg-surface ring-1 ring-ink/10'}`}
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
        data.results.map((order) => <VendorOrderCard key={order.id} order={order} />)
      )}
    </div>
  )
}

function VendorOrderCard({ order }: { order: VendorSideOrder }) {
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const update = useMutation({
    mutationFn: (status: VendorOrderStatus) => api.post(`/vendor/orders/${order.id}/status/`, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['vendor-orders'] }),
    onError: (e) => setError(errorMessage(e)),
  })
  const d = order.delivery
  const mapLink = d.latitude && d.longitude ? `https://www.google.com/maps?q=${d.latitude},${d.longitude}` : null

  return (
    <article className="space-y-3 rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
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
        <li className="mt-1 flex justify-between border-t border-ink/10 pt-1 font-semibold">
          <span>Total · {PAYMENT_LABELS[order.payment_method]}</span>
          <span>{formatGNF(order.total_amount)}</span>
        </li>
      </ul>

      <div className="space-y-1 rounded-xl bg-background p-3 text-sm">
        <p className="flex items-start gap-2">
          <MapPin className="mt-0.5 size-4 shrink-0 text-primary-dark" />
          <span>
            {d.quartier}, {d.commune || d.city} — {d.landmark}
            {mapLink && (
              <a href={mapLink} target="_blank" rel="noreferrer" className="ml-1 text-primary-dark underline">
                Voir sur la carte
              </a>
            )}
          </span>
        </p>
        <a href={`tel:${d.phone}`} className="flex items-center gap-2 font-medium text-primary-dark">
          <Phone className="size-4" /> {d.phone}
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
              {ACTIONS[status]}
            </Button>
          ))}
        </div>
      )}
    </article>
  )
}
