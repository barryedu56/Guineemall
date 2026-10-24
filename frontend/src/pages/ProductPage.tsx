import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, ChevronRight, Eye, Heart, ImageOff, MessageCircle, MessagesSquare, Minus, Pencil, Plus, ShieldCheck, ShoppingBag, Truck } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import Price from '../components/Price'
import { ProductGrid } from '../components/ProductCard'
import ShareButton from '../components/ShareButton'
import Stars from '../components/Stars'
import { ShopAvatar } from '../components/Statuts'
import { Button, SectionHeader, Skeleton } from '../components/ui'
import { useAuth } from '../context/auth'
import { useCart } from '../context/cart'
import { useIsOwnShop } from '../context/ownership'
import { useSiteConfig } from '../context/settings'
import { useToast } from '../context/toast'
import { api, errorMessage } from '../lib/api'
import { useFavorites } from '../lib/queries'
import type { Conversation, Paginated, ProductDetail, ProductSummary } from '../lib/types'
import Reviews from './Reviews'

export default function ProductPage() {
  const { slug } = useParams()
  const { data: product, isLoading } = useQuery({
    queryKey: ['product', slug],
    queryFn: async () => (await api.get<ProductDetail>(`/products/${slug}/`)).data,
  })

  if (isLoading) {
    return (
      <div className="grid gap-8 md:grid-cols-2">
        <Skeleton className="aspect-square rounded-[2rem]" />
        <div className="space-y-4"><Skeleton className="h-10 w-3/4" /><Skeleton className="h-8 w-1/3" /><Skeleton className="h-40" /></div>
      </div>
    )
  }
  if (!product) return <p className="py-16 text-center">Produit introuvable.</p>
  // key : l'état (photo, quantité) repart à zéro quand on passe à un autre produit
  return <ProductView key={product.id} product={product} />
}

