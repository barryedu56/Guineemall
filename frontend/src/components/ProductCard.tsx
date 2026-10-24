import { Heart, ImageOff, Pencil, Plus } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'

import { useCart } from '../context/cart'
import { useIsOwnShop } from '../context/ownership'
import { useToast } from '../context/toast'
import { errorMessage } from '../lib/api'
import { useFavorites } from '../lib/queries'
import type { ProductSummary } from '../lib/types'
import Price from './Price'
import Stars from './Stars'
import { Skeleton } from './ui'

export default function ProductCard({ product }: { product: ProductSummary }) {
  const { add } = useCart()
  const toast = useToast()
  const navigate = useNavigate()
  const favorites = useFavorites()
  const discount = product.compare_at_price ? Math.round((1 - product.price / product.compare_at_price) * 100) : 0
  const favorite = favorites.isFavorite(product.slug)
  const own = useIsOwnShop(product.vendor)

  async function addToCart() {
    try {
      await add(product)
      toast(`« ${product.name} » ajouté au panier`)
    } catch (e) {
      toast(errorMessage(e), 'error')
    }
  }

  function toggleFavorite() {
    if (!favorites.enabled) return navigate(`/connexion?suivant=/produit/${product.slug}`)
    favorites.toggle.mutate(product.slug, {
      onSuccess: () => toast(favorite ? 'Retiré des favoris' : 'Ajouté aux favoris', 'info'),
    })
  }

  return (
    <article className="card card-hover group flex flex-col overflow-hidden">
      <div className="relative aspect-square overflow-hidden bg-ink/[0.03]">
        <Link to={`/produit/${product.slug}`} className="block size-full" aria-label={product.name}>
          {product.image ? (
            <img src={product.image} alt={product.name} loading="lazy" className="size-full object-cover transition duration-500 group-hover:scale-105" />
          ) : (
            <ImageOff className="absolute inset-0 m-auto size-10 text-ink/20" />
          )}
        </Link>
        <div className="absolute left-2.5 top-2.5 flex flex-col gap-1">
          {discount > 0 && <span className="rounded-full bg-danger px-2 py-0.5 text-xs font-bold text-white">-{discount}%</span>}
          {product.stock === 0 && <span className="rounded-full bg-ink/80 px-2 py-0.5 text-xs font-bold text-white">Épuisé</span>}
        </div>
        {!own && <button
          onClick={toggleFavorite}
          className="absolute right-2.5 top-2.5 flex size-9 items-center justify-center rounded-full bg-surface/90 shadow-sm backdrop-blur transition hover:scale-110"
          aria-label={favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
          aria-pressed={favorite}
        >
          <Heart className={`size-[18px] ${favorite ? 'fill-danger text-danger' : 'text-ink/70'}`} />
        </button>}
        {own ? (
          // Produit du vendeur connecté : modifier au lieu d'acheter
          <Link
            to={`/vendeur/produits?modifier=${product.id}`}
            className="absolute bottom-2.5 right-2.5 flex items-center gap-1.5 rounded-full bg-ink px-3 py-2 text-xs font-semibold text-white shadow-lift transition hover:bg-ink/90"
          >
            <Pencil className="size-3.5" /> Modifier
          </Link>
        ) : product.stock > 0 && (
          <button
            onClick={addToCart}
            className="absolute bottom-2.5 right-2.5 flex size-10 items-center justify-center rounded-full bg-primary shadow-lift transition hover:scale-110 hover:bg-primary-hover active:scale-95 md:translate-y-2 md:opacity-0 md:group-hover:translate-y-0 md:group-hover:opacity-100"
            aria-label={`Ajouter « ${product.name} » au panier`}
          >
            <Plus className="size-5" />
          </button>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-1 p-3 sm:p-4">
        <Link to={`/produit/${product.slug}`} className="line-clamp-2 text-sm font-semibold leading-snug hover:text-primary-dark">
          {product.name}
        </Link>
        <Stars rating={product.rating} count={product.reviews_count} />
        <div className="mt-auto flex flex-wrap items-baseline gap-x-2 pt-1">
          <Price amount={product.price} className="font-extrabold" />
          {product.compare_at_price && <Price amount={product.compare_at_price} className="text-xs text-ink/45 line-through" />}
        </div>
      </div>
    </article>
  )
}

export function ProductGrid({ products }: { products: ProductSummary[] }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
      {products.map((p) => (
        <ProductCard key={p.id} product={p} />
      ))}
    </div>
  )
}

export function ProductGridSkeleton({ count = 10 }: { count?: number }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="card overflow-hidden">
          <Skeleton className="aspect-square rounded-none" />
          <div className="space-y-2 p-4">
            <Skeleton className="h-4 w-4/5" />
            <Skeleton className="h-4 w-1/2" />
          </div>
        </div>
      ))}
    </div>
  )
}
