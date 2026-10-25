import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, ExternalLink, LocateFixed, MapPin } from 'lucide-react'
import { useMemo, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'

import { ShopAvatar } from '../../components/Statuts'
import { Button, ErrorBox, Input, PhoneInput, Select, Textarea } from '../../components/ui'
import { useSiteConfig } from '../../context/settings'
import { api, errorMessage, formatDate } from '../../lib/api'
import type { MyShop } from '../../lib/types'
import { useMyShop } from './useMyShop'

export default function ShopSettingsPage() {
  const { data: shop } = useMyShop()
  if (!shop) return null
  return (
    <div className="grid max-w-5xl gap-6 lg:grid-cols-2">
      <ShopSettingsForm shop={shop} />
      <PickupAddressForm shop={shop} />
      {!shop.is_verified && <VerificationForm />}
    </div>
  )
}

interface VerificationRequest {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  status_label: string
  admin_note: string
  created_at: string
}

function VerificationForm() {
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const [chosenType, setType] = useState<string | null>(null)
  const type = chosenType ?? config?.choices.document_type[0]?.value ?? ''
  const [number, setNumber] = useState('')
  const [files, setFiles] = useState<Record<string, File | null>>({ document_front: null, document_back: null, shop_photo: null })
  const [error, setError] = useState<string | null>(null)
  const { data: requests = [] } = useQuery({
    queryKey: ['verification'],
    queryFn: async () => (await api.get<VerificationRequest[]>('/vendor/verification/')).data,
  })
  const last = requests[0]
  const send = useMutation({
    mutationFn: () => {
      const data = new FormData()
      data.append('document_type', type)
      data.append('document_number', number)
      Object.entries(files).forEach(([k, f]) => f && data.append(k, f))
      return api.post('/vendor/verification/', data)
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['verification'] }),
    onError: (e) => setError(errorMessage(e)),
  })

  if (last?.status === 'pending') {
    return (
      <div className="h-fit rounded-2xl bg-amber-50 p-5 text-amber-900">
        <p className="font-semibold">Demande de badge en cours d'examen</p>
        <p className="text-sm">Envoyée le {formatDate(last.created_at)}. Vous serez prévenu de la décision.</p>
      </div>
    )
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        setError(null)
        send.mutate()
      }}
      className="h-fit space-y-3 card p-6"
    >
      <h2 className="flex items-center gap-2 text-lg font-semibold"><BadgeCheck className="size-5 text-primary-dark" /> Badge « Vendeur vérifié »</h2>
      <p className="text-sm text-ink/70">Vos documents sont vus uniquement par l'équipe {config?.site.name} et ne sont jamais affichés.</p>
      {last?.status === 'rejected' && (
        <p className="rounded-xl bg-red-50 p-3 text-sm text-red-700">Demande précédente refusée{last.admin_note ? ` : ${last.admin_note}` : '.'}</p>
      )}
      <Select label="Document" value={type} onChange={(e) => setType(e.target.value)}>
        {config?.choices.document_type.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
      </Select>
      <Input label="Numéro du document" value={number} onChange={(e) => setNumber(e.target.value)} required />
      {[
        ['document_front', 'Photo du recto *'],
        ['document_back', 'Photo du verso'],
        ['shop_photo', 'Photo de votre boutique'],
      ].map(([key, label]) => (
        <label key={key} className="block text-sm">
          <span className="font-medium">{label}</span>
          <input type="file" accept="image/*" required={key === 'document_front'} onChange={(e) => setFiles({ ...files, [key]: e.target.files?.[0] ?? null })} className="mt-1 block w-full text-sm" />
        </label>
      ))}
      <ErrorBox message={error} />
      <Button type="submit" loading={send.isPending}>Envoyer ma demande</Button>
    </form>
  )
}

