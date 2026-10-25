import { useQuery } from '@tanstack/react-query'
import { ArrowRight, BadgeCheck, ShieldCheck, Smartphone, Store, Truck } from 'lucide-react'
import { Link } from 'react-router-dom'

import { ProductGrid, ProductGridSkeleton } from '../components/ProductCard'
import Stars from '../components/Stars'
import StatutsBar, { ShopAvatar } from '../components/Statuts'
import { SectionHeader, Skeleton } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { api } from '../lib/api'
import { useCategories } from '../lib/queries'
import type { Paginated, ProductSummary, Shop } from '../lib/types'
import ClientHome from './ClientHome'
import { LiveStrip } from './LivePages'
import { CampaignBanners } from './PromoPages'

function useProducts(key: string, params: Record<string, string | number>) {
  return useQuery({
    queryKey: ['products', key],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/products/', { params })).data,
  })
}

export default function HomePage() {
  const { user } = useAuth()
  // Le client connecté a son propre accueil ; les visiteurs voient la vitrine
  if (user?.role === 'client') return <ClientHome />
  return <PublicHome />
}

function PublicHome() {
  const latest = useProducts('latest', { ordering: '-created_at', page_size: 10 })
  const onSale = useProducts('on-sale', { on_sale: 'true', page_size: 5 })

  return (
    <div className="space-y-12">
      <Hero />
      <StatutsBar />
      <CampaignBanners />
      <Categories />
      <LiveStrip />

      {!!onSale.data?.results.length && (
        <section>
          <SectionHeader
            title="🔥 Bonnes affaires"
            action={<Link to="/produits?on_sale=true" className="flex items-center gap-1 hover:underline">Tout voir <ArrowRight className="size-4" /></Link>}
          />
          <ProductGrid products={onSale.data.results} />
        </section>
      )}

      <section>
        <SectionHeader
          title="Nouveautés"
          action={<Link to="/produits" className="flex items-center gap-1 hover:underline">Tout voir <ArrowRight className="size-4" /></Link>}
        />
        {latest.isLoading ? <ProductGridSkeleton /> : <ProductGrid products={latest.data?.results ?? []} />}
      </section>

      <VerifiedShops />
      <SellCallToAction />
    </div>
  )
}

function Hero() {
  const { data: config } = useSiteConfig()
  if (!config) return <Skeleton className="h-72 rounded-[2rem]" />
  const { site } = config

  // Garanties affichées selon la configuration réelle (modes de paiement actifs, séquestre, villes livrées)
  const guarantees = [
    { icon: ShieldCheck, title: 'Paiement protégé', text: `Versé à la boutique après votre réception (ou ${config.escrow_days} jours)` },
    config.payment_methods.length > 0 && {
      icon: Smartphone,
      title: 'Paiement au choix',
      text: config.payment_methods.map((m) => m.label).join(', '),
    },
    config.delivery_locations.length > 0 && {
      icon: Truck,
      title: 'Livraison à domicile',
      text: `${config.delivery_locations.length} ville${config.delivery_locations.length > 1 ? 's' : ''} desservie${config.delivery_locations.length > 1 ? 's' : ''}${config.pickup_points ? ', ou en point relais' : ''}`,
    },
  ].filter(Boolean) as { icon: typeof ShieldCheck; title: string; text: string }[]

  return (
    <section className="space-y-4">
      <div className="relative overflow-hidden rounded-[2rem] bg-linear-to-br from-primary via-primary to-secondary">
        {site.hero_image && <img src={site.hero_image} alt="" className="absolute inset-0 size-full object-cover" />}
        <div className={`relative px-6 py-12 sm:px-12 sm:py-16 ${site.hero_image ? 'bg-linear-to-r from-ink/80 via-ink/50 to-transparent text-white' : ''}`}>
          {/* Motif décoratif */}
          {!site.hero_image && (
            <div className="pointer-events-none absolute -right-16 -top-16 size-72 rounded-full bg-white/20 blur-2xl sm:size-96" aria-hidden />
          )}
          <h1 className="relative max-w-2xl text-4xl font-extrabold leading-[1.1] sm:text-5xl">{site.hero_title}</h1>
          <p className={`relative mt-4 max-w-xl text-lg ${site.hero_image ? 'text-white/85' : 'text-ink/75'}`}>{site.hero_subtitle}</p>
          <div className="relative mt-8 flex flex-wrap gap-3">
            <Link to="/produits" className="inline-flex items-center gap-2 rounded-full bg-ink px-6 py-3.5 font-semibold text-white shadow-lift transition hover:bg-ink/90">
              Découvrir les produits <ArrowRight className="size-5" />
            </Link>
            {config.products_on_sale > 0 && (
              <Link to="/produits?on_sale=true" className="inline-flex items-center rounded-full bg-white/80 px-6 py-3.5 font-semibold text-ink backdrop-blur transition hover:bg-white">
                Voir les promos
              </Link>
            )}
          </div>
        </div>
      </div>
      <div className="grid gap-3 sm:grid-cols-3">
        {guarantees.map(({ icon: Icon, title, text }) => (
          <div key={title} className="card flex items-center gap-4 p-4">
            <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary-light text-primary-dark">
              <Icon className="size-6" />
            </span>
            <div>
              <p className="font-bold">{title}</p>
              <p className="text-sm text-ink/60">{text}</p>
            </div>
          </div>
        ))}
      </div>
    </section>
  )
}

