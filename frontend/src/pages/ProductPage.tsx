import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, Heart, ImageOff, MessageCircle, MessagesSquare, Minus, Plus, ShoppingCart } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import Price from '../components/Price'
import ShareButton from '../components/ShareButton'
import Stars from '../components/Stars'
import { Button, ErrorBox, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useCart } from '../context/cart'
import { api, errorMessage } from '../lib/api'
import type { Conversation, Paginated, ProductDetail, ProductSummary } from '../lib/types'
import Reviews from './Reviews'

export default function ProductPage() {
  const { slug } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const { add } = useCart()
  const queryClient = useQueryClient()
  const [imageIndex, setImageIndex] = useState(0)
  const [quantity, setQuantity] = useState(1)
  const [error, setError] = useState<string | null>(null)
  const [added, setAdded] = useState(false)

  const { data: product, isLoading } = useQuery({
    queryKey: ['product', slug],
    queryFn: async () => (await api.get<ProductDetail>(`/products/${slug}/`)).data,
  })
  const { data: favorites } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/favorites/')).data,
    enabled: !!user,
  })
  const isFavorite = favorites?.results.some((p) => p.slug === slug) ?? false

  const contact = useMutation({
    mutationFn: async () =>
      (await api.post<Conversation>('/conversations/start/', { shop: product!.vendor.slug, product: slug })).data,
    onSuccess: (c) => {
      queryClient.invalidateQueries({ queryKey: ['conversations'] })
      navigate(`/messages/${c.id}`)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const toggleFavorite = useMutation({
    mutationFn: () => (isFavorite ? api.delete(`/favorites/${slug}/`) : api.post(`/favorites/${slug}/`)),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['favorites'] }),
  })

  if (isLoading) return <Spinner />
  if (!product) return <p className="py-16 text-center">Produit introuvable.</p>

  const summary: ProductSummary = {
    ...product,
    image: product.images[0]?.image ?? null,
    vendor: product.vendor.slug,
    category: product.category.slug,
  }

  async function addToCart(goToCart: boolean) {
    setError(null)
    try {
      await add(summary, quantity)
      if (goToCart) navigate('/panier')
      else {
        setAdded(true)
        setTimeout(() => setAdded(false), 2000)
      }
    } catch (e) {
      setError(errorMessage(e))
    }
  }

  const whatsappLink = product.vendor.whatsapp
    ? `https://wa.me/${product.vendor.whatsapp.replace(/\D/g, '').replace(/^(?!224)/, '224')}?text=${encodeURIComponent(
        `Bonjour, je suis intéressé(e) par « ${product.name} » vu sur GuinéeMall : ${window.location.href}`,
      )}`
    : null

  return (
    <>
    <div className="grid gap-8 md:grid-cols-2">
      <div>
        <div className="relative aspect-square overflow-hidden rounded-3xl bg-surface ring-1 ring-ink/5">
          {product.images.length ? (
            <img src={product.images[imageIndex]?.image} alt={product.name} className="size-full object-cover" />
          ) : (
            <ImageOff className="absolute inset-0 m-auto size-16 text-ink/20" />
          )}
        </div>
        {product.images.length > 1 && (
          <div className="mt-3 flex gap-2 overflow-x-auto">
            {product.images.map((img, i) => (
              <button
                key={img.id}
                onClick={() => setImageIndex(i)}
                className={`size-20 shrink-0 overflow-hidden rounded-xl ring-2 ${i === imageIndex ? 'ring-primary-dark' : 'ring-transparent'}`}
              >
                <img src={img.image} alt="" className="size-full object-cover" />
              </button>
            ))}
          </div>
        )}
      </div>

      <div className="space-y-5">
        <div>
          <Link to={`/produits?category=${product.category.slug}`} className="text-sm text-primary-dark hover:underline">
            {product.category.name}
          </Link>
          <h1 className="mt-1 text-2xl font-bold sm:text-3xl">{product.name}</h1>
          {product.rating && (
            <a href="#avis" className="mt-1 inline-block">
              <Stars rating={product.rating} count={product.reviews_count} size="md" />
            </a>
          )}
        </div>

        <div className="flex flex-wrap items-baseline gap-3">
          <Price amount={product.price} className="text-3xl font-extrabold text-primary-dark" />
          {product.compare_at_price && (
            <Price amount={product.compare_at_price} className="text-lg text-ink/50 line-through" />
          )}
        </div>

        <p className={product.stock > 0 ? 'text-green-700' : 'text-red-600'}>
          {product.stock > 0 ? `En stock (${product.stock} disponible${product.stock > 1 ? 's' : ''})` : 'Épuisé'}
        </p>

        {product.stock > 0 && (
          <div className="flex items-center gap-3">
            <span className="font-medium">Quantité</span>
            <div className="flex items-center rounded-xl ring-1 ring-ink/15">
              <button className="p-2.5" onClick={() => setQuantity(Math.max(1, quantity - 1))} aria-label="Moins">
                <Minus className="size-4" />
              </button>
              <span className="w-10 text-center font-semibold">{quantity}</span>
              <button className="p-2.5" onClick={() => setQuantity(Math.min(product.stock, quantity + 1))} aria-label="Plus">
                <Plus className="size-4" />
              </button>
            </div>
          </div>
        )}

        <ErrorBox message={error} />

        <div className="flex flex-wrap gap-3">
          <Button className="flex-1" disabled={product.stock === 0} onClick={() => addToCart(true)}>
            Acheter maintenant
          </Button>
          <Button variant="outline" className="flex-1" disabled={product.stock === 0} onClick={() => addToCart(false)}>
            <ShoppingCart className="size-5" /> {added ? 'Ajouté ✓' : 'Ajouter au panier'}
          </Button>
          {user && (
            <Button variant="outline" onClick={() => toggleFavorite.mutate()} aria-label="Favori">
              <Heart className={`size-5 ${isFavorite ? 'fill-red-500 text-red-500' : ''}`} />
            </Button>
          )}
        </div>

        <div className="flex flex-wrap gap-3">
          <Button
            variant="outline"
            className="flex-1"
            loading={contact.isPending}
            onClick={() => (user ? contact.mutate() : navigate(`/connexion?suivant=/produit/${slug}`))}
          >
            <MessagesSquare className="size-5" /> Écrire à la boutique
          </Button>
          <ShareButton slug={product.slug} name={product.name} />
        </div>

        {whatsappLink && (
          <a
            href={whatsappLink}
            target="_blank"
            rel="noreferrer"
            className="flex items-center justify-center gap-2 rounded-xl bg-[#25D366] py-2.5 font-semibold text-white hover:opacity-90"
          >
            <MessageCircle className="size-5" /> Commander via WhatsApp
          </a>
        )}

        <Link
          to={`/boutique/${product.vendor.slug}`}
          className="flex items-center gap-3 rounded-2xl bg-surface p-4 ring-1 ring-ink/5 hover:ring-primary"
        >
          <div className="flex-1">
            <p className="text-sm text-ink/60">Vendu par</p>
            <p className="flex items-center gap-1.5 font-semibold">
              {product.vendor.shop_name}
              {product.vendor.is_verified && <BadgeCheck className="size-4 text-primary-dark" />}
            </p>
            {product.vendor.rating && <Stars rating={product.vendor.rating} count={product.vendor.reviews_count} />}
          </div>
          <span className="text-sm text-primary-dark">Voir la boutique →</span>
        </Link>

        <div>
          <h2 className="mb-2 font-semibold">Description</h2>
          <p className="whitespace-pre-line text-ink/80">{product.description}</p>
        </div>

        {Object.keys(product.attributes).length > 0 && (
          <dl className="grid grid-cols-2 gap-2 rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
            {Object.entries(product.attributes).map(([key, value]) => (
              <div key={key}>
                <dt className="text-sm capitalize text-ink/60">{key}</dt>
                <dd className="font-medium">{value}</dd>
              </div>
            ))}
          </dl>
        )}
      </div>
    </div>
    <Reviews slug={product.slug} />
    </>
  )
}