function ShopSettingsForm({ shop }: { shop: MyShop }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ shop_name: shop.shop_name, description: shop.description, whatsapp: shop.whatsapp })
  const [logo, setLogo] = useState<File | null>(null)
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const save = useMutation({
    mutationFn: async () => {
      const data = new FormData()
      Object.entries(form).forEach(([k, v]) => data.append(k, v))
      if (logo) data.append('logo', logo)
      return (await api.patch<MyShop>('/vendor/shop/', data)).data
    },
    onSuccess: (updated) => {
      queryClient.setQueryData(['my-shop'], updated)
      queryClient.invalidateQueries({ queryKey: ['me'] })
      setLogo(null)
      setSaved(true)
      setTimeout(() => setSaved(false), 2000)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const logoPreview = useMemo(() => (logo ? URL.createObjectURL(logo) : null), [logo])

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    save.mutate()
  }

  return (
    <form onSubmit={submit} className="h-fit space-y-4 card p-6">
      <div className="flex items-center gap-4">
        <ShopAvatar logo={logoPreview ?? shop.logo} name={shop.shop_name} className="size-20" />
        <label className="cursor-pointer text-sm font-medium text-primary-dark hover:underline">
          Changer le logo
          <input type="file" accept="image/*" className="hidden" onChange={(e) => setLogo(e.target.files?.[0] ?? null)} />
        </label>
        {shop.is_verified && (
          <span className="ml-auto flex items-center gap-1 text-sm font-medium text-primary-dark">
            <BadgeCheck className="size-5" /> Vendeur vérifié
          </span>
        )}
      </div>
      <Input label="Nom de la boutique" value={form.shop_name} onChange={(e) => setForm({ ...form, shop_name: e.target.value })} required />
      <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
      <PhoneInput label="Numéro WhatsApp" value={form.whatsapp} onValueChange={(whatsapp) => setForm({ ...form, whatsapp })} hint="Affiché sur vos produits avec le bouton « Commander via WhatsApp »." />
      <ErrorBox message={error} />
      <div className="flex flex-wrap items-center gap-4">
        <Button type="submit" loading={save.isPending}>
          {saved ? 'Enregistré ✓' : 'Enregistrer'}
        </Button>
        {shop.status === 'approved' && (
          <Link to={`/boutique/${shop.slug}`} className="flex items-center gap-1 text-sm text-primary-dark hover:underline">
            Voir ma boutique <ExternalLink className="size-4" />
          </Link>
        )}
      </div>
    </form>
  )
}

/** Où le livreur vient chercher les colis : indispensable pour demander un livreur. */
function PickupAddressForm({ shop }: { shop: MyShop }) {
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const locations = config?.delivery_locations ?? []
  const [form, setForm] = useState({
    pickup_city: shop.pickup_city,
    pickup_commune: shop.pickup_commune,
    pickup_quartier: shop.pickup_quartier,
    pickup_landmark: shop.pickup_landmark,
    pickup_phone: shop.pickup_phone,
    pickup_hours: hoursText(parseHours(shop.pickup_hours)),
    pickup_latitude: shop.pickup_latitude,
    pickup_longitude: shop.pickup_longitude,
  })
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [locating, setLocating] = useState(false)
  const city = form.pickup_city || locations[0]?.city || ''
  const communes = locations.find((l) => l.city === city)?.communes ?? []
  const commune = form.pickup_commune || communes[0] || ''
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  const save = useMutation({
    mutationFn: async () =>
      (await api.patch<MyShop>('/vendor/shop/', { ...form, pickup_city: city, pickup_commune: commune })).data,
    onSuccess: (updated) => {
      queryClient.setQueryData(['my-shop'], updated)
      setSaved(true)
      setTimeout(() => setSaved(false), 2000)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  function locate() {
    if (!navigator.geolocation) return setError('Votre appareil ne donne pas sa position.')
    setLocating(true)
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setLocating(false)
        setForm((f) => ({ ...f, pickup_latitude: pos.coords.latitude.toFixed(6), pickup_longitude: pos.coords.longitude.toFixed(6) }))
      },
      () => {
        setLocating(false)
        setError('Position refusée. Autorisez la localisation dans votre navigateur.')
      },
      { enableHighAccuracy: true },
    )
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        setError(null)
        save.mutate()
      }}
      className="h-fit space-y-4 card p-6"
    >
      <div>
        <h2 className="flex items-center gap-2 text-lg font-semibold"><MapPin className="size-5 text-primary-dark" /> Adresse de retrait des colis</h2>
        <p className="text-sm text-ink/70">Le livreur que vous choisissez la voit pour venir chercher le colis. Elle n'est pas affichée aux clients.</p>
      </div>
      {!shop.has_pickup_address && (
        <p className="rounded-xl bg-amber-100 p-3 text-sm font-medium text-amber-900">À remplir pour pouvoir confier vos colis à un livreur.</p>
      )}
      <div className="grid gap-4 sm:grid-cols-2">
        <Select label="Ville" value={city} onChange={(e) => setForm({ ...form, pickup_city: e.target.value, pickup_commune: '' })}>
          {locations.map((l) => <option key={l.city}>{l.city}</option>)}
        </Select>
        <Select label="Commune" value={commune} onChange={set('pickup_commune')}>
          {communes.map((c) => <option key={c}>{c}</option>)}
        </Select>
      </div>
      <Input label="Quartier" value={form.pickup_quartier} onChange={set('pickup_quartier')} required placeholder="Kipé" />
      <Textarea label="Repère précis" value={form.pickup_landmark} onChange={set('pickup_landmark')} required placeholder="Immeuble bleu face à la pharmacie, 1er étage" />
      <PhoneInput label="Téléphone pour le livreur" value={form.pickup_phone} onValueChange={(pickup_phone) => setForm({ ...form, pickup_phone })} hint="Vide = votre numéro WhatsApp" />
      <HoursPicker value={form.pickup_hours} onChange={(pickup_hours) => setForm((f) => ({ ...f, pickup_hours }))} />
      <div className="flex flex-wrap items-center gap-3 text-sm">
        <Button type="button" variant="outline" size="sm" loading={locating} onClick={locate}>
          <LocateFixed className="size-4" /> {form.pickup_latitude ? 'Mettre à jour ma position' : 'Je suis à la boutique : enregistrer ma position'}
        </Button>
        {form.pickup_latitude && <span className="text-green-700">Position GPS enregistrée : le livreur aura l'itinéraire.</span>}
      </div>
      <ErrorBox message={error} />
      <Button type="submit" loading={save.isPending}>{saved ? 'Enregistré ✓' : 'Enregistrer l’adresse'}</Button>
    </form>
  )
}

const DAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
// Heures proposées : de 6 h à 23 h, toutes les demi-heures (en minutes depuis minuit)
const TIMES = Array.from({ length: 35 }, (_, i) => 360 + i * 30)

const formatTime = (minutes: number) => `${Math.floor(minutes / 60)} h${minutes % 60 ? ` ${minutes % 60}` : ''}`

/** « Lun-Sam » pour des jours qui se suivent, sinon « Lun, Mer, Ven ». */
function formatDays(days: number[]) {
  const sorted = [...days].sort((a, b) => a - b)
  if (sorted.length === 7) return 'Tous les jours'
  const consecutive = sorted.length > 2 && sorted.every((d, i) => i === 0 || d === sorted[i - 1] + 1)
  return consecutive ? `${DAYS[sorted[0]]}-${DAYS[sorted[sorted.length - 1]]}` : sorted.map((d) => DAYS[d]).join(', ')
}

/** Relit un horaire déjà enregistré (« Lun-Sam, 9 h - 19 h », « Lun -sam 10h à 18h »…) ; sinon valeurs par défaut. */
function parseHours(text: string) {
  const lower = text.toLowerCase()
  const index = (abbr: string) => DAYS.findIndex((d) => d.toLowerCase() === abbr.slice(0, 3))
  let days = [0, 1, 2, 3, 4, 5]
  if (lower.includes('tous les jours')) days = [0, 1, 2, 3, 4, 5, 6]
  else {
    const range = lower.match(/([a-zé]{3})[a-zé]*\s*-\s*([a-zé]{3})/)
    const found = [...lower.matchAll(/\b(lun|mar|mer|jeu|ven|sam|dim)/g)].map((m) => index(m[1]))
    if (range && index(range[1]) >= 0 && index(range[2]) >= index(range[1])) {
      days = Array.from({ length: index(range[2]) - index(range[1]) + 1 }, (_, i) => index(range[1]) + i)
    } else if (found.length) days = found
  }
  const times = [...lower.matchAll(/(\d{1,2})\s*h\s*(\d{2})?/g)].map((m) => Number(m[1]) * 60 + Number(m[2] ?? 0))
  return { days, open: times[0] ?? 540, close: times[1] ?? 1140 }
}

function hoursText({ days, open, close }: ReturnType<typeof parseHours>) {
  return days.length ? `${formatDays(days)}, ${formatTime(open)} - ${formatTime(close)}` : ''
}

function HoursPicker({ value, onChange }: { value: string; onChange: (value: string) => void }) {
  const [state, setState] = useState(() => parseHours(value))
  function update(next: typeof state) {
    setState(next)
    onChange(hoursText(next))
  }
  return (
    <fieldset className="space-y-2">
      <legend className="text-sm font-semibold">Horaires de retrait</legend>
      <div className="flex flex-wrap gap-1.5">
        {DAYS.map((day, i) => {
          const on = state.days.includes(i)
          return (
            <button
              key={day}
              type="button"
              aria-pressed={on}
              onClick={() => update({ ...state, days: on ? state.days.filter((d) => d !== i) : [...state.days, i] })}
              className={`w-12 rounded-full py-1.5 text-sm font-semibold ring-1 transition ${on ? 'bg-ink text-white ring-ink' : 'ring-ink/15 hover:ring-ink/30'}`}
            >
              {day}
            </button>
          )
        })}
      </div>
      <div className="flex items-center gap-2 text-sm">
        <span>De</span>
        <select value={state.open} onChange={(e) => update({ ...state, open: Number(e.target.value), close: Math.max(state.close, Number(e.target.value) + 30) })} className="rounded-xl border border-ink/15 bg-surface px-3 py-2">
          {TIMES.slice(0, -1).map((t) => <option key={t} value={t}>{formatTime(t)}</option>)}
        </select>
        <span>à</span>
        <select value={state.close} onChange={(e) => update({ ...state, close: Number(e.target.value) })} className="rounded-xl border border-ink/15 bg-surface px-3 py-2">
          {TIMES.filter((t) => t > state.open).map((t) => <option key={t} value={t}>{formatTime(t)}</option>)}
        </select>
      </div>
      <p className="text-xs text-ink/60">
        {state.days.length ? <>Le livreur verra : <strong>{formatDays(state.days)}, {formatTime(state.open)} - {formatTime(state.close)}</strong></> : 'Choisissez au moins un jour.'}
      </p>
    </fieldset>
  )
}
