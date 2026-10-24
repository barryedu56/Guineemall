import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ImageOff, ImagePlus, Package, Pencil, Plus, Trash2, X } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import { Badge, Button, Empty, ErrorBox, Input, Select, Spinner, Textarea } from '../../components/ui'
import { api, errorMessage, formatGNF } from '../../lib/api'
import type { Category, Paginated, VendorProduct } from '../../lib/types'

export default function VendorProductsPage() {
  const [editing, setEditing] = useState<VendorProduct | 'new' | null>(null)
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-products'],
    queryFn: async () => (await api.get<Paginated<VendorProduct>>('/vendor/products/')).data,
  })

  if (editing) return <ProductForm product={editing === 'new' ? null : editing} onDone={() => setEditing(null)} />
  if (isLoading) return <Spinner />

  return (
    <div className="space-y-4">
      <div className="flex justify-end">
        <Button onClick={() => setEditing('new')}>
          <Plus className="size-5" /> Nouveau produit
        </Button>
      </div>
      {!data?.results.length ? (
        <Empty icon={<Package />} title="Aucun produit">
          <p className="text-ink/60">Ajoutez votre premier produit pour commencer à vendre.</p>
        </Empty>
      ) : (
        <ul className="divide-y divide-ink/5 rounded-2xl bg-surface ring-1 ring-ink/5">
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
              <Button variant="ghost" onClick={() => setEditing(p)} aria-label="Modifier">
                <Pencil className="size-4" />
              </Button>
            </li>
          ))}
        </ul>
      )}
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
  const [current, setCurrent] = useState<VendorProduct | null>(product)
  const [newImages, setNewImages] = useState<File[]>([])
  const [error, setError] = useState<string | null>(null)

  const { data: categories = [] } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get<Category[]>('/categories/')).data,
  })

  const save = useMutation({
    mutationFn: async () => {
      const payload = { ...form, compare_at_price: form.compare_at_price || null }
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
    <form onSubmit={submit} className="max-w-2xl space-y-4 rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
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
        <Input label="Prix (GNF)" type="number" min={0} value={form.price} onChange={set('price')} required />
        <Input label="Ancien prix (GNF)" type="number" min={0} value={form.compare_at_price} onChange={set('compare_at_price')} hint="Optionnel, pour afficher une promo" />
        <Input label="Stock" type="number" min={0} value={form.stock} onChange={set('stock')} required />
      </div>
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
