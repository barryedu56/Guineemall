import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CheckCircle2, Package } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'

import { Button, Empty, ErrorBox, Spinner, StatusBadge } from '../components/ui'
import { api, errorMessage, formatDate, formatGNF } from '../lib/api'
import type { Order, Paginated } from '../lib/types'

const PAYMENT_LABELS = {
  cash_on_delivery: 'Paiement à la livraison',
  orange_money: 'Orange Money',
  mtn_momo: 'MTN Mobile Money',
}

export function OrdersPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['orders'],
    queryFn: async () => (await api.get<Paginated<Order>>('/orders/')).data,
  })

  if (isLoading) return <Spinner />
  if (!data?.results.length) {
    return (
      <Empty icon={<Package />} title="Aucune commande pour le moment">
        <Link to="/produits" className="font-medium text-primary-dark hover:underline">
          Commencer mes achats
        </Link>
      </Empty>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-3">
      <h1 className="mb-4 text-2xl font-bold">Mes commandes</h1>
      {data.results.map((order) => (
        <Link
          key={order.id}
          to={`/commandes/${order.id}`}
          className="flex items-center justify-between gap-3 rounded-2xl bg-surface p-4 ring-1 ring-ink/5 hover:ring-primary"
        >
          <div>
            <p className="font-semibold">Commande #{order.id}</p>
            <p className="text-sm text-ink/60">
              {formatDate(order.created_at)} · {order.vendor_orders.reduce((n, vo) => n + vo.items.length, 0)} article(s)
            </p>
          </div>
          <div className="text-right">
            <p className="font-bold">{formatGNF(order.total_amount)}</p>
            <StatusBadge status={order.status} label={order.status_label} />
          </div>
        </Link>
      ))}
    </div>
  )
}

export function OrderDetailPage() {
  const { id } = useParams()
  const [params] = useSearchParams()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)

  const { data: order, isLoading } = useQuery({
    queryKey: ['orders', id],
    queryFn: async () => (await api.get<Order>(`/orders/${id}/`)).data,
  })

  const cancel = useMutation({
    mutationFn: async () => (await api.post<Order>(`/orders/${id}/cancel/`)).data,
    onSuccess: (updated) => {
      queryClient.setQueryData(['orders', id], updated)
      queryClient.invalidateQueries({ queryKey: ['orders'], exact: true })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  if (isLoading) return <Spinner />
  if (!order) return <p className="py-16 text-center">Commande introuvable.</p>

  const cancellable = order.vendor_orders.some((vo) => vo.status === 'pending' || vo.status === 'confirmed')

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

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-2xl font-bold">Commande #{order.id}</h1>
          <p className="text-sm text-ink/60">{formatDate(order.created_at)}</p>
        </div>
        <StatusBadge status={order.status} label={order.status_label} />
      </div>

      {order.vendor_orders.map((vo) => (
        <section key={vo.id} className="rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
          <div className="mb-2 flex items-center justify-between">
            <h2 className="font-semibold">{vo.shop_name}</h2>
            <StatusBadge status={vo.status} label={vo.status_label} />
          </div>
          <ul className="divide-y divide-ink/5 text-sm">
            {vo.items.map((item) => (
              <li key={item.id} className="flex justify-between gap-2 py-2">
                <span>
                  {item.quantity} × {item.product_slug ? <Link to={`/produit/${item.product_slug}`} className="hover:underline">{item.product_name}</Link> : item.product_name}
                </span>
                <span className="shrink-0">{formatGNF(item.subtotal)}</span>
              </li>
            ))}
          </ul>
        </section>
      ))}

      <section className="grid gap-4 rounded-2xl bg-surface p-4 ring-1 ring-ink/5 sm:grid-cols-2">
        <div>
          <h2 className="mb-1 text-sm font-semibold text-ink/60">Livraison</h2>
          <p>
            {order.delivery_quartier}, {order.delivery_commune || order.delivery_city}
          </p>
          <p className="text-sm text-ink/70">{order.delivery_landmark}</p>
          <p className="text-sm text-ink/70">{order.delivery_phone}</p>
        </div>
        <div>
          <h2 className="mb-1 text-sm font-semibold text-ink/60">Paiement</h2>
          <p>{PAYMENT_LABELS[order.payment_method]}</p>
          <p className="mt-2 text-xl font-bold">{formatGNF(order.total_amount)}</p>
        </div>
      </section>

      <ErrorBox message={error} />
      {cancellable && (
        <Button
          variant="outline"
          className="w-full text-red-600"
          loading={cancel.isPending}
          onClick={() => window.confirm('Annuler cette commande ?') && cancel.mutate()}
        >
          Annuler la commande
        </Button>
      )}
    </div>
  )
}
