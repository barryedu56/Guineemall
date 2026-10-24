import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Banknote, MapPin, Smartphone } from 'lucide-react'
import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'

import AddressForm from '../components/AddressForm'
import { Button, ErrorBox, Spinner } from '../components/ui'
import { useCart } from '../context/cart'
import { api, errorMessage, formatGNF } from '../lib/api'
import type { Address, Order, PaymentMethod } from '../lib/types'

const PAYMENTS: { value: PaymentMethod; label: string; text: string; icon: typeof Banknote }[] = [
  { value: 'cash_on_delivery', label: 'Paiement à la livraison', text: 'Payez en espèces à la réception', icon: Banknote },
  { value: 'orange_money', label: 'Orange Money', text: 'Bientôt : paiement direct depuis votre téléphone', icon: Smartphone },
  { value: 'mtn_momo', label: 'MTN Mobile Money', text: 'Bientôt : paiement direct depuis votre téléphone', icon: Smartphone },
]

export default function CheckoutPage() {
  const { lines, total, loading } = useCart()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [addressId, setAddressId] = useState<number | null>(null)
  const [showForm, setShowForm] = useState(false)
  const [payment, setPayment] = useState<PaymentMethod>('cash_on_delivery')
  const [error, setError] = useState<string | null>(null)

  const { data: addresses, isLoading } = useQuery({
    queryKey: ['addresses'],
    queryFn: async () => (await api.get<Address[]>('/auth/addresses/')).data,
  })

  // Par défaut : l'adresse marquée « par défaut », sinon la première
  const selectedId = addressId ?? (addresses?.find((a) => a.is_default) ?? addresses?.[0])?.id ?? null
  const formVisible = showForm || addresses?.length === 0

  const placeOrder = useMutation({
    mutationFn: async () => (await api.post<Order>('/orders/', { address: selectedId, payment_method: payment })).data,
    onSuccess: (order) => {
      queryClient.invalidateQueries({ queryKey: ['cart'] })
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      navigate(`/commandes/${order.id}?nouvelle=1`, { replace: true })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  if (loading || isLoading) return <Spinner />
  if (lines.length === 0 && !placeOrder.isSuccess) return <Navigate to="/panier" replace />

  return (
    <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_320px]">
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Finaliser la commande</h1>

        <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
          <h2 className="mb-3 flex items-center gap-2 text-lg font-semibold">
            <MapPin className="size-5 text-primary-dark" /> Adresse de livraison
          </h2>
          <div className="space-y-2">
            {addresses?.map((a) => (
              <label
                key={a.id}
                className={`flex cursor-pointer gap-3 rounded-xl p-3 ring-1 ${selectedId === a.id ? 'bg-primary-light/40 ring-primary-dark' : 'ring-ink/10'}`}
              >
                <input type="radio" checked={selectedId === a.id} onChange={() => setAddressId(a.id)} className="mt-1 accent-primary-dark" />
                <span>
                  <span className="font-medium">
                    {a.label} · {a.quartier}, {a.commune || a.city}
                  </span>
                  <span className="block text-sm text-ink/60">{a.landmark}</span>
                  <span className="block text-sm text-ink/60">{a.contact_phone}</span>
                </span>
              </label>
            ))}
          </div>
          {formVisible ? (
            <div className="mt-4">
              <AddressForm
                onSaved={(a) => {
                  setAddressId(a.id)
                  setShowForm(false)
                }}
              />
            </div>
          ) : (
            <Button variant="ghost" className="mt-2" onClick={() => setShowForm(true)}>
              + Nouvelle adresse
            </Button>
          )}
        </section>

        <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
          <h2 className="mb-3 text-lg font-semibold">Mode de paiement</h2>
          <div className="space-y-2">
            {PAYMENTS.map(({ value, label, text, icon: Icon }) => (
              <label
                key={value}
                className={`flex cursor-pointer items-center gap-3 rounded-xl p-3 ring-1 ${payment === value ? 'bg-primary-light/40 ring-primary-dark' : 'ring-ink/10'}`}
              >
                <input type="radio" checked={payment === value} onChange={() => setPayment(value)} className="accent-primary-dark" />
                <Icon className="size-6 text-ink/60" />
                <span>
                  <span className="block font-medium">{label}</span>
                  <span className="block text-sm text-ink/60">{text}</span>
                </span>
              </label>
            ))}
          </div>
        </section>
      </div>

      <aside className="h-fit space-y-4 rounded-2xl bg-surface p-5 ring-1 ring-ink/5 lg:sticky lg:top-24">
        <h2 className="font-semibold">Récapitulatif</h2>
        <ul className="space-y-1 text-sm">
          {lines.map((l) => (
            <li key={l.id} className="flex justify-between gap-2">
              <span className="truncate">
                {l.quantity} × {l.product.name}
              </span>
              <span className="shrink-0">{formatGNF(l.subtotal)}</span>
            </li>
          ))}
        </ul>
        <div className="flex justify-between border-t border-ink/10 pt-3 text-lg">
          <span>Total</span>
          <span className="font-bold">{formatGNF(total)}</span>
        </div>
        <ErrorBox message={error} />
        <Button className="w-full" disabled={!selectedId} loading={placeOrder.isPending} onClick={() => placeOrder.mutate()}>
          Confirmer la commande
        </Button>
      </aside>
    </div>
  )
}
