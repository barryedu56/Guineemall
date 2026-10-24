import { useQuery } from '@tanstack/react-query'
import { ShieldCheck, Smartphone, Truck } from 'lucide-react'
import { Link } from 'react-router-dom'

import { ProductGrid } from '../components/ProductCard'
import StatutsBar from '../components/Statuts'
import { Spinner } from '../components/ui'
import { api } from '../lib/api'
import type { Category, Paginated, ProductSummary } from '../lib/types'

export default function HomePage() {
  const { data: categories = [] } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get<Category[]>('/categories/')).data,
  })
  const { data: latest, isLoading } = useQuery({
    queryKey: ['products', 'latest'],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/products/', { params: { ordering: '-created_at' } })).data,
  })

  return (
    <>
      <StatutsBar />

      <section className="mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-primary via-primary to-secondary px-6 py-10 sm:px-10">
        <h1 className="max-w-xl text-3xl font-extrabold leading-tight sm:text-4xl">
          Le marché guinéen, dans votre poche.
        </h1>
        <p className="mt-3 max-w-lg text-ink/80">
          Achetez auprès de boutiques de Conakry et de toute la Guinée. Payez par Orange Money, MTN ou à la livraison.
        </p>
        <Link to="/produits" className="mt-6 inline-block rounded-full bg-ink px-6 py-3 font-semibold text-white hover:bg-ink/90">
          Découvrir les produits
        </Link>
      </section>

      <section className="mb-8 grid gap-3 sm:grid-cols-3">
        {[
          { icon: ShieldCheck, title: 'Vendeurs vérifiés', text: 'Boutiques contrôlées par notre équipe' },
          { icon: Smartphone, title: 'Mobile Money', text: 'Orange Money, MTN MoMo ou à la livraison' },
          { icon: Truck, title: 'Livraison à domicile', text: 'Indiquez simplement un repère près de chez vous' },
        ].map(({ icon: Icon, title, text }) => (
          <div key={title} className="flex items-center gap-3 rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
            <Icon className="size-8 shrink-0 text-primary-dark" />
            <div>
              <p className="font-semibold">{title}</p>
              <p className="text-sm text-ink/60">{text}</p>
            </div>
          </div>
        ))}
      </section>

      {categories.length > 0 && (
        <section className="mb-8">
          <h2 className="mb-3 text-lg font-bold">Catégories</h2>
          <div className="flex flex-wrap gap-2">
            {categories.map((c) => (
              <Link
                key={c.id}
                to={`/produits?category=${c.slug}`}
                className="rounded-full bg-surface px-4 py-2 text-sm font-medium ring-1 ring-ink/10 hover:bg-primary-light"
              >
                {c.name}
              </Link>
            ))}
          </div>
        </section>
      )}

      <section>
        <div className="mb-3 flex items-baseline justify-between">
          <h2 className="text-lg font-bold">Nouveautés</h2>
          <Link to="/produits" className="text-sm font-medium text-primary-dark hover:underline">
            Tout voir
          </Link>
        </div>
        {isLoading ? <Spinner /> : <ProductGrid products={latest?.results ?? []} />}
      </section>
    </>
  )
}
