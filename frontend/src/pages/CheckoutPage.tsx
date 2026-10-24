import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Banknote, CreditCard, Gift, MapPin, ShieldCheck, Smartphone, Store, Tag } from 'lucide-react'
import { useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'

import AddressForm from '../components/AddressForm'
import Price from '../components/Price'
import { Button, ErrorBox, Input, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useCart } from '../context/cart'
import { api, errorMessage } from '../lib/api'
import { startPayment } from '../lib/payment'
import { affiliateCode } from '../lib/prefs'
import type { Address, Order, PaymentMethod, PickupPoint, Quote } from '../lib/types'

const PAYMENTS: { value: PaymentMethod; label: string; text: string; icon: typeof Banknote }[] = [
  { value: 'orange_money', label: 'Orange Money', text: 'Payez depuis votre téléphone, argent protégé', icon: Smartphone },
  { value: 'mtn_momo', label: 'MTN Mobile Money', text: 'Validez sur votre téléphone MTN', icon: Smartphone },
  { value: 'cash_on_delivery', label: 'Paiement à la livraison', text: 'En espèces, à la réception du colis', icon: Banknote },
  { value: 'card', label: 'Carte bancaire (diaspora)', text: 'Visa, Mastercard – paiement en euros', icon: CreditCard },
]

export default function CheckoutPage() {
  const { user } = useAuth()
  const { lines, loading } = useCart()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [method, setMethod] = useState<'home' | 'pickup'>('home')
  const [addressId, setAddressId] = useState<number | null>(null)
  const [pickupId, setPickupId] = useState<number | null>(null)
  const [showForm, setShowForm] = useState(false)
  const [payment, setPayment] = useState<PaymentMethod>('orange_money')
  const [payerPhone, setPayerPhone] = useState(user?.phone ?? '')
  const [couponInput, setCouponInput] = useState('')
  const [coupon, setCoupon] = useState('')
  const [gift, setGift] = useState(false)
  const [recipient, setRecipient] = useState('')
  const [error, setError] = useState<string | null>(null)

  const { data: addresses, isLoading } = useQuery({
    queryKey: ['addresses'],
    queryFn: async () => (await api.get<Address[]>('/auth/addresses/')).data,
  })
  const { data: pickupPoints = [] } = useQuery({
    queryKey: ['pickup-points'],
    queryFn: async () => (await api.get<PickupPoint[]>('/delivery/pickup-points/')).data,
  })

  // Par défaut : l'adresse marquée « par défaut », sinon la première
  const selectedAddress = addressId ?? (addresses?.find((a) => a.is_default) ?? addresses?.[0])?.id ?? null
  const formVisible = showForm || addresses?.length === 0
  const place = method === 'home' ? { address: selectedAddress } : { pickup_point: pickupId }
  const ready = method === 'home' ? !!selectedAddress : !!pickupId

  const { data: quote, error: quoteError } = useQuery({
    queryKey: ['quote', place, coupon, lines.length, lines.reduce((n, l) => n + l.quantity, 0)],
    queryFn: async () => (await api.post<Quote>('/orders/quote/', { ...place, coupon_code: coupon })).data,
    enabled: ready && lines.length > 0,
    retry: false,
  })

  const placeOrder = useMutation({
    mutationFn: async () => {
      const order = (
        await api.post<Order>('/orders/', {
          ...place,
          payment_method: payment,
          coupon_code: quote?.coupon ?? '',
          affiliate_code: affiliateCode(),
          delivery_name: gift ? recipient : '',
        })
      ).data
      return order
    },
    onSuccess: async (order) => {
      queryClient.invalidateQueries({ queryKey: ['cart'] })
      queryClient.invalidateQueries({ queryKey: ['orders'] })
      if (payment === 'cash_on_delivery') {
        navigate(`/commandes/${order.id}?nouvelle=1`, { replace: true })
        return
      }
      try {
        await startPayment(order.id, payerPhone, navigate)
      } catch (e) {
        // La commande existe : le client pourra réessayer de payer depuis son suivi
        navigate(`/commandes/${order.id}?erreur=${encodeURIComponent(errorMessage(e))}`, { replace: true })
      }
    },
    onError: (e) => setError(errorMessage(e)),
  })

  if (loading || isLoading) return <Spinner />
  if (lines.length === 0 && !placeOrder.isSuccess) return <Navigate to="/panier" replace />

  const radio = (active: boolean) =>
    `flex cursor-pointer gap-3 rounded-xl p-3 ring-1 ${active ? 'bg-primary-light/40 ring-primary-dark' : 'ring-ink/10'}`

  return (
    <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_340px]">
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Finaliser la commande</h1>

        <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
          <h2 className="mb-3 text-lg font-semibold">Livraison</h2>
          <div className="mb-4 grid grid-cols-2 gap-2">
            {[
              { value: 'home' as const, label: 'À domicile', icon: MapPin },
              { value: 'pickup' as const, label: 'Point relais (moins cher)', icon: Store },
            ].map(({ value, label, icon: Icon }) => (
              <button
                key={value}
                onClick={() => setMethod(value)}
                className={`flex items-center justify-center gap-2 rounded-xl p-3 text-sm font-medium ring-1 ${method === value ? 'bg-primary ring-primary-dark' : 'ring-ink/10'}`}
              >
                <Icon className="size-5" /> {label}
              </button>
            ))}
          </div>

          {method === 'home' ? (
            <>
              <div className="space-y-2">
                {addresses?.map((a) => (
                  <label key={a.id} className={radio(selectedAddress === a.id)}>
                    <input type="radio" checked={selectedAddress === a.id} onChange={() => setAddressId(a.id)} className="mt-1 accent-primary-dark" />
                    <span>
                      <span className="font-medium">{a.label} · {a.quartier}, {a.commune || a.city}</span>
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
                <Button variant="ghost" className="mt-2" onClick={() => setShowForm(true)}>+ Nouvelle adresse</Button>
              )}
            </>
          ) : (
            <div className="space-y-2">
              {pickupPoints.map((p) => (
                <label key={p.id} className={radio(pickupId === p.id)}>
                  <input type="radio" checked={pickupId === p.id} onChange={() => setPickupId(p.id)} className="mt-1 accent-primary-dark" />
                  <span className="flex-1">
                    <span className="flex justify-between font-medium">
                      {p.name} <Price amount={p.fee} className="text-sm" />
                    </span>
                    <span className="block text-sm text-ink/60">{p.address}, {p.commune}</span>
                    <span className="block text-sm text-ink/60">{p.opening_hours} · {p.phone}</span>
                  </span>
                </label>
              ))}
              {pickupPoints.length === 0 && <p className="text-sm text-ink/60">Aucun point relais pour le moment.</p>}
            </div>
          )}

          <label className="mt-4 flex items-center gap-2 text-sm">
            <input type="checkbox" checked={gift} onChange={(e) => setGift(e.target.checked)} className="size-4 accent-primary-dark" />
            <Gift className="size-4 text-primary-dark" /> J'offre cette commande à un proche (diaspora)
          </label>
          {gift && (
            <div className="mt-2">
              <Input label="Nom de la personne qui reçoit" value={recipient} onChange={(e) => setRecipient(e.target.value)} required />
              <p className="mt-1 text-xs text-ink/60">Le téléphone de l'adresse choisie doit être le sien : le livreur l'appellera.</p>
            </div>
          )}
        </section>

        <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
          <h2 className="mb-3 text-lg font-semibold">Paiement</h2>
          <div className="space-y-2">
            {PAYMENTS.map(({ value, label, text, icon: Icon }) => (
              <label key={value} className={`${radio(payment === value)} items-center`}>
                <input type="radio" checked={payment === value} onChange={() => setPayment(value)} className="accent-primary-dark" />
                <Icon className="size-6 text-ink/60" />
                <span>
                  <span className="block font-medium">{label}</span>
                  <span className="block text-sm text-ink/60">{text}</span>
                </span>
              </label>
            ))}
          </div>
          {payment === 'mtn_momo' && (
            <div className="mt-3">
              <Input label="Numéro MTN qui paie" type="tel" value={payerPhone} onChange={(e) => setPayerPhone(e.target.value)} placeholder="66X XX XX XX" required />
            </div>
          )}
          {payment !== 'cash_on_delivery' && (
            <p className="mt-3 flex gap-2 rounded-xl bg-green-50 p-3 text-sm text-green-800">
              <ShieldCheck className="size-5 shrink-0" />
              Paiement protégé : la boutique n'est payée qu'une fois que vous avez confirmé la réception.
            </p>
          )}
        </section>
      </div>

      <aside className="h-fit space-y-4 rounded-2xl bg-surface p-5 ring-1 ring-ink/5 lg:sticky lg:top-24">
        <h2 className="font-semibold">Récapitulatif</h2>
        <ul className="space-y-1 text-sm">
          {lines.map((l) => (
            <li key={l.id} className="flex justify-between gap-2">
              <span className="truncate">{l.quantity} × {l.product.name}</span>
              <Price amount={l.subtotal} className="shrink-0" />
            </li>
          ))}
        </ul>

        <form
          onSubmit={(e) => {
            e.preventDefault()
            setCoupon(couponInput.trim())
          }}
          className="flex gap-2"
        >
          <div className="relative flex-1">
            <Tag className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
            <input
              value={couponInput}
              onChange={(e) => setCouponInput(e.target.value.toUpperCase())}
              placeholder="Code promo"
              className="w-full rounded-xl border border-ink/15 py-2 pl-9 pr-3 uppercase"
            />
          </div>
          <Button type="submit" variant="outline">OK</Button>
        </form>
        {quote?.coupon_error && <p className="text-sm text-red-600">{quote.coupon_error}</p>}
        {quote?.coupon && <p className="text-sm text-green-700">Code {quote.coupon} appliqué ✓</p>}

        {quote ? (
          <dl className="space-y-1 border-t border-ink/10 pt-3 text-sm">
            <div className="flex justify-between"><dt>Articles</dt><dd><Price amount={quote.subtotal} /></dd></div>
            <div className="flex justify-between">
              <dt>Livraison{quote.shops.length > 1 ? ` (${quote.shops.length} boutiques)` : ''}</dt>
              <dd><Price amount={quote.delivery_fee} /></dd>
            </div>
            {quote.discount > 0 && (
              <div className="flex justify-between text-green-700"><dt>Réduction</dt><dd>-<Price amount={quote.discount} /></dd></div>
            )}
            <div className="flex justify-between pt-2 text-lg font-bold"><dt>Total</dt><dd><Price amount={quote.total} /></dd></div>
            <p className="text-xs text-ink/60">Délai indicatif : {quote.delivery_delay}</p>
          </dl>
        ) : quoteError ? (
          <ErrorBox message={errorMessage(quoteError)} />
        ) : (
          <p className="text-sm text-ink/60">Choisissez où livrer pour voir le total.</p>
        )}

        <ErrorBox message={error} />
        <Button
          className="w-full"
          disabled={!quote || (gift && !recipient.trim()) || (payment === 'mtn_momo' && !payerPhone.trim())}
          loading={placeOrder.isPending}
          onClick={() => {
            setError(null)
            placeOrder.mutate()
          }}
        >
          {payment === 'cash_on_delivery' ? 'Confirmer la commande' : 'Commander et payer'}
        </Button>
      </aside>
    </div>
  )
}