function ProductView({ product }: { product: ProductDetail }) {
  const navigate = useNavigate()
  const { user } = useAuth()
  const { add } = useCart()
  const toast = useToast()
  const queryClient = useQueryClient()
  const favorites = useFavorites()
  const { data: config } = useSiteConfig()
  const [imageIndex, setImageIndex] = useState(0)
  const [quantity, setQuantity] = useState(1)
  // Le vendeur qui consulte son propre produit le gère, il ne l'achète pas
  const own = useIsOwnShop(product.vendor.slug)

  const { data: related } = useQuery({
    queryKey: ['products', 'related', product.category.slug],
    queryFn: async () =>
      (await api.get<Paginated<ProductSummary>>('/products/', { params: { category: product.category.slug, page_size: 6 } })).data,
  })

  const contact = useMutation({
    mutationFn: async () => (await api.post<Conversation>('/conversations/start/', { shop: product.vendor.slug, product: product.slug })).data,
    onSuccess: (c) => {
      queryClient.invalidateQueries({ queryKey: ['conversations'] })
      navigate(`/messages/${c.id}`)
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })

  const summary: ProductSummary = {
    ...product,
    image: product.images[0]?.image ?? null,
    vendor: product.vendor.slug,
    category: product.category.slug,
  }
  const favorite = favorites.isFavorite(product.slug)
  const discount = product.compare_at_price ? Math.round((1 - product.price / product.compare_at_price) * 100) : 0

  async function addToCart(goToCart: boolean) {
    try {
      await add(summary, quantity)
      if (goToCart) navigate('/panier')
      else toast(`${quantity} × « ${product.name} » ajouté au panier`)
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  const whatsappLink = product.vendor.whatsapp
    ? `https://wa.me/${product.vendor.whatsapp.replace(/\D/g, '').replace(/^(?!224)/, '224')}?text=${encodeURIComponent(
        `Bonjour, je suis intéressé(e) par « ${product.name} » vu sur ${config?.site.name ?? ''} : ${window.location.href}`,
      )}`
    : null

  return (
    <>
      <nav className="mb-4 flex items-center gap-1 text-sm text-ink/55" aria-label="Fil d'Ariane">
        <Link to="/" className="hover:text-ink">Accueil</Link>
        <ChevronRight className="size-4" />
        <Link to={`/produits?category=${product.category.slug}`} className="hover:text-ink">{product.category.name}</Link>
        <ChevronRight className="size-4" />
        <span className="truncate text-ink">{product.name}</span>
      </nav>

      <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
        {/* Galerie */}
        <div className="space-y-3 lg:sticky lg:top-40 lg:self-start">
          <div className="card relative aspect-square overflow-hidden">
            {product.images.length ? (
              <img src={product.images[imageIndex]?.image} alt={product.name} className="size-full object-cover" />
            ) : (
              <ImageOff className="absolute inset-0 m-auto size-16 text-ink/20" />
            )}
            {discount > 0 && <span className="absolute left-4 top-4 rounded-full bg-danger px-3 py-1 text-sm font-bold text-white">-{discount}%</span>}
          </div>
          {product.images.length > 1 && (
            <div className="no-scrollbar flex gap-2 overflow-x-auto">
              {product.images.map((img, i) => (
                <button
                  key={img.id}
                  onClick={() => setImageIndex(i)}
                  className={`size-20 shrink-0 overflow-hidden rounded-2xl ring-2 transition ${i === imageIndex ? 'ring-primary-dark' : 'ring-transparent opacity-70 hover:opacity-100'}`}
                  aria-label={`Photo ${i + 1}`}
                >
                  <img src={img.image} alt="" className="size-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Achat */}
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-extrabold leading-tight sm:text-4xl">{product.name}</h1>
            <div className="mt-2 flex flex-wrap items-center gap-3">
              {product.rating ? (
                <a href="#avis"><Stars rating={product.rating} count={product.reviews_count} size="md" /></a>
              ) : (
                <span className="text-sm text-ink/50">Pas encore d'avis</span>
              )}
            </div>
          </div>

          <div className="flex flex-wrap items-baseline gap-3">
            <Price amount={product.price} className="text-4xl font-extrabold" />
            {product.compare_at_price && <Price amount={product.compare_at_price} className="text-xl text-ink/40 line-through" />}
          </div>

          <p className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold ${product.stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'}`}>
            <span className={`size-2 rounded-full ${product.stock > 0 ? 'bg-green-600' : 'bg-red-600'}`} />
            {product.stock > 0 ? `En stock · ${product.stock} disponible${product.stock > 1 ? 's' : ''}` : 'Épuisé'}
          </p>

          {own ? (
            <div className="card space-y-4 p-5">
              <p className="flex items-center gap-2 text-sm font-medium text-ink/70">
                <Eye className="size-4" /> Aperçu de votre produit tel que vos clients le voient.
              </p>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="rounded-2xl bg-background p-3">
                  <p className="text-ink/55">Stock</p>
                  <p className={`text-lg font-bold ${product.stock === 0 ? 'text-danger' : ''}`}>{product.stock === 0 ? 'Épuisé' : product.stock}</p>
                </div>
                <div className="rounded-2xl bg-background p-3">
                  <p className="text-ink/55">Avis</p>
                  <p className="text-lg font-bold">{product.reviews_count}</p>
                </div>
              </div>
              <div className="flex flex-wrap gap-2">
                <Link to={`/vendeur/produits?modifier=${product.id}`} className="inline-flex items-center gap-2 rounded-full bg-ink px-5 py-2.5 font-semibold text-white hover:bg-ink/90">
                  <Pencil className="size-4" /> Modifier ce produit
                </Link>
                <ShareButton slug={product.slug} name={product.name} />
              </div>
            </div>
          ) : (
            <>
          {product.stock > 0 && (
            <div className="flex items-center gap-4">
              <span className="font-semibold">Quantité</span>
              <div className="flex items-center rounded-full border border-ink/15">
                <button className="rounded-full p-3 hover:bg-ink/5 disabled:opacity-30" disabled={quantity <= 1} onClick={() => setQuantity(quantity - 1)} aria-label="Moins">
                  <Minus className="size-4" />
                </button>
                <span className="w-10 text-center font-bold tabular-nums">{quantity}</span>
                <button className="rounded-full p-3 hover:bg-ink/5 disabled:opacity-30" disabled={quantity >= product.stock} onClick={() => setQuantity(quantity + 1)} aria-label="Plus">
                  <Plus className="size-4" />
                </button>
              </div>
            </div>
          )}

          <div className="hidden gap-3 sm:flex">
            <Button size="lg" variant="dark" className="flex-1" disabled={product.stock === 0} onClick={() => addToCart(true)}>Acheter maintenant</Button>
            <Button size="lg" className="flex-1" disabled={product.stock === 0} onClick={() => addToCart(false)}>
              <ShoppingBag className="size-5" /> Ajouter au panier
            </Button>
          </div>

          <div className="flex flex-wrap gap-2">
            <Button variant="outline" size="sm" onClick={() => (favorites.enabled ? favorites.toggle.mutate(product.slug) : navigate(`/connexion?suivant=/produit/${product.slug}`))}>
              <Heart className={`size-4 ${favorite ? 'fill-danger text-danger' : ''}`} /> {favorite ? 'Dans mes favoris' : 'Favori'}
            </Button>
            <Button variant="outline" size="sm" loading={contact.isPending} onClick={() => (user ? contact.mutate() : navigate(`/connexion?suivant=/produit/${product.slug}`))}>
              <MessagesSquare className="size-4" /> Écrire à la boutique
            </Button>
            <ShareButton slug={product.slug} name={product.name} />
            {whatsappLink && (
              <a href={whatsappLink} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-3 py-1.5 text-sm font-semibold text-white hover:opacity-90">
                <MessageCircle className="size-4" /> WhatsApp
              </a>
            )}
          </div>
            </>
          )}

          {config && (
            <div className="card divide-y divide-ink/5">
              <div className="flex gap-3 p-4">
                <ShieldCheck className="size-6 shrink-0 text-primary-dark" />
                <p className="text-sm"><span className="font-semibold">Paiement protégé.</span> La boutique est payée après votre réception, ou {config.escrow_days} jours après la livraison.</p>
              </div>
              <div className="flex gap-3 p-4">
                <Truck className="size-6 shrink-0 text-primary-dark" />
                <p className="text-sm"><span className="font-semibold">Livraison ou point relais.</span> Le prix exact dépend de votre commune et s'affiche avant de payer.</p>
              </div>
            </div>
          )}

          {!own && <Link to={`/boutique/${product.vendor.slug}`} className="card card-hover flex items-center gap-4 p-4">
            <ShopAvatar logo={product.vendor.logo} name={product.vendor.shop_name} className="size-14" />
            <div className="min-w-0 flex-1">
              <p className="text-xs font-semibold uppercase tracking-wider text-ink/45">Vendu par</p>
              <p className="flex items-center gap-1.5 font-bold">
                {product.vendor.shop_name}
                {product.vendor.is_verified && <BadgeCheck className="size-5 text-primary-dark" aria-label="Vendeur vérifié" />}
              </p>
              {product.vendor.rating ? <Stars rating={product.vendor.rating} count={product.vendor.reviews_count} /> : null}
            </div>
            <ChevronRight className="size-5 text-ink/40" />
          </Link>}

          <div>
            <h2 className="mb-2 text-lg font-bold">Description</h2>
            <p className="whitespace-pre-line leading-relaxed text-ink/75">{product.description}</p>
          </div>

          {Object.keys(product.attributes).length > 0 && (
            <div>
              <h2 className="mb-2 text-lg font-bold">Caractéristiques</h2>
              <dl className="card grid grid-cols-2 gap-4 p-4">
                {Object.entries(product.attributes).map(([key, value]) => (
                  <div key={key}>
                    <dt className="text-xs font-semibold uppercase tracking-wider text-ink/45">{key}</dt>
                    <dd className="font-medium">{value}</dd>
                  </div>
                ))}
              </dl>
            </div>
          )}
        </div>
      </div>

      <Reviews slug={product.slug} canReview={!own} />

      {!!related?.results.filter((p) => p.slug !== product.slug).length && (
        <section className="mt-12">
          <SectionHeader title="Dans la même catégorie" />
          <ProductGrid products={related.results.filter((p) => p.slug !== product.slug).slice(0, 5)} />
        </section>
      )}

      {/* Barre d'achat fixe sur mobile */}
      {product.stock > 0 && !own && (
        <div className="fixed inset-x-0 bottom-16 z-30 flex gap-2 border-t border-ink/5 bg-surface/95 p-3 backdrop-blur-xl sm:hidden">
          <Button variant="dark" className="flex-1" onClick={() => addToCart(true)}>Acheter</Button>
          <Button className="flex-1" onClick={() => addToCart(false)}><ShoppingBag className="size-5" /> Panier</Button>
        </div>
      )}
    </>
  )
}
