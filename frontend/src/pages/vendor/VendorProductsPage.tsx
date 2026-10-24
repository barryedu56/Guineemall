import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ImageOff, ImagePlus, Package, Pencil, Plus, Search, Trash2, X } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'

import { AmountInput, Badge, Button, Empty, ErrorBox, Input, Pager, Select, Spinner, Textarea } from '../../components/ui'
import { api, errorMessage, formatGNF } from '../../lib/api'
import { useCategories } from '../../lib/queries'
import type { Paginated, VendorProduct } from '../../lib/types'

export default function VendorProductsPage() {
  // Produit à modifier dans l'adresse (?modifier=12, ou ?modifier=nouveau) : ouvrable depuis la fiche produit
  const [params, setParams] = useSearchParams()
  const target = params.get('modifier')
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-products', page, search],
    queryFn: async () => (await api.get<Paginated<VendorProduct>>('/vendor/products/', { params: { page, search } })).data,
    placeholderData: keepPreviousData,
  })

  const { data: targetProduct, isLoading: loadingTarget, isError } = useQuery({
    queryKey: ['vendor-products', 'detail', target],
    queryFn: async () => (await api.get<VendorProduct>(`/vendor/products/${target}/`)).data,
    enabled: !!target && target !== 'nouveau',
  })
  const openEditor = (id: number | 'nouveau') => setParams({ modifier: String(id) })
  const closeEditor = () => setParams({}, { replace: true })

  if (target === 'nouveau') return <ProductForm product={null} onDone={closeEditor} />
  if (target && loadingTarget) return <Spinner />
  if (target && targetProduct) return <ProductForm key={targetProduct.id} product={targetProduct} onDone={closeEditor} />
  if (target && isError) return <Empty icon={<Package />} title="Ce produit n'existe pas ou n'est pas à vous" />
  if (isLoading) return <Spinner />

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="relative min-w-60 flex-1 sm:max-w-sm">
          <Search className="absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
          <input
            value={search}
            onChange={(e) => {
              setSearch(e.target.value)
              setPage(1)
            }}
            placeholder="Rechercher un produit"
            className="w-full rounded-full bg-surface py-2.5 pl-10 pr-4 shadow-sm outline-none focus:ring-4 focus:ring-primary/25"
          />
        </div>
        <Button onClick={() => openEditor('nouveau')}>
          <Plus className="size-5" /> Nouveau produit
        </Button>
      </div>
      {!data?.results.length ? (
        <Empty icon={<Package />} title="Aucun produit">
          <p className="text-ink/60">Ajoutez votre premier produit pour commencer à vendre.</p>
        </Empty>
      ) : (
        <ul className="card divide-y divide-ink/5">
          {data.results.map((p) => (
            <li key={p.id} className="flex items-center gap-3 p-3">
              <div className="relative size-14 shrink-0 overflow-hidden rounded-xl bg-ink/5">
                {p.images[0] ? (
                  <img src={p.images[0].image} alt="" className="size-full object-cover" />
                ) : (
                  <ImageOff className="absolute inset-0 m-auto size-5 text-ink/20" />
                )}
              </div>
              <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{p.name}</p>
                <p className="text-sm text-ink/60">
                  {formatGNF(p.price)} · Stock : <span className={p.stock === 0 ? 'font-semibold text-red-600' : ''}>{p.stock}</span>
                </p>
              </div>
              {p.status === 'inactive' && <Badge>Masqué</Badge>}
              <Button variant="ghost" onClick={() => openEditor(p.id)} aria-label="Modifier">
                <Pencil className="size-4" />
              </Button>
            </li>
          ))}
        </ul>
      )}
      {data && <Pager page={data.page} totalPages={data.total_pages} onChange={setPage} />}
    </div>
  )
}

