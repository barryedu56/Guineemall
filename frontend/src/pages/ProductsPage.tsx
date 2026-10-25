import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { SearchX, SlidersHorizontal, X } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { useSearchParams } from 'react-router-dom'

import { ProductGrid, ProductGridSkeleton } from '../components/ProductCard'
import { Button, Empty, Overlay, Pager } from '../components/ui'
import { useSiteConfig } from '../context/settings'
import { api, formatNumber } from '../lib/api'
import { useCategories } from '../lib/queries'
import type { Paginated, ProductSummary } from '../lib/types'

export default function ProductsPage() {
  const [params, setParams] = useSearchParams()
  const [filtersOpen, setFiltersOpen] = useState(false)
  const { data: config } = useSiteConfig()
  const { data: categories = [] } = useCategories()
  const { data, isLoading, isFetching } = useQuery({
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
  const category = categories.find((c) => c.slug === params.get('category'))
  const title = search ? `Résultats pour « ${search} »` : params.get('on_sale') ? 'Promotions' : category?.name ?? 'Tous les produits'
  const activeFilters = ['category', 'min_price', 'max_price', 'in_stock', 'on_sale', 'search'].filter((k) => params.get(k))

  const filters = (
    <div className="space-y-6">
      <FilterGroup title="Catégories">
        <div className="flex flex-wrap gap-2 lg:flex-col lg:items-stretch">
          <FilterChip active={!params.get('category')} onClick={() => update('category', '')}>Toutes</FilterChip>
          {categories.map((c) => (
            <FilterChip key={c.slug} active={params.get('category') === c.slug} onClick={() => update('category', c.slug)}>
              <span className="flex w-full justify-between gap-2">{c.name} <span className="text-ink/40">{c.products_count}</span></span>
            </FilterChip>
          ))}
        </div>
      </FilterGroup>
      <FilterGroup title="Prix (GNF)">
        <div className="flex gap-2">
          {(['min_price', 'max_price'] as const).map((key) => (
            <PriceFilter key={`${key}-${params.get(key) ?? ''}`} placeholder={key === 'min_price' ? 'Min' : 'Max'} initial={params.get(key) ?? ''} onCommit={(v) => update(key, v)} />
          ))}
        </div>
      </FilterGroup>
      <FilterGroup title="Disponibilité">
        <Toggle label="En stock uniquement" checked={params.get('in_stock') === 'true'} onChange={(v) => update('in_stock', v ? 'true' : '')} />
        <Toggle label="En promotion" checked={params.get('on_sale') === 'true'} onChange={(v) => update('on_sale', v ? 'true' : '')} />
      </FilterGroup>
      {activeFilters.length > 0 && (
        <Button variant="ghost" size="sm" onClick={() => setParams(new URLSearchParams())}>
          <X className="size-4" /> Effacer les filtres
        </Button>
      )}
    </div>
  )

  return (
    <div className="grid gap-8 lg:grid-cols-[250px_1fr]">
      <aside className="card hidden h-fit p-5 lg:sticky lg:top-40 lg:block">{filters}</aside>

      {/* Filtres sur mobile : panneau coulissant */}
      {filtersOpen && (
        <Overlay>
        <div className="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal>
          <button className="absolute inset-0 bg-ink/40" onClick={() => setFiltersOpen(false)} aria-label="Fermer les filtres" />
          <div className="absolute inset-x-0 bottom-0 max-h-[85vh] animate-fade-up overflow-y-auto rounded-t-[2rem] bg-surface p-6">
            <div className="mb-4 flex items-center justify-between">
              <p className="text-lg font-bold">Filtres</p>
              <button onClick={() => setFiltersOpen(false)} className="rounded-full p-2 hover:bg-ink/5" aria-label="Fermer"><X className="size-5" /></button>
            </div>
            {filters}
            <Button className="mt-6 w-full" onClick={() => setFiltersOpen(false)}>Voir {data?.count ?? ''} produit(s)</Button>
          </div>
        </div>
        </Overlay>
      )}

      <section>
        <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
          <div>
            <h1 className="text-2xl font-extrabold sm:text-3xl">{title}</h1>
            {data && <p className="mt-1 text-ink/55">{formatNumber(data.count)} produit{data.count > 1 ? 's' : ''}</p>}
          </div>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" className="lg:hidden" onClick={() => setFiltersOpen(true)}>
              <SlidersHorizontal className="size-4" /> Filtres {activeFilters.length > 0 && `(${activeFilters.length})`}
            </Button>
            <select
              value={params.get('ordering') ?? config?.choices.product_sort[0]?.value ?? ''}
              onChange={(e) => update('ordering', e.target.value)}
              className="rounded-full border border-ink/15 bg-surface px-4 py-1.5 text-sm font-medium"
              aria-label="Trier"
            >
              {config?.choices.product_sort.map((s) => (
                <option key={s.value} value={s.value}>{s.label}</option>
              ))}
            </select>
          </div>
        </div>

        {isLoading ? (
          <ProductGridSkeleton />
        ) : data?.results.length ? (
          <div className={isFetching ? 'opacity-60 transition' : 'transition'}>
            <ProductGrid products={data.results} />
            <Pager page={data.page} totalPages={data.total_pages} onChange={(p) => update('page', String(p))} />
          </div>
        ) : (
          <Empty icon={<SearchX />} title="Aucun produit trouvé">
            <p className="text-ink/60">Essayez une autre recherche ou retirez des filtres.</p>
            {activeFilters.length > 0 && <Button variant="outline" onClick={() => setParams(new URLSearchParams())}>Effacer les filtres</Button>}
          </Empty>
        )}
      </section>
    </div>
  )
}

function FilterGroup({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div>
      <p className="mb-3 text-xs font-bold uppercase tracking-wider text-ink/50">{title}</p>
      <div className="space-y-2">{children}</div>
    </div>
  )
}

function FilterChip({ active, onClick, children }: { active: boolean; onClick: () => void; children: ReactNode }) {
  return (
    <button
      onClick={onClick}
      className={`rounded-full px-3.5 py-2 text-left text-sm font-medium transition lg:rounded-xl ${active ? 'bg-ink text-white' : 'bg-background hover:bg-ink/5 lg:bg-transparent'}`}
    >
      {children}
    </button>
  )
}

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <label className="flex cursor-pointer items-center justify-between gap-3 text-sm font-medium">
      {label}
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        onClick={() => onChange(!checked)}
        className={`relative h-6 w-11 rounded-full transition ${checked ? 'bg-primary-dark' : 'bg-ink/15'}`}
      >
        <span className={`absolute top-0.5 size-5 rounded-full bg-white shadow transition ${checked ? 'left-[22px]' : 'left-0.5'}`} />
      </button>
    </label>
  )
}

/** Prix minimum / maximum, affiché avec séparateur de milliers ; appliqué en quittant le champ ou avec Entrée. */
function PriceFilter({ placeholder, initial, onCommit }: { placeholder: string; initial: string; onCommit: (v: string) => void }) {
  const [digits, setDigits] = useState(initial)
  return (
    <input
      type="text"
      inputMode="numeric"
      placeholder={placeholder}
      value={digits ? formatNumber(Number(digits)) : ''}
      onChange={(e) => setDigits(e.target.value.replace(/\D/g, ''))}
      onBlur={() => digits !== initial && onCommit(digits)}
      onKeyDown={(e) => e.key === 'Enter' && onCommit(digits)}
      className="w-full rounded-2xl border border-ink/10 bg-surface px-3 py-2.5 outline-none focus:border-primary-dark focus:ring-4 focus:ring-primary/25"
    />
  )
}
