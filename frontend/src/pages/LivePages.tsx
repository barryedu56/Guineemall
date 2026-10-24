import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Radio, Send } from 'lucide-react'
import { useEffect, useRef, useState, type FormEvent } from 'react'
import { Link, useParams } from 'react-router-dom'

import ProductCard from '../components/ProductCard'
import { ShopAvatar } from '../components/Statuts'
import { Empty, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { api, formatDate, tokens } from '../lib/api'
import { useLives } from '../lib/queries'
import type { Live, LiveComment } from '../lib/types'

export function LiveStrip() {
  const { data: lives = [] } = useLives()
  if (!lives.length) return null
  return (
    <section className="mb-6">
      <h2 className="mb-3 flex items-center gap-2 text-lg font-bold"><Radio className="size-5 text-red-600" /> Ventes en direct</h2>
      <div className="-mx-4 flex gap-3 overflow-x-auto px-4 pb-2">
        {lives.map((l) => <LiveCard key={l.id} live={l} />)}
      </div>
    </section>
  )
}

function LiveCard({ live }: { live: Live }) {
  return (
    <Link to={`/lives/${live.id}`} className="w-64 shrink-0 rounded-2xl bg-surface p-4 ring-1 ring-ink/5 hover:ring-primary">
      <div className="flex items-center gap-2">
        <ShopAvatar logo={live.shop.logo} name={live.shop.shop_name} className="size-9" />
        <span className="truncate font-semibold">{live.shop.shop_name}</span>
      </div>
      <p className="mt-2 line-clamp-2 font-medium">{live.title}</p>
      {live.status === 'live' ? (
        <span className="mt-2 inline-flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">● EN DIRECT</span>
      ) : (
        <span className="mt-2 block text-sm text-ink/60">{formatDate(live.starts_at)}</span>
      )}
    </Link>
  )
}

export function LivesPage() {
  const { data: lives, isLoading } = useLives()
  if (isLoading) return <Spinner />
  if (!lives?.length) return <Empty icon={<Radio />} title="Aucun direct prévu pour le moment" />
  return (
    <>
      <h1 className="mb-4 text-2xl font-bold">Ventes en direct</h1>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">{lives.map((l) => <LiveCard key={l.id} live={l} />)}</div>
    </>
  )
}

export function LivePage() {
  const { id } = useParams()
  const { data: live, isLoading } = useQuery({
    queryKey: ['live', id],
    queryFn: async () => (await api.get<Live>(`/lives/${id}/`)).data,
  })
  if (isLoading) return <Spinner />
  if (!live) return <p className="py-16 text-center">Ce direct est terminé.</p>
  return <LiveRoom key={live.id} live={live} />
}

function LiveRoom({ live }: { live: Live }) {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [comments, setComments] = useState<LiveComment[]>(live.comments ?? [])
  const [pinned, setPinned] = useState<string | null>(live.pinned_product ?? null)
  const [text, setText] = useState('')
  const socket = useRef<WebSocket | null>(null)
  const bottom = useRef<HTMLDivElement>(null)

  // Commentaires et produit épinglé en temps réel
  useEffect(() => {
    const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws'
    const token = tokens.access
    const ws = new WebSocket(`${protocol}://${window.location.host}/ws/live/${live.id}/${token ? `?token=${token}` : ''}`)
    ws.onmessage = (msg) => {
      const event = JSON.parse(msg.data)
      if (event.type === 'live.comment') setComments((c) => [...c.slice(-99), event.comment])
      if (event.type === 'live.pinned') setPinned(event.product)
      if (event.type === 'live.status') queryClient.invalidateQueries({ queryKey: ['live', String(live.id)] })
    }
    socket.current = ws
    return () => ws.close()
  }, [live.id, queryClient])

  useEffect(() => {
    bottom.current?.scrollIntoView({ block: 'end' })
  }, [comments.length])

  const pinnedProduct = live.products?.find((p) => p.slug === pinned)

  function send(e: FormEvent) {
    e.preventDefault()
    if (!text.trim()) return
    socket.current?.send(JSON.stringify({ type: 'comment', text }))
    setText('')
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[1fr_340px]">
      <div className="space-y-4">
        <div className="aspect-video overflow-hidden rounded-2xl bg-black">
          {live.embed_url ? (
            <iframe src={live.embed_url} title={live.title} className="size-full" allow="autoplay; encrypted-media; fullscreen" allowFullScreen />
          ) : (
            <a href={live.stream_url} target="_blank" rel="noreferrer" className="flex size-full items-center justify-center font-semibold text-white underline">
              Ouvrir le direct
            </a>
          )}
        </div>
        <div className="flex items-center gap-3">
          <ShopAvatar logo={live.shop.logo} name={live.shop.shop_name} className="size-11" />
          <div>
            <h1 className="text-xl font-bold">{live.title}</h1>
            <Link to={`/boutique/${live.shop.slug}`} className="text-sm text-primary-dark hover:underline">{live.shop.shop_name}</Link>
          </div>
          {live.status === 'live' && <span className="ml-auto rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">● EN DIRECT</span>}
        </div>
        {pinnedProduct && (
          <div className="rounded-2xl bg-primary-light/40 p-3 ring-2 ring-primary-dark">
            <p className="mb-2 text-sm font-semibold">Présenté en ce moment</p>
            <div className="max-w-56"><ProductCard product={pinnedProduct} /></div>
          </div>
        )}
        {!!live.products?.length && (
          <div>
            <h2 className="mb-2 font-semibold">Produits du direct</h2>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              {live.products.map((p) => <ProductCard key={p.id} product={p} />)}
            </div>
          </div>
        )}
      </div>

      <aside className="flex h-[70vh] flex-col rounded-2xl bg-surface ring-1 ring-ink/5 lg:sticky lg:top-24">
        <p className="border-b border-ink/5 px-4 py-3 font-semibold">Commentaires</p>
        <div className="flex-1 space-y-2 overflow-y-auto p-3 text-sm">
          {comments.map((c) => (
            <p key={c.id}><span className="font-semibold">{c.author} </span>{c.text}</p>
          ))}
          <div ref={bottom} />
        </div>
        {user ? (
          <form onSubmit={send} className="flex gap-2 border-t border-ink/5 p-2">
            <input value={text} onChange={(e) => setText(e.target.value)} maxLength={300} placeholder="Écrire un commentaire…" className="min-w-0 flex-1 rounded-full bg-background px-4 py-2 outline-none" />
            <button className="rounded-full bg-primary p-2.5" aria-label="Envoyer"><Send className="size-4" /></button>
          </form>
        ) : (
          <Link to={`/connexion?suivant=/lives/${live.id}`} className="border-t border-ink/5 p-3 text-center text-sm text-primary-dark">
            Connectez-vous pour commenter
          </Link>
        )}
      </aside>
    </div>
  )
}
