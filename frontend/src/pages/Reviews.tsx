import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ImagePlus, MessageSquareQuote } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import Stars, { StarInput } from '../components/Stars'
import { Button, ErrorBox, Textarea } from '../components/ui'
import { useAuth } from '../context/auth'
import { api, errorMessage, formatDate } from '../lib/api'
import type { Paginated, Review } from '../lib/types'

export default function Reviews({ slug, canReview = true }: { slug: string; canReview?: boolean }) {
  const { user } = useAuth()
  const [writing, setWriting] = useState(false)
  const { data } = useQuery({
    queryKey: ['reviews', slug],
    queryFn: async () => (await api.get<Paginated<Review>>(`/products/${slug}/reviews/`)).data,
  })

  return (
    <section id="avis" className="mt-10 scroll-mt-24">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-xl font-bold">Avis clients ({data?.count ?? 0})</h2>
        {user && canReview && !writing && (
          <Button variant="outline" onClick={() => setWriting(true)}>Donner mon avis</Button>
        )}
      </div>
      {writing && <ReviewForm slug={slug} onDone={() => setWriting(false)} />}
      {data?.results.length ? (
        <ul className="space-y-3">
          {data.results.map((r) => (
            <li key={r.id} className="card p-4">
              <div className="flex flex-wrap items-center gap-3">
                <span className="font-semibold">{r.author}</span>
                <Stars rating={r.rating} />
                <span className="text-xs text-ink/50">{formatDate(r.created_at)} · Achat vérifié</span>
              </div>
              {r.comment && <p className="mt-2 whitespace-pre-line">{r.comment}</p>}
              {r.photos.length > 0 && (
                <div className="mt-2 flex gap-2">
                  {r.photos.map((p) => (
                    <a key={p.id} href={p.image} target="_blank" rel="noreferrer">
                      <img src={p.image} alt="" className="size-20 rounded-xl object-cover" />
                    </a>
                  ))}
                </div>
              )}
              {r.vendor_reply && (
                <p className="mt-3 flex gap-2 rounded-xl bg-background p-3 text-sm">
                  <MessageSquareQuote className="size-4 shrink-0 text-primary-dark" />
                  <span><span className="font-semibold">Réponse de la boutique : </span>{r.vendor_reply}</span>
                </p>
              )}
            </li>
          ))}
        </ul>
      ) : (
        !writing && <p className="text-ink/60">Pas encore d'avis. Seuls les clients qui ont reçu le produit peuvent en laisser un.</p>
      )}
    </section>
  )
}

function ReviewForm({ slug, onDone }: { slug: string; onDone: () => void }) {
  const queryClient = useQueryClient()
  const [rating, setRating] = useState(5)
  const [comment, setComment] = useState('')
  const [photos, setPhotos] = useState<File[]>([])
  const [error, setError] = useState<string | null>(null)

  const send = useMutation({
    mutationFn: () => {
      const data = new FormData()
      data.append('rating', String(rating))
      data.append('comment', comment)
      photos.forEach((p) => data.append('uploaded_photos', p))
      return api.post(`/products/${slug}/reviews/`, data)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reviews', slug] })
      queryClient.invalidateQueries({ queryKey: ['product', slug] })
      onDone()
    },
    onError: (e) => setError(errorMessage(e)),
  })

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    send.mutate()
  }

  return (
    <form onSubmit={submit} className="mb-6 space-y-3 card p-4">
      <StarInput value={rating} onChange={setRating} />
      <Textarea label="Votre avis" value={comment} onChange={(e) => setComment(e.target.value)} placeholder="Qualité, livraison, conformité…" />
      <label className="flex w-fit cursor-pointer items-center gap-2 text-sm font-medium text-primary-dark">
        <ImagePlus className="size-5" /> Ajouter des photos ({photos.length}/4)
        <input type="file" accept="image/*" multiple className="hidden" onChange={(e) => setPhotos(Array.from(e.target.files ?? []).slice(0, 4))} />
      </label>
      <ErrorBox message={error} />
      <div className="flex gap-2">
        <Button type="submit" loading={send.isPending}>Publier</Button>
        <Button type="button" variant="ghost" onClick={onDone}>Annuler</Button>
      </div>
    </form>
  )
}
