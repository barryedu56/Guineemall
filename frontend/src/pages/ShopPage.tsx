import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, Crown, Eye, MessageCircle, MessagesSquare, Package, Pencil, Plus, Star } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { ProductGrid, ProductGridSkeleton } from '../components/ProductCard'
import { ShopAvatar } from '../components/Statuts'
import { Badge, Button, Empty, Pager, Skeleton } from '../components/ui'
import { useAuth } from '../context/auth'
import { useIsOwnShop } from '../context/ownership'
import { useToast } from '../context/toast'
import { api, errorMessage, formatNumber } from '../lib/api'
import type { Conversation, Paginated, ProductSummary, Shop } from '../lib/types'

export default function ShopPage() {
  const { slug } = useParams()
  const { user } = useAuth()
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const own = useIsOwnShop(slug)

  const { data: shop, isLoading } = useQuery({
    queryKey: ['shop', slug],
    queryFn: async () => (await api.get<Shop>(`/shops/${slug}/`)).data,
  })
  const { data: products, isLoading: loadingProducts } = useQuery({
    queryKey: ['products', 'shop', slug, page],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/products/', { params: { vendor: slug, page } })).data,
    placeholderData: keepPreviousData,
  })
  const contact = useMutation({
    mutationFn: async () => (await api.post<Conversation>('/conversations/start/', { shop: slug })).data,
    onSuccess: (c) => {
      queryClient.invalidateQueries({ queryKey: ['conversations'] })
      navigate(`/messages/${c.id}`)
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  if (isLoading) return <Skeleton className="h-56 rounded-[2rem]" />
  if (!shop) return <p className="py-16 text-center">Boutique introuvable.</p>

  const whatsapp = shop.whatsapp ? `https://wa.me/${shop.whatsapp.replace(/\D/g, '').replace(/^(?!224)/, '224')}` : null

  return (
    <>
      {own && (
        <p className="mb-4 flex items-center gap-2 rounded-2xl bg-primary-light px-4 py-3 text-sm font-medium">
          <Eye className="size-4 shrink-0" /> Aperçu : votre boutique telle que vos clients la voient. Les achats y sont désactivés pour vous.
        </p>
      )}
      <section className="card mb-8 overflow-hidden">
        <div className="h-28 bg-linear-to-r from-primary via-secondary to-primary-dark sm:h-36" />
        <div className="flex flex-col gap-4 px-6 pb-6 sm:flex-row sm:items-end">
          <ShopAvatar logo={shop.logo} name={shop.shop_name} className="-mt-12 size-24 shrink-0 ring-4 ring-surface" />
          <div className="flex-1">
            <h1 className="flex flex-wrap items-center gap-2 text-2xl font-extrabold sm:text-3xl">
              {shop.shop_name}
              {shop.is_verified && <BadgeCheck className="size-7 text-primary-dark" aria-label="Vendeur vérifié" />}
              {shop.is_premium && <Badge tone="brand"><Crown className="size-3" /> Premium</Badge>}
            </h1>
            <div className="mt-1 flex flex-wrap gap-4 text-sm text-ink/60">
              <span className="flex items-center gap-1"><Package className="size-4" /> {formatNumber(shop.products_count ?? 0)} produits</span>
              {shop.rating ? (
                <span className="flex items-center gap-1"><Star className="size-4 fill-primary text-primary" /> {shop.rating.toFixed(1)} ({shop.reviews_count} avis)</span>
              ) : null}
              <span>Sur la plateforme depuis {new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(new Date(shop.created_at))}</span>
            </div>
            {shop.description && <p className="mt-3 max-w-2xl text-ink/75">{shop.description}</p>}
          </div>
          <div className="flex flex-wrap gap-2">
            {own ? (
              <>
                <Link to="/vendeur/boutique" className="inline-flex items-center gap-2 rounded-full bg-ink px-5 py-2.5 font-semibold text-white hover:bg-ink/90">
                  <Pencil className="size-4" /> Modifier ma boutique
                </Link>
                <Link to="/vendeur/produits?modifier=nouveau" className="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-2.5 font-semibold hover:bg-primary-hover">
                  <Plus className="size-4" /> Ajouter un produit
                </Link>
              </>
            ) : (
              <>
                <Button variant="dark" loading={contact.isPending} onClick={() => (user ? contact.mutate() : navigate(`/connexion?suivant=/boutique/${slug}`))}>
                  <MessagesSquare className="size-4" /> Écrire
                </Button>
                {whatsapp && (
                  <a href={whatsapp} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-5 py-2.5 font-semibold text-white hover:opacity-90">
                    <MessageCircle className="size-4" /> WhatsApp
                  </a>
                )}
              </>
            )}
          </div>
        </div>
      </section>

      {loadingProducts ? (
        <ProductGridSkeleton />
      ) : products?.results.length ? (
        <>
          <ProductGrid products={products.results} />
          <Pager page={products.page} totalPages={products.total_pages} onChange={setPage} />
        </>
      ) : (
        <Empty icon={<Package />} title="Aucun produit pour le moment" />
      )}
    </>
  )
}
