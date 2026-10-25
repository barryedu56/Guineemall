import { useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, ChevronLeft, ChevronRight, Pause, ShoppingBag, Store, X } from 'lucide-react'
import { useEffect, useEffectEvent, useRef, useState } from 'react'
import { Link } from 'react-router-dom'

import { useAuth } from '../context/auth'
import { useIsOwnShop } from '../context/ownership'
import { api, formatGNF, timeAgo } from '../lib/api'
import type { Statut, StatutGroup } from '../lib/types'
import { Overlay } from './ui'

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
    <section>
      <h2 className="mb-3 text-xl font-extrabold">Statuts des boutiques</h2>
      <div className="no-scrollbar -mx-4 flex gap-3 overflow-x-auto px-4 pb-1">
        {groups.map((group, index) => {
          const latest = group.statuts[group.statuts.length - 1]
          return (
            <button
              key={group.vendor.slug}
              onClick={() => setOpened(index)}
              className="group flex w-20 shrink-0 flex-col items-center gap-2 sm:w-24"
              aria-label={`Statuts de ${group.vendor.shop_name}`}
            >
              {/* Anneau coloré tant qu'il reste des statuts non vus */}
              <span
                className={`relative rounded-full p-[3px] transition group-hover:scale-105 ${group.all_seen ? 'bg-ink/15' : 'bg-linear-to-tr from-secondary-dark via-primary-dark to-primary'}`}
              >
                <span className="block size-[4.25rem] overflow-hidden rounded-full border-[3px] border-background bg-ink sm:size-20">
                  {latest.media_type === 'image' ? (
                    <img src={latest.media} alt="" loading="lazy" className="size-full object-cover" />
                  ) : (
                    <ShopAvatar logo={group.vendor.logo} name={group.vendor.shop_name} className="size-full" />
                  )}
                </span>
                {/* Logo de la boutique en médaillon */}
                <span className="absolute -bottom-0.5 -right-0.5 rounded-full border-2 border-background">
                  <ShopAvatar logo={group.vendor.logo} name={group.vendor.shop_name} className="size-7" />
                </span>
              </span>
              <span className="w-full truncate text-center text-xs font-semibold">{group.vendor.shop_name}</span>
            </button>
          )
        })}
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
    // La page derrière ne défile pas pendant la lecture
    const overflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = overflow
    }
  }, [])

  const statut = group.statuts[position.index]
  const hasPrevious = position.group > 0 || position.index > 0
  return (
    <Overlay>
      <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black" role="dialog" aria-modal aria-label={`Statuts de ${group.vendor.shop_name}`}>
        {/* Fond flouté tiré de l'image (ordinateur) */}
        {statut.media_type === 'image' && (
          <img src={statut.media} alt="" aria-hidden className="absolute inset-0 hidden size-full scale-110 object-cover opacity-40 blur-3xl sm:block" />
        )}
        <button onClick={onClose} className="absolute right-4 top-4 z-20 hidden rounded-full bg-white/10 p-2 text-white hover:bg-white/20 sm:block" aria-label="Fermer">
          <X className="size-6" />
        </button>
        {hasPrevious && (
          <button onClick={previous} className="absolute left-4 z-20 hidden rounded-full bg-white/90 p-2 text-ink shadow-lift hover:bg-white sm:block" aria-label="Précédent">
            <ChevronLeft className="size-6" />
          </button>
        )}
        <button onClick={next} className="absolute right-4 z-20 hidden rounded-full bg-white/90 p-2 text-ink shadow-lift hover:bg-white sm:block" aria-label="Suivant">
          <ChevronRight className="size-6" />
        </button>
        {/* key : chaque statut repart avec une progression à zéro */}
        <StatutSlide key={statut.id} group={group} statut={statut} index={position.index} onNext={next} onPrevious={previous} onClose={onClose} />
      </div>
    </Overlay>
  )
}

