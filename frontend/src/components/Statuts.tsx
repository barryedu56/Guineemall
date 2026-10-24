import { useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, ShoppingBag, Store, X } from 'lucide-react'
import { useEffect, useEffectEvent, useRef, useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../context/auth'
import { api, formatGNF, timeAgo } from '../lib/api'
import type { StatutGroup } from '../lib/types'

const IMAGE_DURATION_MS = 5000

/** Barre horizontale des statuts des boutiques, comme sur WhatsApp. */
export default function StatutsBar() {
  const queryClient = useQueryClient()
  const [opened, setOpened] = useState<number | null>(null)
  const { data: groups = [] } = useQuery({
    queryKey: ['statuts'],
    queryFn: async () => (await api.get<StatutGroup[]>('/statuts/')).data,
  })

  if (groups.length === 0) return null

  return (
    <section className="mb-6">
      <h2 className="mb-3 text-lg font-bold">Statuts des boutiques</h2>
      <div className="-mx-4 flex gap-4 overflow-x-auto px-4 pb-2">
        {groups.map((group, index) => (
          <button key={group.vendor.slug} onClick={() => setOpened(index)} className="flex w-18 shrink-0 flex-col items-center gap-1.5">
            <span
              className={`rounded-full p-0.5 ${group.all_seen ? 'bg-ink/15' : 'bg-gradient-to-tr from-secondary-dark via-primary-dark to-primary'}`}
            >
              <span className="block rounded-full bg-surface p-0.5">
                <ShopAvatar logo={group.vendor.logo} name={group.vendor.shop_name} className="size-16" />
              </span>
            </span>
            <span className="w-full truncate text-xs font-medium">{group.vendor.shop_name}</span>
          </button>
        ))}
      </div>
      {opened !== null && (
        <StatutViewer
          groups={groups}
          startGroup={opened}
          onClose={() => {
            setOpened(null)
            queryClient.invalidateQueries({ queryKey: ['statuts'] })
          }}
        />
      )}
    </section>
  )
}

export function ShopAvatar({ logo, name, className = '' }: { logo: string | null; name: string; className?: string }) {
  return logo ? (
    <img src={logo} alt={name} className={`rounded-full object-cover ${className}`} />
  ) : (
    <span className={`flex items-center justify-center rounded-full bg-primary-light text-primary-dark ${className}`}>
      <Store className="size-1/2" />
    </span>
  )
}

function StatutViewer({ groups, startGroup, onClose }: { groups: StatutGroup[]; startGroup: number; onClose: () => void }) {
  // Commence au premier statut pas encore vu de la boutique
  const firstUnseen = (g: StatutGroup) => Math.max(0, g.statuts.findIndex((s) => !s.seen))
  const [position, setPosition] = useState({ group: startGroup, index: firstUnseen(groups[startGroup]) })
  const group = groups[position.group]

  function next() {
    if (position.index < group.statuts.length - 1) return setPosition({ ...position, index: position.index + 1 })
    if (position.group < groups.length - 1) {
      return setPosition({ group: position.group + 1, index: firstUnseen(groups[position.group + 1]) })
    }
    onClose()
  }

  function previous() {
    if (position.index > 0) return setPosition({ ...position, index: position.index - 1 })
    if (position.group > 0) setPosition({ group: position.group - 1, index: 0 })
  }

  const onKey = useEffectEvent((e: KeyboardEvent) => {
    if (e.key === 'Escape') onClose()
    if (e.key === 'ArrowRight') next()
    if (e.key === 'ArrowLeft') previous()
  })
  useEffect(() => {
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  const statut = group.statuts[position.index]
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black">
      {/* key : chaque statut repart avec une progression à zéro */}
      <StatutSlide
        key={statut.id}
        group={group}
        index={position.index}
        onNext={next}
        onPrevious={previous}
        onClose={onClose}
      />
    </div>
  )
}

function StatutSlide({
  group,
  index,
  onNext,
  onPrevious,
  onClose,
}: {
  group: StatutGroup
  index: number
  onNext: () => void
  onPrevious: () => void
  onClose: () => void
}) {
  const { user } = useAuth()
  const statut = group.statuts[index]
  const [progress, setProgress] = useState(0)
  const [paused, setPaused] = useState(false)
  const elapsed = useRef(0)
  const advance = useEffectEvent(onNext)

  // Enregistre la vue
  useEffect(() => {
    if (user && !statut.seen) api.post(`/statuts/${statut.id}/view/`).catch(() => {})
  }, [user, statut])

  // Avance automatique pour les images ; les vidéos avancent à la fin de la lecture (onEnded)
  useEffect(() => {
    if (statut.media_type !== 'image' || paused) return
    let last = Date.now()
    const timer = setInterval(() => {
      const now = Date.now()
      elapsed.current += now - last
      last = now
      const p = Math.min(1, elapsed.current / IMAGE_DURATION_MS)
      setProgress(p)
      if (p >= 1) {
        clearInterval(timer)
        advance()
      }
    }, 50)
    return () => clearInterval(timer)
  }, [statut.media_type, paused])

  return (
    <div className="relative flex h-full w-full max-w-md flex-col">
      {/* Barres de progression */}
      <div className="absolute inset-x-0 top-0 z-10 flex gap-1 p-3">
        {group.statuts.map((s, i) => (
          <div key={s.id} className="h-0.5 flex-1 overflow-hidden rounded bg-white/30">
            <div className="h-full bg-white" style={{ width: `${i < index ? 100 : i === index ? progress * 100 : 0}%` }} />
          </div>
        ))}
      </div>

      {/* En-tête */}
      <div className="absolute inset-x-0 top-4 z-10 flex items-center gap-3 px-3 pt-2 text-white">
        <Link to={`/boutique/${group.vendor.slug}`} onClick={onClose} className="flex items-center gap-2">
          <ShopAvatar logo={group.vendor.logo} name={group.vendor.shop_name} className="size-9" />
          <span className="font-semibold">{group.vendor.shop_name}</span>
          {group.vendor.is_verified && <BadgeCheck className="size-4 text-primary" />}
        </Link>
        <span className="text-sm text-white/70">{timeAgo(statut.created_at)}</span>
        <button onClick={onClose} className="ml-auto rounded-full p-1 hover:bg-white/10" aria-label="Fermer">
          <X className="size-6" />
        </button>
      </div>

      {/* Média : appui long = pause */}
      <div
        className="relative flex flex-1 items-center justify-center"
        onPointerDown={() => setPaused(true)}
        onPointerUp={() => setPaused(false)}
      >
        {statut.media_type === 'image' ? (
          <img src={statut.media} alt={statut.caption} className="max-h-full w-full object-contain" />
        ) : (
          <video
            src={statut.media}
            autoPlay
            playsInline
            className="max-h-full w-full object-contain"
            onTimeUpdate={(e) => setProgress(e.currentTarget.currentTime / (e.currentTarget.duration || 1))}
            onEnded={onNext}
          />
        )}
        {/* Zones de navigation : gauche = précédent, droite = suivant */}
        <button className="absolute inset-y-0 left-0 w-1/3" onClick={onPrevious} aria-label="Précédent" />
        <button className="absolute inset-y-0 right-0 w-2/3" onClick={onNext} aria-label="Suivant" />
      </div>

      {/* Légende + produit */}
      <div className="absolute inset-x-0 bottom-0 z-10 space-y-3 bg-gradient-to-t from-black/80 to-transparent p-4 pt-12 text-white">
        {statut.caption && <p className="text-center">{statut.caption}</p>}
        {statut.product && (
          <Link
            to={`/produit/${statut.product.slug}`}
            onClick={onClose}
            className="flex items-center justify-center gap-2 rounded-xl bg-primary py-3 font-semibold text-ink"
          >
            <ShoppingBag className="size-5" />
            {statut.product.name} · {formatGNF(statut.product.price)}
          </Link>
        )}
      </div>
    </div>
  )
}
