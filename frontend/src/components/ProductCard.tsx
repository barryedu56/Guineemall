import { ImageOff, ShoppingCart } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'

import { useCart } from '../context/cart'
import type { ProductSummary } from '../lib/types'
import Price from './Price'
import Stars from './Stars'

export default function ProductCard({ product }: { product: ProductSummary }) {
  const { add } = useCart()
  const [added, setAdded] = useState(false)
  const discount = product.compare_at_price
    ? Math.round((1 - product.price / product.compare_at_price) * 100)
    : 0

  async function addToCart() {
    await add(product)
    setAdded(true)
    setTimeout(() => setAdded(false), 1500)
  }

  return (
    <article className="group flex flex-col overflow-hidden rounded-2xl bg-surface shadow-sm ring-1 ring-ink/5 transition hover:shadow-md">
      <Link to={`/produit/${product.slug}`} className="relative aspect-square bg-ink/5">
        {product.image ? (
          <img src={product.image} alt={product.name} loading="lazy" className="size-full object-cover" />
        ) : (
          <ImageOff className="absolute inset-0 m-auto size-10 text-ink/20" />
        )}
        {discount > 0 && (
          <span className="absolute left-2 top-2 rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">
            -{discount}%
          </span>
        )}
      </Link>
      <div className="flex flex-1 flex-col gap-1 p-3">
        <Link to={`/produit/${product.slug}`} className="line-clamp-2 text-sm font-medium leading-snug hover:underline">
          {product.name}
        </Link>
        <Stars rating={product.rating} count={product.reviews_count} />
        <div className="mt-auto pt-1">
          <Price amount={product.price} className="block font-bold text-primary-dark" />
          {product.compare_at_price && (
            <Price amount={product.compare_at_price} className="block text-xs text-ink/50 line-through" />
          )}
        </div>
        <button
          onClick={addToCart}
          disabled={product.stock === 0}
          className="mt-2 flex items-center justify-center gap-1.5 rounded-xl bg-primary py-2 text-sm font-semibold transition hover:bg-primary-hover disabled:bg-ink/10 disabled:text-ink/40"
        >
          <ShoppingCart className="size-4" />
          {product.stock === 0 ? 'Épuisé' : added ? 'Ajouté ✓' : 'Ajouter'}
        </button>
      </div>
    </article>
  )
}

export function ProductGrid({ products }: { products: ProductSummary[] }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
      {products.map((p) => (
        <ProductCard key={p.id} product={p} />
      ))}
    </div>
  )
}
