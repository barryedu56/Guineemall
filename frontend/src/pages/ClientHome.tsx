import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Gift, Heart, MessageCircle, Package, Sparkles, Ticket, Wallet } from 'lucide-react'
import { Link } from 'react-router-dom'

import { Avatar } from '../components/Layout'
import Price from '../components/Price'
import { ProductGrid, ProductGridSkeleton } from '../components/ProductCard'
import StatutsBar from '../components/Statuts'
import { SectionHeader } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { api, formatDate } from '../lib/api'
import { useFavorites } from '../lib/queries'
import type { Order, Paginated, ProductSummary, Referral } from '../lib/types'
import { LiveStrip } from './LivePages'
import { CampaignBanners } from './PromoPages'

/** Accueil personnalisé du client connecté (la page publique reste pour les visiteurs). */
export default function ClientHome() {
  const { user } = useAuth()
  const { data: config } = useSiteConfig()
  const favorites = useFavorites()

  const { data: orders } = useQuery({
    queryKey: ['orders', 'page', 1],
    queryFn: async () => (await api.get<Paginated<Order>>('/orders/', { params: { page: 1 } })).data,
  })
  const { data: forYou, isLoading: loadingForYou } = useQuery({
    queryKey: ['products', 'for-you'],
    queryFn: async () => (await api.get<ProductSummary[]>('/products/for-you/')).data,
  })
  const { data: referral } = useQuery({
    queryKey: ['referral'],
    queryFn: async () => (await api.get<Referral>('/auth/referral/')).data,
  })
  const { data: favoriteProducts } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/favorites/', { params: { page_size: 100 } })).data,
    enabled: favorites.enabled,
  })

  const ongoing = orders?.results.filter((o) => o.status !== 'delivered' && o.status !== 'cancelled') ?? []
  const toAct = ongoing.filter((o) => o.awaiting_payment || o.can_confirm_reception)
  const firstName = user?.name.split(' ')[0] ?? ''

  const shortcuts = [
    { to: '/commandes', label: 'Commandes', icon: Package, badge: ongoing.length },
    { to: '/favoris', label: 'Favoris', icon: Heart, badge: favoriteProducts?.count ?? 0 },
    { to: '/messages', label: 'Messages', icon: MessageCircle, badge: 0 },
    { to: '/portefeuille', label: 'Portefeuille', icon: Wallet, badge: 0 },
    { to: '/parrainage', label: 'Inviter', icon: Gift, badge: 0 },
  ]

  return (
    <div className="space-y-12">
      {/* Bienvenue + raccourcis */}
      <section className="relative overflow-hidden rounded-[2rem] bg-ink p-6 text-white sm:p-10">
        <div className="pointer-events-none absolute -right-20 -top-20 size-80 rounded-full bg-primary/25 blur-3xl" aria-hidden />
        <div className="relative flex flex-wrap items-center gap-4">
          <Avatar name={user?.name ?? ''} className="size-14 text-lg ring-4 ring-white/10" />
          <div>
            <h1 className="text-3xl font-extrabold sm:text-4xl">Bonjour {firstName} 👋</h1>
            <p className="mt-1 text-white/70">
              {ongoing.length
                ? `Vous avez ${ongoing.length} commande${ongoing.length > 1 ? 's' : ''} en cours.`
                : `Que cherchez-vous aujourd'hui sur ${config?.site.name ?? ''} ?`}
            </p>
          </div>
        </div>
        {/* Défilement horizontal sur mobile, grille sur grand écran */}
        <nav className="no-scrollbar relative -mx-6 mt-8 flex gap-2 overflow-x-auto px-6 sm:mx-0 sm:grid sm:max-w-2xl sm:grid-cols-5 sm:px-0">
          {shortcuts.map(({ to, label, icon: Icon, badge }) => (
            <Link key={to} to={to} className="group flex w-24 shrink-0 flex-col items-center gap-2 rounded-2xl bg-white/5 p-3 text-center text-xs font-semibold transition hover:bg-white/10 sm:w-auto sm:text-sm">
              <span className="relative flex size-11 items-center justify-center rounded-xl bg-primary text-ink transition group-hover:scale-105">
                <Icon className="size-5" />
                {badge > 0 && (
                  <span className="absolute -right-1.5 -top-1.5 flex min-w-5 items-center justify-center rounded-full bg-white px-1 text-[11px] font-bold text-ink">{badge}</span>
                )}
              </span>
              {label}
            </Link>
          ))}
        </nav>
      </section>

      {/* Actions qui attendent le client */}
      {toAct.length > 0 && (
        <section className="space-y-3">
          {toAct.map((o) => (
            <Link
              key={o.id}
              to={`/commandes/${o.id}`}
              className="flex flex-wrap items-center gap-4 rounded-3xl bg-primary p-5 font-semibold shadow-card transition hover:bg-primary-hover"
            >
              <Package className="size-6" />
              <span className="flex-1">
                {o.awaiting_payment
                  ? <>Commande #{o.id} en attente de paiement {o.payment_method_label} · <Price amount={o.total_amount} /></>
                  : <>Commande #{o.id} livrée : confirmez la réception pour payer la boutique</>}
              </span>
              <span className="flex items-center gap-1">{o.awaiting_payment ? 'Payer' : 'Confirmer'} <ArrowRight className="size-5" /></span>
            </Link>
          ))}
        </section>
      )}

      {/* Commandes en cours avec progression */}
      {ongoing.length > 0 && (
        <section>
          <SectionHeader title="Mes commandes en cours" action={<Link to="/commandes" className="flex items-center gap-1 hover:underline">Tout voir <ArrowRight className="size-4" /></Link>} />
          <div className="grid gap-3 md:grid-cols-2">
            {ongoing.slice(0, 4).map((o) => <OrderProgress key={o.id} order={o} />)}
          </div>
        </section>
      )}

      <StatutsBar />

      {/* Bons de réduction disponibles */}
      {!!referral?.coupons.length && (
        <section>
          <SectionHeader title={<span className="flex items-center gap-2"><Ticket className="size-6 text-primary-dark" /> Mes bons de réduction</span>} />
          <div className="no-scrollbar -mx-4 flex gap-3 overflow-x-auto px-4 pb-2">
            {referral.coupons.map((c) => (
              <div key={c.code} className="flex w-72 shrink-0 overflow-hidden rounded-3xl bg-primary-light shadow-card">
                <div className="flex w-24 flex-col items-center justify-center bg-primary p-3 text-center font-extrabold">
                  <Price amount={c.value} className="text-sm leading-tight" />
                </div>
                <div className="flex-1 border-l-2 border-dashed border-primary-dark/30 p-4">
                  <p className="font-mono font-bold tracking-wider">{c.code}</p>
                  <p className="text-xs text-ink/60">Dès <Price amount={c.min_amount} /> d'achat</p>
                  <p className="mt-1 text-xs text-ink/60">À saisir au moment de commander</p>
                </div>
              </div>
            ))}
          </div>
        </section>
      )}

      <CampaignBanners />

      <section>
        <SectionHeader title={<span className="flex items-center gap-2"><Sparkles className="size-6 text-primary-dark" /> Sélectionné pour vous</span>} />
        {loadingForYou ? <ProductGridSkeleton count={5} /> : <ProductGrid products={forYou ?? []} />}
      </section>

      <LiveStrip />

      {!!favoriteProducts?.results.length && (
        <section>
          <SectionHeader title="Vos favoris" action={<Link to="/favoris" className="flex items-center gap-1 hover:underline">Tout voir <ArrowRight className="size-4" /></Link>} />
          <ProductGrid products={favoriteProducts.results.slice(0, 5)} />
        </section>
      )}

      {/* Parrainage */}
      {referral && config && (
        <Link to="/parrainage" className="flex flex-col items-start gap-4 rounded-[2rem] bg-linear-to-r from-primary to-secondary p-6 sm:flex-row sm:items-center sm:gap-6 sm:p-8">
          <Gift className="size-12" />
          <div className="flex-1">
            <p className="text-xl font-extrabold">Invitez vos amis, gagnez <Price amount={config.referral_reward} /></p>
            <p className="text-ink/75">Votre code : <span className="font-mono font-bold">{referral.code}</span> · {referral.referrals} ami(s) inscrit(s)</p>
          </div>
          <span className="flex items-center gap-1 font-semibold">Partager <ArrowRight className="size-5" /></span>
        </Link>
      )}
    </div>
  )
}

