import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Banknote, Bike, Clock, MapPin, Phone, Star, Store, Wallet } from 'lucide-react'
import { useEffect, useState, type FormEvent, type ReactNode } from 'react'
import { Link } from 'react-router-dom'

import Price from '../components/Price'
import { Button, Empty, ErrorBox, Input, PhoneInput, Select, Spinner, StatusBadge } from '../components/ui'
import { useAuth } from '../context/auth'
import { useRealtime } from '../context/realtime'
import { useSiteConfig } from '../context/settings'
import { api, errorMessage, formatPhone } from '../lib/api'
import type { RiderDelivery, RiderProfile } from '../lib/types'
import { AccountTypeSwitch, AuthLayout } from './AuthPages'

export function RiderRegisterPage() {
  const { registerRider } = useAuth()
  const { data: config } = useSiteConfig()
  const locations = config?.delivery_locations ?? []
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', city: '', vehicle_plate: '' })
  const [communes, setCommunes] = useState<string[]>([])
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })
  // Villes et communes proposées = celles où la livraison est réellement assurée
  const city = form.city || locations[0]?.city || ''
  const cityCommunes = locations.find((l) => l.city === city)?.communes ?? []

  async function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await registerRider({ ...form, city, communes: communes.join(', ') }) // <GuestOnly> redirige vers /livreur
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout title="Devenir livreur" subtitle={`Livrez à moto dans votre quartier et soyez payé à chaque course, directement dans votre portefeuille ${config?.site.name ?? ''}.`}>
      <AccountTypeSwitch />
      <ol className="mb-6 space-y-1 rounded-2xl bg-primary-light p-4 text-sm">
        <li><strong>1.</strong> Vous envoyez votre candidature.</li>
        <li><strong>2.</strong> L'équipe la vérifie et valide votre compte (vous êtes prévenu).</li>
        <li><strong>3.</strong> Vous passez « Disponible » et acceptez les courses proposées.</li>
      </ol>
      <form onSubmit={submit} className="space-y-4">
        <Input label="Nom complet" value={form.name} onChange={set('name')} required autoComplete="name" />
        <div className="grid gap-4 sm:grid-cols-2">
          <PhoneInput label="Téléphone" value={form.phone} onValueChange={(phone) => setForm({ ...form, phone })} required />
          <Input label="Email" type="email" value={form.email} onChange={set('email')} required autoComplete="email" />
        </div>
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required minLength={8} autoComplete="new-password" hint="8 caractères minimum" />
        <div className="grid gap-4 sm:grid-cols-2">
          <Select label="Ville" value={city} onChange={(e) => { setForm({ ...form, city: e.target.value }); setCommunes([]) }}>
            {locations.map((l) => <option key={l.city}>{l.city}</option>)}
          </Select>
          <Input label="Plaque de la moto" value={form.vehicle_plate} onChange={set('vehicle_plate')} placeholder="RC-1234-A" />
        </div>
        {cityCommunes.length > 0 && (
          <fieldset>
            <legend className="text-sm font-semibold">Communes où vous livrez</legend>
            <p className="mb-2 text-xs text-ink/55">Aucune cochée = toute la ville.</p>
            <div className="flex flex-wrap gap-2">
              {cityCommunes.map((c) => {
                const on = communes.includes(c)
                return (
                  <button
                    key={c}
                    type="button"
                    aria-pressed={on}
                    onClick={() => setCommunes(on ? communes.filter((x) => x !== c) : [...communes, c])}
                    className={`rounded-full px-3.5 py-1.5 text-sm font-medium ring-1 transition ${on ? 'bg-ink text-white ring-ink' : 'ring-ink/15 hover:ring-ink/30'}`}
                  >
                    {c}
                  </button>
                )
              })}
            </div>
          </fieldset>
        )}
        <ErrorBox message={error} />
        <Button type="submit" size="lg" loading={loading} className="w-full">Envoyer ma candidature</Button>
      </form>
    </AuthLayout>
  )
}

/** Partage de la position toutes les 30 s pendant une course (suivi en direct par le client). */
function useShareLocation(active: boolean) {
  const [sharing, setSharing] = useState(false)
  useEffect(() => {
    if (!active || !navigator.geolocation) return
    let last = 0
    const id = navigator.geolocation.watchPosition(
      (pos) => {
        setSharing(true)
        if (Date.now() - last < 30_000) return
        last = Date.now()
        api.post('/rider/deliveries/location/', {
          latitude: pos.coords.latitude.toFixed(6),
          longitude: pos.coords.longitude.toFixed(6),
        }).catch(() => {})
      },
      () => setSharing(false),
      { enableHighAccuracy: true },
    )
    return () => navigator.geolocation.clearWatch(id)
  }, [active])
  return sharing
}