function Categories() {
  const { data: categories, isLoading } = useCategories()
  const visible = categories?.filter((c) => c.products_count > 0) ?? []
  if (isLoading) return <Skeleton className="h-28" />
  if (!visible.length) return null
  return (
    <section>
      <SectionHeader title="Catégories" />
      <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
        {visible.map((c) => (
          <Link key={c.slug} to={`/produits?category=${c.slug}`} className="card card-hover flex flex-col items-center gap-2 p-4 text-center">
            {c.image ? (
              <img src={c.image} alt="" className="size-14 rounded-2xl object-cover" />
            ) : (
              <span className="flex size-14 items-center justify-center rounded-2xl bg-primary-light text-xl font-extrabold text-primary-dark">
                {c.name[0]}
              </span>
            )}
            <span className="text-sm font-semibold leading-tight">{c.name}</span>
            <span className="text-xs text-ink/50">{c.products_count} produit{c.products_count > 1 ? 's' : ''}</span>
          </Link>
        ))}
      </div>
    </section>
  )
}

function VerifiedShops() {
  const { data } = useQuery({
    queryKey: ['shops', 'verified'],
    queryFn: async () => (await api.get<Paginated<Shop>>('/shops/', { params: { is_verified: true, page_size: 8 } })).data,
  })
  if (!data?.results.length) return null
  return (
    <section>
      <SectionHeader title={<span className="flex items-center gap-2"><BadgeCheck className="size-6 text-primary-dark" /> Boutiques vérifiées</span>} />
      <div className="no-scrollbar -mx-4 flex gap-3 overflow-x-auto px-4 pb-2">
        {data.results.map((shop) => (
          <Link key={shop.slug} to={`/boutique/${shop.slug}`} className="card card-hover flex w-60 shrink-0 items-center gap-3 p-4">
            <ShopAvatar logo={shop.logo} name={shop.shop_name} className="size-14" />
            <div className="min-w-0">
              <p className="truncate font-bold">{shop.shop_name}</p>
              {shop.rating ? <Stars rating={shop.rating} count={shop.reviews_count} /> : null}
              <p className="text-xs text-ink/50">{shop.products_count} produit{(shop.products_count ?? 0) > 1 ? 's' : ''}</p>
            </div>
          </Link>
        ))}
      </div>
    </section>
  )
}

function SellCallToAction() {
  const { data: config } = useSiteConfig()
  if (!config) return null
  return (
    <section className="grid gap-4 md:grid-cols-2">
      <Link to="/devenir-vendeur" className="group relative overflow-hidden rounded-[2rem] bg-ink p-8 text-white">
        <Store className="size-10 text-primary" />
        <p className="mt-4 text-2xl font-extrabold">Vendez sur {config.site.name}</p>
        <p className="mt-2 max-w-sm text-white/70">
          Ouvrez votre boutique gratuitement, publiez vos statuts et encaissez par Mobile Money.
          Commission de {Math.round(config.commission_rate * 100)} % seulement sur les ventes.
        </p>
        <span className="mt-6 inline-flex items-center gap-2 font-semibold text-primary">
          Ouvrir ma boutique <ArrowRight className="size-5 transition group-hover:translate-x-1" />
        </span>
      </Link>
      <Link to="/devenir-livreur" className="group relative overflow-hidden rounded-[2rem] bg-primary-light p-8">
        <Truck className="size-10 text-primary-dark" />
        <p className="mt-4 text-2xl font-extrabold">Devenez livreur</p>
        <p className="mt-2 max-w-sm text-ink/70">Livrez à moto dans votre quartier et soyez payé à chaque course, directement dans votre portefeuille.</p>
        <span className="mt-6 inline-flex items-center gap-2 font-semibold text-primary-dark">
          Postuler <ArrowRight className="size-5 transition group-hover:translate-x-1" />
        </span>
      </Link>
    </section>
  )
}
