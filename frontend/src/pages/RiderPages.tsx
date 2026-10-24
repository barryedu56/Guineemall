import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Banknote, Bike, Clock, MapPin, Phone, Store, Wallet } from 'lucide-react'
import { useEffect, useState, type FormEvent, type ReactNode } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import Price from '../components/Price'
import { Button, Empty, ErrorBox, Input, Spinner, StatusBadge } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { api, errorMessage } from '../lib/api'
import type { RiderDelivery, RiderProfile } from '../lib/types'

export function RiderRegisterPage() {
  const { registerRider } = useAuth()
  const { data: config } = useSiteConfig()
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', city: 'Conakry', communes: '', vehicle_plate: '' })
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  async function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await registerRider(form)
      navigate('/livreur', { replace: true })
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="card mx-auto max-w-md p-6 sm:p-10">
      <Bike className="size-10 text-primary-dark" />
      <h1 className="mt-2 text-2xl font-bold">Devenir livreur {config?.site.name}</h1>
      <p className="mb-6 mt-1 text-ink/70">Livrez à moto dans votre quartier et soyez payé à chaque course.</p>
      <form onSubmit={submit} className="space-y-4">
        <Input label="Nom complet" value={form.name} onChange={set('name')} required />
        <Input label="Téléphone" type="tel" value={form.phone} onChange={set('phone')} required />
        <Input label="Email" type="email" value={form.email} onChange={set('email')} required />
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required minLength={8} />
        <Input label="Ville" value={form.city} onChange={set('city')} required />
        <Input label="Communes desservies" value={form.communes} onChange={set('communes')} placeholder="Ratoma, Dixinn" />
        <Input label="Plaque de la moto" value={form.vehicle_plate} onChange={set('vehicle_plate')} />
        <ErrorBox message={error} />
        <Button type="submit" loading={loading} className="w-full">Envoyer ma candidature</Button>
      </form>
    </div>
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

  const updateProfile = useMutation({
    mutationFn: async (changes: Partial<RiderProfile>) => (await api.patch<RiderProfile>('/rider/profile/', changes)).data,
    onSuccess: (p) => {
      queryClient.setQueryData(['rider-profile'], p)
      queryClient.invalidateQueries({ queryKey: ['rider-available'] })
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const act = useMutation({
    mutationFn: ({ id, step }: { id: number; step: 'accept' | 'pickup' | 'deliver' }) => api.post(`/rider/deliveries/${id}/${step}/`),
    onSuccess: () => {
      setError(null)
      queryClient.invalidateQueries({ queryKey: ['rider-available'] })
      queryClient.invalidateQueries({ queryKey: ['rider-mine'] })
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
        <Link to="/portefeuille" className="flex items-center gap-2 font-semibold text-primary-dark"><Wallet className="size-5" /> Mon portefeuille</Link>
      </div>

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
                <Button loading={act.isPending} onClick={() => act.mutate({ id: d.id, step: 'pickup' })}>J'ai récupéré le colis</Button>
              ) : (
                <Button loading={act.isPending} onClick={() => window.confirm('Colis remis au client ?') && act.mutate({ id: d.id, step: 'deliver' })}>
                  Colis livré
                </Button>
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
              <Button loading={act.isPending} onClick={() => act.mutate({ id: d.id, step: 'accept' })}>Accepter la course</Button>
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
        <p className="flex gap-2"><Store className="size-4 shrink-0 text-primary-dark" /> {d.pickup.shop_name} · <a href={`tel:${d.pickup.phone}`} className="text-primary-dark">{d.pickup.phone}</a></p>
        <p className="flex gap-2">
          <MapPin className="size-4 shrink-0 text-primary-dark" />
          <span>
            {d.dropoff.quartier}, {d.dropoff.commune || d.dropoff.city}
            {d.dropoff.landmark && <span className="block text-ink/60">{d.dropoff.landmark}</span>}
            {map && <a href={map} target="_blank" rel="noreferrer" className="text-primary-dark underline">Itinéraire</a>}
          </span>
        </p>
        {d.dropoff.phone && (
          <a href={`tel:${d.dropoff.phone}`} className="flex gap-2 font-medium text-primary-dark"><Phone className="size-4" /> {d.dropoff.name} · {d.dropoff.phone}</a>
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