/** Progression d'une commande d'après le statut le moins avancé de ses boutiques (étapes fournies par l'API). */
function OrderProgress({ order }: { order: Order }) {
  const { data: config } = useSiteConfig()
  const steps = config?.choices.vendor_order_status.filter((s) => s.value !== 'cancelled') ?? []
  const active = order.vendor_orders.filter((vo) => vo.status !== 'cancelled')
  const index = active.length ? Math.min(...active.map((vo) => steps.findIndex((s) => s.value === vo.status))) : 0
  const current = steps[Math.max(0, index)]

  return (
    <Link to={`/commandes/${order.id}`} className="card card-hover block p-5">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="font-bold">Commande #{order.id}</p>
          <p className="text-sm text-ink/55">{formatDate(order.created_at)} · {order.vendor_orders.map((vo) => vo.shop_name).join(', ')}</p>
        </div>
        <Price amount={order.total_amount} className="font-bold" />
      </div>
      <div className="mt-4 flex gap-1.5" aria-label={`Étape : ${current?.label}`}>
        {steps.map((s, i) => (
          <span key={s.value} className={`h-1.5 flex-1 rounded-full ${i <= index ? 'bg-primary-dark' : 'bg-ink/10'}`} />
        ))}
      </div>
      <p className="mt-2 text-sm font-semibold text-primary-dark">
        {order.awaiting_payment ? 'En attente de paiement' : current?.label}
      </p>
    </Link>
  )
}