function ProductForm({ product, onDone }: { product: VendorProduct | null; onDone: () => void }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    name: product?.name ?? '',
    description: product?.description ?? '',
    price: product?.price?.toString() ?? '',
    compare_at_price: product?.compare_at_price?.toString() ?? '',
    stock: product?.stock?.toString() ?? '1',
    category: product?.category ?? '',
    status: product?.status ?? 'active',
  })
  // Caractéristiques libres affichées sur la fiche produit (taille, couleur, mémoire…)
  const [attributes, setAttributes] = useState<[string, string][]>(Object.entries(product?.attributes ?? {}))
  const [current, setCurrent] = useState<VendorProduct | null>(product)
  const [newImages, setNewImages] = useState<File[]>([])
  const [error, setError] = useState<string | null>(null)

  const { data: categories = [] } = useCategories()

  const save = useMutation({
    mutationFn: async () => {
      const payload = {
        ...form,
        compare_at_price: form.compare_at_price || null,
        attributes: Object.fromEntries(attributes.filter(([k, v]) => k.trim() && v.trim()).map(([k, v]) => [k.trim(), v.trim()])),
      }
      const saved = current
        ? (await api.patch<VendorProduct>(`/vendor/products/${current.id}/`, payload)).data
        : (await api.post<VendorProduct>('/vendor/products/', payload)).data
      for (const [i, file] of newImages.entries()) {
        const data = new FormData()
        data.append('image', file)
        data.append('position', String(saved.images.length + i))
        await api.post(`/vendor/products/${saved.id}/images/`, data)
      }
      return saved
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendor-products'] })
      queryClient.invalidateQueries({ queryKey: ['products'] })
      onDone()
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const remove = useMutation({
    mutationFn: () => api.delete(`/vendor/products/${current!.id}/`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendor-products'] })
      onDone()
    },
  })

  async function deleteImage(imageId: number) {
    if (!current) return
    await api.delete(`/vendor/products/${current.id}/images/${imageId}/`)
    setCurrent({ ...current, images: current.images.filter((i) => i.id !== imageId) })
    queryClient.invalidateQueries({ queryKey: ['vendor-products'] })
  }

  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    save.mutate()
  }

  return (
    <form onSubmit={submit} className="card max-w-2xl space-y-5 p-6">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold">{current ? 'Modifier le produit' : 'Nouveau produit'}</h2>
        <button type="button" onClick={onDone} className="rounded-full p-1 hover:bg-ink/5" aria-label="Fermer">
          <X className="size-5" />
        </button>
      </div>

      <div>
        <span className="text-sm font-medium">Photos</span>
        <div className="mt-1 flex flex-wrap gap-2">
          {current?.images.map((img) => (
            <div key={img.id} className="relative size-20 overflow-hidden rounded-xl">
              <img src={img.image} alt="" className="size-full object-cover" />
              <button type="button" onClick={() => deleteImage(img.id)} className="absolute right-1 top-1 rounded-full bg-black/60 p-0.5 text-white" aria-label="Supprimer la photo">
                <X className="size-3.5" />
              </button>
            </div>
          ))}
          {newImages.map((file, i) => (
            <div key={i} className="relative size-20 overflow-hidden rounded-xl opacity-80">
              <img src={URL.createObjectURL(file)} alt="" className="size-full object-cover" />
              <button type="button" onClick={() => setNewImages(newImages.filter((_, j) => j !== i))} className="absolute right-1 top-1 rounded-full bg-black/60 p-0.5 text-white" aria-label="Retirer">
                <X className="size-3.5" />
              </button>
            </div>
          ))}
          <label className="flex size-20 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-ink/20 text-ink/50 hover:border-primary-dark">
            <ImagePlus className="size-6" />
            <span className="text-xs">Ajouter</span>
            <input type="file" accept="image/*" multiple className="hidden" onChange={(e) => setNewImages([...newImages, ...Array.from(e.target.files ?? [])])} />
          </label>
        </div>
      </div>

      <Input label="Nom du produit" value={form.name} onChange={set('name')} required />
      <Select label="Catégorie" value={form.category} onChange={set('category')} required>
        <option value="">Choisir…</option>
        {categories.map((c) => (
          <option key={c.slug} value={c.slug}>
            {c.name}
          </option>
        ))}
      </Select>
      <Textarea label="Description" value={form.description} onChange={set('description')} required rows={5} />
      <div className="grid gap-3 sm:grid-cols-3">
        <AmountInput label="Prix (GNF)" value={form.price} onValueChange={(price) => setForm({ ...form, price })} required />
        <AmountInput label="Ancien prix (GNF)" value={form.compare_at_price} onValueChange={(compare_at_price) => setForm({ ...form, compare_at_price })} hint="Optionnel, pour afficher une promo" />
        <Input label="Stock" type="number" min={0} value={form.stock} onChange={set('stock')} required />
      </div>
      <fieldset className="space-y-2">
        <legend className="text-sm font-semibold">Caractéristiques</legend>
        {attributes.map(([key, value], i) => (
          <div key={i} className="flex gap-2">
            <input
              value={key}
              onChange={(e) => setAttributes(attributes.map((a, j) => (j === i ? [e.target.value, a[1]] : a)))}
              placeholder="Ex. : Couleur"
              className="w-2/5 rounded-2xl border border-ink/10 px-4 py-2.5"
            />
            <input
              value={value}
              onChange={(e) => setAttributes(attributes.map((a, j) => (j === i ? [a[0], e.target.value] : a)))}
              placeholder="Ex. : Noir"
              className="min-w-0 flex-1 rounded-2xl border border-ink/10 px-4 py-2.5"
            />
            <button type="button" onClick={() => setAttributes(attributes.filter((_, j) => j !== i))} className="rounded-full p-2 text-ink/40 hover:text-danger" aria-label="Retirer">
              <X className="size-4" />
            </button>
          </div>
        ))}
        <Button type="button" variant="ghost" size="sm" onClick={() => setAttributes([...attributes, ['', '']])}>
          <Plus className="size-4" /> Ajouter une caractéristique
        </Button>
      </fieldset>
      <Select label="Visibilité" value={form.status} onChange={set('status')}>
        <option value="active">Visible par les clients</option>
        <option value="inactive">Masqué</option>
      </Select>

      <ErrorBox message={error} />
      <div className="flex flex-wrap gap-3">
        <Button type="submit" loading={save.isPending}>
          Enregistrer
        </Button>
        {current && (
          <Button
            type="button"
            variant="ghost"
            className="ml-auto text-red-600"
            loading={remove.isPending}
            onClick={() => window.confirm('Supprimer ce produit ?') && remove.mutate()}
          >
            <Trash2 className="size-4" /> Supprimer
          </Button>
        )}
      </div>
    </form>
  )
}
