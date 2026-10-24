import { useQuery } from '@tanstack/react-query'
import { BadgeCheck } from 'lucide-react'
import { useParams } from 'react-router-dom'

import { ProductGrid } from '../components/ProductCard'
import { ShopAvatar } from '../components/Statuts'
import { Spinner } from '../components/ui'
import { api } from '../lib/api'
import type { Paginated, ProductSummary, Shop } from '../lib/types'

export default function ShopPage() {
  const { slug } = useParams()
  const { data: shop, isLoading } = useQuery({
    queryKey: ['shop', slug],
    queryFn: async () => (await api.get<Shop>(`/shops/${slug}/`)).data,
  })
  const { data: products } = useQuery({
    queryKey: ['products', 'shop', slug],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/products/', { params: { vendor: slug } })).data,
  })

  if (isLoading) return <Spinner />
  if (!shop) return <p className="py-16 text-center">Boutique introuvable.</p>

  return (
    <>
      <section className="mb-6 flex items-center gap-4 rounded-3xl bg-surface p-6 ring-1 ring-ink/5">
        <ShopAvatar logo={shop.logo} name={shop.shop_name} className="size-20" />
        <div>
          <h1 className="flex items-center gap-2 text-2xl font-bold">
            {shop.shop_name}
            {shop.is_verified && <BadgeCheck className="size-6 text-primary-dark" aria-label="Vendeur vérifié" />}
          </h1>
          {shop.description && <p className="mt-1 text-ink/70">{shop.description}</p>}
        </div>
      </section>
      <h2 className="mb-3 text-lg font-bold">Produits ({products?.count ?? 0})</h2>
      <ProductGrid products={products?.results ?? []} />
    </>
  )
}
