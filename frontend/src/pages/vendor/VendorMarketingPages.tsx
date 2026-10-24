import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { MessageSquareQuote, Radio, Tag, Trash2 } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'

import Price from '../../components/Price'
import Stars from '../../components/Stars'
import { Badge, Button, Empty, ErrorBox, Input, Select, Spinner, Textarea } from '../../components/ui'
import { api, errorMessage, formatDate } from '../../lib/api'
import type { Coupon, Paginated, Review, VendorLive, VendorProduct } from '../../lib/types'

export function VendorCouponsPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ code: '', type: 'percent', value: '', min_amount: '0', usage_limit: '', ends_at: '', description: '' })
  const [error, setError] = useState<string | null>(null)
  const { data: coupons = [], isLoading } = useQuery({
    queryKey: ['vendor-coupons'],
    queryFn: async () => (await api.get<Coupon[]>('/vendor/coupons/')).data,
  })
  const create = useMutation({
    mutationFn: () =>
      api.post('/vendor/coupons/', {
        ...form,
        value: Number(form.value),
        min_amount: Number(form.min_amount || 0),
        usage_limit: form.usage_limit ? Number(form.usage_limit) : null,
        ends_at: form.ends_at ? new Date(form.ends_at).toISOString() : null,
      }),
    onSuccess: () => {
      setForm({ ...form, code: '', value: '', description: '' })
      queryClient.invalidateQueries({ queryKey: ['vendor-coupons'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })
  const toggle = useMutation({
    mutationFn: (c: Coupon) => api.patch(`/vendor/coupons/${c.id}/`, { is_active: !c.is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['vendor-coupons'] }),
  })
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    create.mutate()
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
      <form onSubmit={submit} className="h-fit space-y-3 rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
        <h2 className="text-lg font-semibold">Nouveau code promo</h2>
        <Input label="Code" value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value.toUpperCase() })} placeholder="TABASKI20" required />
        <div className="grid grid-cols-2 gap-3">
          <Select label="Type" value={form.type} onChange={set('type')}>
            <option value="percent">Pourcentage</option>
            <option value="fixed">Montant fixe</option>
          </Select>
          <Input label={form.type === 'percent' ? 'Réduction (%)' : 'Réduction (GNF)'} type="number" min={1} value={form.value} onChange={set('value')} required />
        </div>
        <Input label="Achat minimum (GNF)" type="number" min={0} value={form.min_amount} onChange={set('min_amount')} />
        <div className="grid grid-cols-2 gap-3">
          <Input label="Utilisations max" type="number" min={1} value={form.usage_limit} onChange={set('usage_limit')} hint="Vide = illimité" />
          <Input label="Fin" type="date" value={form.ends_at} onChange={set('ends_at')} />
        </div>
        <Input label="Description" value={form.description} onChange={set('description')} />
        <ErrorBox message={error} />
        <Button type="submit" loading={create.isPending} className="w-full">Créer</Button>
        <p className="text-xs text-ink/60">La réduction ne s'applique qu'à vos produits et reste à votre charge.</p>
      </form>
      <section>
        <h2 className="mb-3 text-lg font-semibold">Mes codes</h2>
        {isLoading ? <Spinner /> : !coupons.length ? <Empty icon={<Tag />} title="Aucun code promo" /> : (
          <ul className="divide-y divide-ink/5 rounded-2xl bg-surface ring-1 ring-ink/5">
            {coupons.map((c) => (
              <li key={c.id} className="flex items-center gap-3 p-3">
                <div className="flex-1">
                  <p className="font-bold">{c.code} {!c.is_active && <Badge>Désactivé</Badge>}</p>
                  <p className="text-sm text-ink/60">
                    {c.type === 'percent' ? `-${c.value} %` : <>-<Price amount={c.value} /></>} · utilisé {c.used_count} fois
                    {c.usage_limit ? ` / ${c.usage_limit}` : ''}{c.ends_at ? ` · jusqu'au ${formatDate(c.ends_at)}` : ''}
                  </p>
                </div>
                <Button variant="outline" onClick={() => toggle.mutate(c)}>{c.is_active ? 'Désactiver' : 'Activer'}</Button>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  )
}

export function VendorReviewsPage() {
  const queryClient = useQueryClient()
  const [replies, setReplies] = useState<Record<number, string>>({})
  const { data, isLoading } = useQuery({
    queryKey: ['vendor-reviews'],
    queryFn: async () => (await api.get<Paginated<Review>>('/vendor/reviews/')).data,
  })
  const reply = useMutation({
    mutationFn: ({ id, text }: { id: number; text: string }) => api.post(`/vendor/reviews/${id}/reply/`, { reply: text }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['vendor-reviews'] }),
  })

  if (isLoading) return <Spinner />
  if (!data?.results.length) return <Empty icon={<MessageSquareQuote />} title="Aucun avis pour le moment" />

  return (
    <ul className="space-y-3">
      {data.results.map((r) => (
        <li key={r.id} className="space-y-2 rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
          <div className="flex flex-wrap items-center gap-3">
            <Stars rating={r.rating} />
            <span className="font-semibold">{r.author}</span>
            {r.product && <Link to={`/produit/${r.product.slug}`} className="text-sm text-primary-dark hover:underline">{r.product.name}</Link>}
            <span className="text-xs text-ink/50">{formatDate(r.created_at)}</span>
          </div>
          {r.comment && <p>{r.comment}</p>}
          {r.photos.length > 0 && (
            <div className="flex gap-2">{r.photos.map((p) => <img key={p.id} src={p.image} alt="" className="size-16 rounded-lg object-cover" />)}</div>
          )}
          {r.vendor_reply ? (
            <p className="rounded-xl bg-background p-3 text-sm"><span className="font-semibold">Votre réponse : </span>{r.vendor_reply}</p>
          ) : (
            <form
              onSubmit={(e) => {
                e.preventDefault()
                reply.mutate({ id: r.id, text: replies[r.id] ?? '' })
              }}
              className="flex gap-2"
            >
              <input
                value={replies[r.id] ?? ''}
                onChange={(e) => setReplies({ ...replies, [r.id]: e.target.value })}
                placeholder="Répondre publiquement…"
                className="min-w-0 flex-1 rounded-xl border border-ink/15 px-3 py-2"
              />
              <Button type="submit" variant="outline" disabled={!replies[r.id]?.trim()}>Répondre</Button>
            </form>
          )}
        </li>
      ))}
    </ul>
  )
}

export function VendorLivesPage() {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ title: '', stream_url: '', starts_at: '', description: '', product_slugs: [] as string[] })
  const [error, setError] = useState<string | null>(null)
  const { data: lives = [], isLoading } = useQuery({
    queryKey: ['vendor-lives'],
    queryFn: async () => (await api.get<VendorLive[]>('/vendor/lives/')).data,
  })
  const { data: products } = useQuery({
    queryKey: ['vendor-products'],
    queryFn: async () => (await api.get<Paginated<VendorProduct>>('/vendor/products/')).data,
  })
  const refresh = () => queryClient.invalidateQueries({ queryKey: ['vendor-lives'] })
  const create = useMutation({
    mutationFn: () => api.post('/vendor/lives/', { ...form, starts_at: new Date(form.starts_at).toISOString() }),
    onSuccess: () => {
      setForm({ title: '', stream_url: '', starts_at: '', description: '', product_slugs: [] })
      refresh()
    },
    onError: (e) => setError(errorMessage(e)),
  })
  const action = useMutation({
    mutationFn: ({ id, kind, product }: { id: number; kind: 'start' | 'end' | 'pin' | 'delete'; product?: string }) =>
      kind === 'delete' ? api.delete(`/vendor/lives/${id}/`) : api.post(`/vendor/lives/${id}/${kind}/`, { product }),
    onSuccess: refresh,
    onError: (e) => setError(errorMessage(e)),
  })

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    create.mutate()
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[380px_1fr]">
      <form onSubmit={submit} className="h-fit space-y-3 rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
        <h2 className="text-lg font-semibold">Programmer un direct</h2>
        <p className="text-sm text-ink/60">
          Lancez votre direct sur Facebook, YouTube ou TikTok, puis collez son lien ici : vos clients le regardent sur GuinéeMall
          et achètent les produits affichés en dessous.
        </p>
        <Input label="Titre" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} required />
        <Input label="Lien du direct" type="url" value={form.stream_url} onChange={(e) => setForm({ ...form, stream_url: e.target.value })} required placeholder="https://www.youtube.com/live/…" />
        <Input label="Début" type="datetime-local" value={form.starts_at} onChange={(e) => setForm({ ...form, starts_at: e.target.value })} required />
        <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
        <fieldset>
          <legend className="text-sm font-medium">Produits présentés</legend>
          <div className="mt-1 max-h-40 space-y-1 overflow-y-auto">
            {products?.results.map((p) => (
              <label key={p.slug} className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={form.product_slugs.includes(p.slug)}
                  onChange={(e) =>
                    setForm({
                      ...form,
                      product_slugs: e.target.checked ? [...form.product_slugs, p.slug] : form.product_slugs.filter((s) => s !== p.slug),
                    })
                  }
                  className="accent-primary-dark"
                />
                {p.name}
              </label>
            ))}
          </div>
        </fieldset>
        <ErrorBox message={error} />
        <Button type="submit" loading={create.isPending} className="w-full">Programmer</Button>
      </form>

      <section className="space-y-3">
        {isLoading ? <Spinner /> : !lives.length ? <Empty icon={<Radio />} title="Aucun direct" /> : lives.map((l) => (
          <article key={l.id} className="space-y-3 rounded-2xl bg-surface p-4 ring-1 ring-ink/5">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p className="font-semibold">{l.title}</p>
                <p className="text-sm text-ink/60">{formatDate(l.starts_at)}</p>
              </div>
              <Badge tone={l.status === 'live' ? 'danger' : l.status === 'ended' ? 'neutral' : 'info'}>
                {l.status === 'live' ? '● En direct' : l.status === 'ended' ? 'Terminé' : 'Programmé'}
              </Badge>
            </div>
            <div className="flex flex-wrap gap-2">
              {l.status === 'scheduled' && <Button onClick={() => action.mutate({ id: l.id, kind: 'start' })}>Démarrer (prévenir les clients)</Button>}
              {l.status === 'live' && <Button variant="outline" onClick={() => action.mutate({ id: l.id, kind: 'end' })}>Terminer</Button>}
              <Link to={`/lives/${l.id}`} className="self-center text-sm text-primary-dark underline">Voir la page</Link>
              {l.status !== 'live' && (
                <button onClick={() => window.confirm('Supprimer ce direct ?') && action.mutate({ id: l.id, kind: 'delete' })} className="ml-auto p-2 text-ink/40 hover:text-red-600" aria-label="Supprimer">
                  <Trash2 className="size-4" />
                </button>
              )}
            </div>
            {l.status === 'live' && l.product_slugs.length > 0 && (
              <div>
                <p className="mb-1 text-sm font-medium">Épingler le produit présenté :</p>
                <div className="flex flex-wrap gap-2">
                  {l.product_slugs.map((slug) => (
                    <button key={slug} onClick={() => action.mutate({ id: l.id, kind: 'pin', product: slug })} className="rounded-full bg-background px-3 py-1 text-sm ring-1 ring-ink/10 hover:bg-primary-light">
                      {products?.results.find((p) => p.slug === slug)?.name ?? slug}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </article>
        ))}
      </section>
    </div>
  )
}