function StatutSlide({
  group,
  statut,
  index,
  onNext,
  onPrevious,
  onClose,
}: {
  group: StatutGroup
  statut: Statut
  index: number
  onNext: () => void
  onPrevious: () => void
  onClose: () => void
}) {
  const { user } = useAuth()
  const own = useIsOwnShop(group.vendor.slug)
  const [progress, setProgress] = useState(0)
  const [paused, setPaused] = useState(false)
  const elapsed = useRef(0)
  const advance = useEffectEvent(onNext)

  // Enregistre la vue (pas pour la boutique elle-même)
  useEffect(() => {
    if (user && !own && !statut.seen) api.post(`/statuts/${statut.id}/view/`).catch(() => {})
  }, [user, own, statut])

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
    // Plein écran sur mobile ; format vertical 9:16 centré sur grand écran
    <div className="relative z-10 flex h-dvh w-full flex-col overflow-hidden bg-black sm:h-[92vh] sm:w-auto sm:max-w-[calc(92vh*9/16)] sm:rounded-3xl sm:shadow-2xl sm:aspect-[9/16]">
      {/* Barres de progression */}
      <div className="absolute inset-x-0 top-0 z-10 flex gap-1 px-3 pt-3">
        {group.statuts.map((s, i) => (
          <div key={s.id} className="h-[3px] flex-1 overflow-hidden rounded-full bg-white/30">
            <div className="h-full bg-white" style={{ width: `${i < index ? 100 : i === index ? progress * 100 : 0}%` }} />
          </div>
        ))}
      </div>

      {/* En-tête */}
      <div className="absolute inset-x-0 top-0 z-10 flex items-center gap-3 bg-linear-to-b from-black/60 to-transparent px-3 pb-8 pt-7 text-white">
        <Link to={`/boutique/${group.vendor.slug}`} onClick={onClose} className="flex min-w-0 items-center gap-2">
          <ShopAvatar logo={group.vendor.logo} name={group.vendor.shop_name} className="size-9 ring-2 ring-white/70" />
          <span className="truncate font-semibold">{group.vendor.shop_name}</span>
          {group.vendor.is_verified && <BadgeCheck className="size-4 shrink-0 text-primary" />}
        </Link>
        <span className="shrink-0 text-sm text-white/70">{timeAgo(statut.created_at)}</span>
        {paused && <Pause className="size-4 shrink-0 text-white/80" />}
        <button onClick={onClose} className="ml-auto rounded-full p-1.5 hover:bg-white/15 sm:hidden" aria-label="Fermer">
          <X className="size-6" />
        </button>
      </div>

      {/* Média : appui long = pause */}
      <div
        className="relative flex flex-1 items-center justify-center"
        onPointerDown={() => setPaused(true)}
        onPointerUp={() => setPaused(false)}
        onPointerLeave={() => setPaused(false)}
      >
        {statut.media_type === 'image' ? (
          <img src={statut.media} alt={statut.caption} className="max-h-full w-full select-none object-contain" draggable={false} />
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
        <button className="absolute inset-y-0 left-0 w-1/3" onClick={onPrevious} aria-label="Statut précédent" />
        <button className="absolute inset-y-0 right-0 w-2/3" onClick={onNext} aria-label="Statut suivant" />
      </div>

      {/* Légende + produit */}
      {(statut.caption || statut.product) && (
        <div className="absolute inset-x-0 bottom-0 z-10 space-y-3 bg-linear-to-t from-black/80 via-black/40 to-transparent px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-16 text-white">
          {statut.caption && <p className="text-center text-base font-medium leading-snug drop-shadow">{statut.caption}</p>}
          {statut.product && (
            <Link
              to={`/produit/${statut.product.slug}`}
              onClick={onClose}
              className="flex items-center gap-3 rounded-2xl bg-white p-2 pr-4 text-ink shadow-lift transition hover:bg-primary-light"
            >
              <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary">
                <ShoppingBag className="size-5" />
              </span>
              <span className="min-w-0 flex-1">
                <span className="block truncate text-sm font-semibold">{statut.product.name}</span>
                <span className="block text-sm font-extrabold">{formatGNF(statut.product.price)}</span>
              </span>
              <span className="text-sm font-bold text-primary-dark">{own ? 'Voir' : 'Acheter'}</span>
            </Link>
          )}
        </div>
      )}
    </div>
  )
}