export function RiderDashboard() {
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const { data: profile, isLoading } = useQuery({
    queryKey: ['rider-profile'],
    queryFn: async () => (await api.get<RiderProfile>('/rider/profile/')).data,
  })
  const approved = !!profile?.is_approved
  const { data: available = [] } = useQuery({
    queryKey: ['rider-available'],
    queryFn: async () => (await api.get<RiderDelivery[]>('/rider/deliveries/available/')).data,
    enabled: approved,
    refetchInterval: 20_000,
  })
  const { data: mine } = useQuery({
    queryKey: ['rider-mine'],
    queryFn: async () => (await api.get<{ results: RiderDelivery[] }>('/rider/deliveries/')).data,
    enabled: approved,
  })
  const active = mine?.results.filter((d) => d.status === 'assigned' || d.status === 'picked_up') ?? []
  const sharing = useShareLocation(active.some((d) => d.status === 'picked_up'))
  const [codes, setCodes] = useState<Record<number, string>>({})

  // Nouvelle course, désistement, annulation : la liste se met à jour dès la notification
  useRealtime((event) => {
    if (event.type === 'notification' && event.notification.kind === 'delivery') {
      queryClient.invalidateQueries({ queryKey: ['rider-available'] })
      queryClient.invalidateQueries({ queryKey: ['rider-mine'] })
    }
  })

  const updateProfile = useMutation({
    mutationFn: async (changes: Partial<RiderProfile>) => (await api.patch<RiderProfile>('/rider/profile/', changes)).data,
    onSuccess: (p) => {
      queryClient.setQueryData(['rider-profile'], p)
      queryClient.invalidateQueries({ queryKey: ['rider-available'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const act = useMutation({
    mutationFn: ({ id, step }: { id: number; step: 'accept' | 'pickup' | 'deliver' | 'release' }) =>
      api.post(`/rider/deliveries/${id}/${step}/`, step === 'deliver' ? { code: codes[id] ?? '' } : {}),
    onSuccess: () => {
      setError(null)
      queryClient.invalidateQueries({ queryKey: ['rider-available'] })
      queryClient.invalidateQueries({ queryKey: ['rider-mine'] })
      queryClient.invalidateQueries({ queryKey: ['rider-profile'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  if (isLoading) return <Spinner />
  if (!approved) {
    return (
      <div className="mx-auto flex max-w-md gap-3 rounded-2xl bg-amber-50 p-5 text-amber-900">
        <Clock className="size-6 shrink-0" />
        <p>Votre candidature est en cours de vérification. Vous serez prévenu dès que vous pourrez accepter des courses.</p>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-3xl font-extrabold">Mes courses</h1>
        <div className="flex items-center gap-4">
          {profile.rating.rating && (
            <span className="flex items-center gap-1 font-semibold"><Star className="size-5 fill-primary text-primary" /> {profile.rating.rating} ({profile.rating.ratings_count})</span>
          )}
          <Link to="/portefeuille" className="flex items-center gap-2 font-semibold text-primary-dark"><Wallet className="size-5" /> Mon portefeuille</Link>
        </div>
      </div>

      {profile.cash_debt > 0 && (
        <Link
          to="/portefeuille"
          className={`flex flex-wrap items-center gap-3 rounded-2xl p-4 font-semibold ${profile.cash_debt >= profile.cash_limit ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-900'}`}
        >
          <Banknote className="size-6" />
          <span className="flex-1">
            Espèces à reverser : <Price amount={profile.cash_debt} />
            <span className="block text-sm font-normal">
              {profile.cash_debt >= profile.cash_limit
                ? 'Plafond atteint : reversez cet argent pour reprendre des courses payées en espèces.'
                : <>Plafond : <Price amount={profile.cash_limit} />. Au-delà, plus de course payée en espèces.</>}
            </span>
          </span>
          Reverser →
        </Link>
      )}

      <section className="card flex flex-wrap items-center gap-4 p-5">
        <button
          onClick={() => updateProfile.mutate({ is_available: !profile.is_available })}
          className={`flex items-center gap-3 rounded-full px-5 py-3 font-bold transition ${profile.is_available ? 'bg-green-600 text-white' : 'bg-ink/10 text-ink/60'}`}
          aria-pressed={profile.is_available}
        >
          <span className={`size-3 rounded-full ${profile.is_available ? 'animate-pulse bg-white' : 'bg-ink/30'}`} />
          {profile.is_available ? 'Disponible' : 'Indisponible'}
        </button>
        <form
          onSubmit={(e) => {
            e.preventDefault()
            const value = new FormData(e.currentTarget).get('communes')
            updateProfile.mutate({ communes: String(value ?? '') })
          }}
          className="flex min-w-60 flex-1 gap-2"
        >
          <input
            name="communes"
            defaultValue={profile.communes}
            placeholder={`Communes desservies à ${profile.city} (vide = toutes)`}
            className="min-w-0 flex-1 rounded-full border border-ink/10 px-4 py-2.5 text-sm"
          />
          <Button type="submit" variant="outline" size="sm" loading={updateProfile.isPending}>Enregistrer</Button>
        </form>
      </section>
      <ErrorBox message={error} />

      {active.length > 0 && (
        <section className="space-y-3">
          <h2 className="font-semibold">En cours {sharing && <span className="text-sm font-normal text-green-700">· position partagée</span>}</h2>
          {active.map((d) => (
            <DeliveryCard key={d.id} delivery={d}>
              {d.status === 'assigned' ? (
                <div className="flex flex-wrap gap-2">
                  <Button loading={act.isPending} onClick={() => act.mutate({ id: d.id, step: 'pickup' })}>J'ai récupéré le colis</Button>
                  <Button variant="ghost" onClick={() => window.confirm('Rendre cette course ? Elle sera proposée aux autres livreurs.') && act.mutate({ id: d.id, step: 'release' })}>
                    Je ne peux plus la faire
                  </Button>
                </div>
              ) : (
                <form
                  onSubmit={(e) => {
                    e.preventDefault()
                    act.mutate({ id: d.id, step: 'deliver' })
                  }}
                  className="flex flex-wrap items-end gap-3 rounded-2xl bg-primary-light p-4"
                >
                  <label className="flex-1 space-y-1">
                    <span className="block text-sm font-semibold">Code donné par le client</span>
                    <span className="block text-xs text-ink/60">Remettez le colis, puis demandez son code à 4 chiffres.</span>
                    <input
                      value={codes[d.id] ?? ''}
                      onChange={(e) => setCodes({ ...codes, [d.id]: e.target.value.replace(/\D/g, '').slice(0, 4) })}
                      inputMode="numeric"
                      placeholder="••••"
                      className="w-40 rounded-2xl border border-ink/15 bg-surface px-4 py-2.5 text-center font-mono text-2xl tracking-[0.4em]"
                    />
                  </label>
                  <Button type="submit" disabled={(codes[d.id] ?? '').length !== 4} loading={act.isPending}>Colis livré</Button>
                </form>
              )}
            </DeliveryCard>
          ))}
        </section>
      )}

      <section className="space-y-3">
        <h2 className="font-semibold">Courses disponibles à {profile.city}</h2>
        {!profile.is_available ? (
          <p className="text-sm text-ink/60">Passez « Disponible » pour voir les courses.</p>
        ) : available.length ? (
          available.map((d) => (
            <DeliveryCard key={d.id} delivery={d}>
              {d.cash_blocked ? (
                <p className="text-sm font-medium text-red-700">Reversez d'abord vos espèces pour prendre cette course payée à la livraison.</p>
              ) : (
                <Button loading={act.isPending} onClick={() => act.mutate({ id: d.id, step: 'accept' })}>Accepter la course</Button>
              )}
            </DeliveryCard>
          ))
        ) : (
          <Empty icon={<Bike />} title="Aucune course pour le moment">
            <p className="text-sm text-ink/60">La liste se met à jour automatiquement.</p>
          </Empty>
        )}
      </section>
    </div>
  )
}

function DeliveryCard({ delivery: d, children }: { delivery: RiderDelivery; children: ReactNode }) {
  const map = d.dropoff.latitude ? `https://www.google.com/maps/dir/?api=1&destination=${d.dropoff.latitude},${d.dropoff.longitude}` : null
  return (
    <article className="card space-y-3 p-5">
      <div className="flex items-start justify-between gap-2">
        <p className="font-semibold">Commande #{d.order_id} · {d.items_count} article(s)</p>
        <StatusBadge status={d.status === 'picked_up' ? 'processing' : d.status === 'open' ? 'pending' : 'confirmed'} label={d.status_label} />
      </div>
      <div className="grid gap-2 text-sm sm:grid-cols-2">
        <p className="flex gap-2"><Store className="size-4 shrink-0 text-primary-dark" /> {d.pickup.shop_name} · <a href={`tel:${d.pickup.phone}`} className="text-primary-dark">{formatPhone(d.pickup.phone)}</a></p>
        <p className="flex gap-2">
          <MapPin className="size-4 shrink-0 text-primary-dark" />
          <span>
            {d.dropoff.quartier}, {d.dropoff.commune || d.dropoff.city}
            {d.dropoff.landmark && <span className="block text-ink/60">{d.dropoff.landmark}</span>}
            {map && <a href={map} target="_blank" rel="noreferrer" className="text-primary-dark underline">Itinéraire</a>}
          </span>
        </p>
        {d.dropoff.phone && (
          <a href={`tel:${d.dropoff.phone}`} className="flex gap-2 font-medium text-primary-dark"><Phone className="size-4" /> {d.dropoff.name} · {formatPhone(d.dropoff.phone)}</a>
        )}
      </div>
      <div className="flex flex-wrap items-center gap-4 rounded-xl bg-background p-3 text-sm">
        <span>Vous gagnez : <Price amount={d.fee} className="font-bold text-green-700" /></span>
        {d.cash_to_collect > 0 && (
          <span className="flex items-center gap-1"><Banknote className="size-4" /> À encaisser : <Price amount={d.cash_to_collect} className="font-bold" /></span>
        )}
      </div>
      {children}
    </article>
  )
}
