import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { CircleDot, Eye, ImagePlus, Trash2 } from 'lucide-react'
import { useMemo, useState, type FormEvent } from 'react'

import { Badge, Button, Empty, ErrorBox, Input, Select, Spinner } from '../../components/ui'
import { api, errorMessage, timeAgo } from '../../lib/api'
import { fetchAll } from '../../lib/queries'
import type { Paginated, VendorProduct, VendorStatut } from '../../lib/types'

export default function VendorStatutsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-statuts'],
    queryFn: async () => (await api.get<Paginated<VendorStatut>>('/vendor/statuts/')).data,
  })

  return (
    <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
      <PublishForm />
      <section>
        <h2 className="mb-3 text-lg font-semibold">Mes statuts</h2>
        {isLoading ? (
          <Spinner />
        ) : !data?.results.length ? (
          <Empty icon={<CircleDot />} title="Aucun statut publié" />
        ) : (
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {data.results.map((s) => (
              <StatutCard key={s.id} statut={s} />
            ))}
          </div>
        )}
      </section>
    </div>
  )
}

function PublishForm() {
  const queryClient = useQueryClient()
  const [media, setMedia] = useState<File | null>(null)
  const [caption, setCaption] = useState('')
  const [product, setProduct] = useState('')
  const [error, setError] = useState<string | null>(null)
  const preview = useMemo(() => (media ? URL.createObjectURL(media) : null), [media])

  const { data: allProducts = [] } = useQuery({
    queryKey: ['vendor-products', 'all'],
    queryFn: () => fetchAll<VendorProduct>('/vendor/products/'),
  })

  const publish = useMutation({
    mutationFn: () => {
      const data = new FormData()
      data.append('media', media!)
      data.append('caption', caption)
      if (product) data.append('product_slug', product)
      return api.post('/vendor/statuts/', data)
    },
    onSuccess: () => {
      setMedia(null)
      setCaption('')
      setProduct('')
      queryClient.invalidateQueries({ queryKey: ['vendor-statuts'] })
      queryClient.invalidateQueries({ queryKey: ['statuts'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    publish.mutate()
  }

  return (
    <form onSubmit={submit} className="h-fit space-y-4 card p-5">
      <h2 className="text-lg font-semibold">Publier un statut</h2>
      <label className="relative flex aspect-[9/16] max-h-80 w-full cursor-pointer items-center justify-center overflow-hidden rounded-2xl bg-ink/5 text-ink/50 hover:bg-ink/10">
        {preview ? (
          media?.type.startsWith('video/') ? (
            <video src={preview} className="size-full object-cover" muted autoPlay loop playsInline />
          ) : (
            <img src={preview} alt="" className="size-full object-cover" />
          )
        ) : (
          <span className="flex flex-col items-center gap-2 text-sm">
            <ImagePlus className="size-8" />
            Photo ou vidéo
          </span>
        )}
        <input type="file" accept="image/*,video/*" className="hidden" onChange={(e) => setMedia(e.target.files?.[0] ?? null)} />
      </label>
      <Input label="Légende" value={caption} onChange={(e) => setCaption(e.target.value)} maxLength={280} placeholder="Nouvel arrivage ! -20 % ce week-end" />
      <Select label="Produit à mettre en avant (optionnel)" value={product} onChange={(e) => setProduct(e.target.value)}>
        <option value="">Aucun</option>
        {allProducts.map((p) => (
          <option key={p.slug} value={p.slug}>
            {p.name}
          </option>
        ))}
      </Select>
      <ErrorBox message={error} />
      <Button type="submit" className="w-full" disabled={!media} loading={publish.isPending}>
        Publier pour 24 h
      </Button>
    </form>
  )
}

function StatutCard({ statut }: { statut: VendorStatut }) {
  const queryClient = useQueryClient()
  const [showViewers, setShowViewers] = useState(false)
  const { data: viewers } = useQuery({
    queryKey: ['statut-viewers', statut.id],
    queryFn: async () => (await api.get<{ name: string; viewed_at: string }[]>(`/vendor/statuts/${statut.id}/viewers/`)).data,
    enabled: showViewers,
  })
  const remove = useMutation({
    mutationFn: () => api.delete(`/vendor/statuts/${statut.id}/`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vendor-statuts'] })
      queryClient.invalidateQueries({ queryKey: ['statuts'] })
    },
  })

  return (
    <div className={`overflow-hidden card ${statut.is_active ? '' : 'opacity-60'}`}>
      <div className="relative aspect-[9/16] bg-black">
        {statut.media_type === 'video' ? (
          <video src={statut.media} className="size-full object-cover" muted playsInline />
        ) : (
          <img src={statut.media} alt="" className="size-full object-cover" />
        )}
        <div className="absolute left-2 top-2">
          {statut.is_active ? <Badge tone="success">En ligne</Badge> : <Badge>Expiré</Badge>}
        </div>
        {statut.caption && (
          <p className="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/80 p-2 pt-6 text-xs text-white">{statut.caption}</p>
        )}
      </div>
      <div className="flex items-center gap-1 p-2 text-sm">
        <button onClick={() => setShowViewers(!showViewers)} className="flex items-center gap-1 rounded-lg px-1.5 py-1 hover:bg-ink/5">
          <Eye className="size-4" /> {statut.views_count}
        </button>
        <span className="text-xs text-ink/50">{timeAgo(statut.created_at)}</span>
        <button
          onClick={() => window.confirm('Supprimer ce statut ?') && remove.mutate()}
          className="ml-auto rounded-lg p-1 text-ink/40 hover:text-red-600"
          aria-label="Supprimer"
        >
          <Trash2 className="size-4" />
        </button>
      </div>
      {showViewers && (
        <ul className="max-h-40 overflow-y-auto border-t border-ink/5 px-3 py-2 text-xs">
          {viewers?.length ? (
            viewers.map((v, i) => (
              <li key={i} className="flex justify-between py-0.5">
                <span>{v.name}</span>
                <span className="text-ink/50">{timeAgo(v.viewed_at)}</span>
              </li>
            ))
          ) : (
            <li className="text-ink/50">Personne pour l'instant</li>
          )}
        </ul>
      )}
    </div>
  )
}
