import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { SearchX } from 'lucide-react'
import type { ReactNode } from 'react'
import { useSearchParams } from 'react-router-dom'

import { ProductGrid } from '../components/ProductCard'
import { Button, Empty, Spinner } from '../components/ui'
import { api } from '../lib/api'
import type { Category, Paginated, ProductSummary } from '../lib/types'

const SORTS = [
  { value: '-created_at', label: 'Plus récents' },
  { value: 'price', label: 'Prix croissant' },
  { value: '-price', label: 'Prix décroissant' },
]

export default function ProductsPage() {
  const [params, setParams] = useSearchParams()
  const page = Number(params.get('page') ?? 1)

  const { data: categories = [] } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get<Category[]>('/categories/')).data,
  })
  const { data, isLoading } = useQuery({
    queryKey: ['products', params.toString()],
    queryFn: async () =>
      (await api.get<Paginated<ProductSummary>>('/products/', { params: Object.fromEntries(params) })).data,
    placeholderData: keepPreviousData,
  })

  function update(key: string, value: string) {
    const next = new URLSearchParams(params)
    if (value) next.set(key, value)
    else next.delete(key)
    if (key !== 'page') next.delete('page')
    setParams(next)
  }

  const search = params.get('search')
  const pages = data ? Math.ceil(data.count / 20) : 1

  return (
    <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
      <aside className="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <div>
          <h2 className="mb-2 font-semibold">Catégories</h2>
          <div className="flex flex-wrap gap-2 lg:flex-col lg:items-start">
            <FilterChip active={!params.get('category')} onClick={() => update('category', '')}>
              Toutes
            </FilterChip>
            {categories.map((c) => (
              <FilterChip key={c.slug} active={params.get('category') === c.slug} onClick={() => update('category', c.slug)}>
                {c.name}
              </FilterChip>
            ))}
          </div>
        </div>
        <div>
          <h2 className="mb-2 font-semibold">Prix (GNF)</h2>
          <div className="flex gap-2">
            <input
              type="number"
              placeholder="Min"
              defaultValue={params.get('min_price') ?? ''}
              onBlur={(e) => update('min_price', e.target.value)}
              className="w-full rounded-xl border border-ink/15 px-3 py-2"
            />
            <input
              type="number"
              placeholder="Max"
              defaultValue={params.get('max_price') ?? ''}
              onBlur={(e) => update('max_price', e.target.value)}
              className="w-full rounded-xl border border-ink/15 px-3 py-2"
            />
          </div>
        </div>
        <label className="flex items-center gap-2">
          <input
            type="checkbox"
            checked={params.get('in_stock') === 'true'}
            onChange={(e) => update('in_stock', e.target.checked ? 'true' : '')}
            className="size-4 accent-primary-dark"
          />
          En stock uniquement
        </label>
      </aside>

      <section>
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h1 className="text-xl font-bold">
            {search ? `Résultats pour « ${search} »` : 'Tous les produits'}
            {data && <span className="ml-2 text-base font-normal text-ink/50">({data.count})</span>}
          </h1>
          <select
            value={params.get('ordering') ?? '-created_at'}
            onChange={(e) => update('ordering', e.target.value)}
            className="rounded-xl border border-ink/15 bg-surface px-3 py-2"
          >
            {SORTS.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
        </div>

        {isLoading ? (
          <Spinner />
        ) : data?.results.length ? (
          <>
            <ProductGrid products={data.results} />
            {pages > 1 && (
              <div className="mt-6 flex items-center justify-center gap-3">
                <Button variant="outline" disabled={page <= 1} onClick={() => update('page', String(page - 1))}>
                  Précédent
                </Button>
                <span className="text-sm">
                  Page {page} / {pages}
                </span>
                <Button variant="outline" disabled={page >= pages} onClick={() => update('page', String(page + 1))}>
                  Suivant
                </Button>
              </div>
            )}
          </>
        ) : (
          <Empty icon={<SearchX />} title="Aucun produit trouvé">
            <p className="text-ink/60">Essayez une autre recherche ou retirez des filtres.</p>
          </Empty>
        )}
      </section>
    </div>
  )
}

function FilterChip({ active, onClick, children }: { active: boolean; onClick: () => void; children: ReactNode }) {
  return (
    <button
      onClick={onClick}
      className={`rounded-full px-3 py-1.5 text-sm ${active ? 'bg-primary font-semibold' : 'bg-surface ring-1 ring-ink/10 hover:bg-ink/5'}`}
    >
      {children}
    </button>
  )
}
