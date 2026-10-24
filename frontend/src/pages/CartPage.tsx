import { ImageOff, Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import Price from '../components/Price'
import { Button, Empty, ErrorBox, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useCart } from '../context/cart'
import { errorMessage } from '../lib/api'
import type { CartLine } from '../lib/types'

export default function CartPage() {
  const { lines, total, loading, setQuantity, remove } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()
  const [error, setError] = useState<string | null>(null)

  async function run(action: () => Promise<void>) {
    setError(null)
    try {
      await action()
    } catch (e) {
      setError(errorMessage(e))
    }
  }

  if (loading) return <Spinner />
  if (lines.length === 0) {
    return (
      <Empty icon={<ShoppingCart />} title="Votre panier est vide">
        <Link to="/produits" className="font-medium text-primary-dark hover:underline">
          Découvrir les produits
        </Link>
      </Empty>
    )
  }

  // Regroupe par boutique : chaque boutique prépare et livre sa partie
  const byShop = lines.reduce<Record<string, CartLine[]>>((acc, line) => {
    ;(acc[line.product.vendor] ??= []).push(line)
    return acc
  }, {})

  return (
    <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
      <section className="space-y-4">
        <h1 className="text-2xl font-bold">Mon panier</h1>
        <ErrorBox message={error} />
        {Object.entries(byShop).map(([shop, shopLines]) => (
          <div key={shop} className="rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
            <Link to={`/boutique/${shop}`} className="mb-3 block text-sm font-semibold text-ink/60 hover:underline">
              Boutique : {shop}
            </Link>
            <ul className="divide-y divide-ink/5">
              {shopLines.map((line) => (
                <li key={line.id} className="flex gap-3 py-3">
                  <Link to={`/produit/${line.product.slug}`} className="relative size-20 shrink-0 overflow-hidden rounded-xl bg-ink/5">
                    {line.product.image ? (
                      <img src={line.product.image} alt="" className="size-full object-cover" />
                    ) : (
                      <ImageOff className="absolute inset-0 m-auto size-6 text-ink/20" />
                    )}
                  </Link>
                  <div className="flex flex-1 flex-col">
                    <Link to={`/produit/${line.product.slug}`} className="font-medium hover:underline">
                      {line.product.name}
                    </Link>
                    <span className="text-sm text-ink/60"><Price amount={line.product.price} /></span>
                    <div className="mt-auto flex items-center justify-between pt-2">
                      <div className="flex items-center rounded-lg ring-1 ring-ink/15">
                        <button
                          className="p-1.5 disabled:opacity-30"
                          disabled={line.quantity <= 1}
                          onClick={() => run(() => setQuantity(line, line.quantity - 1))}
                          aria-label="Moins"
                        >
                          <Minus className="size-4" />
                        </button>
                        <span className="w-8 text-center text-sm font-semibold">{line.quantity}</span>
                        <button
                          className="p-1.5 disabled:opacity-30"
                          disabled={line.quantity >= line.product.stock}
                          onClick={() => run(() => setQuantity(line, line.quantity + 1))}
                          aria-label="Plus"
                        >
                          <Plus className="size-4" />
                        </button>
                      </div>
                      <span className="font-semibold"><Price amount={line.subtotal} /></span>
                      <button onClick={() => run(() => remove(line))} className="p-1.5 text-ink/40 hover:text-red-600" aria-label="Retirer">
                        <Trash2 className="size-5" />
                      </button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        ))}
      </section>

      <aside className="h-fit space-y-4 rounded-2xl bg-surface p-5 ring-1 ring-ink/5 lg:sticky lg:top-24">
        <div className="flex justify-between text-lg">
          <span>Total</span>
          <span className="font-bold"><Price amount={total} /></span>
        </div>
        <p className="text-sm text-ink/60">Livraison selon votre commune ou retrait en point relais, calculés à l’étape suivante.</p>
        <Button className="w-full" onClick={() => navigate(user ? '/commande' : '/connexion?suivant=/commande')}>
          {user ? 'Passer la commande' : 'Se connecter pour commander'}
        </Button>
      </aside>
    </div>
  )
}
