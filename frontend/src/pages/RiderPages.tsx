import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Banknote, Bike, ChevronRight, Clock, MapPin, Star, Store, Wallet } from 'lucide-react'
import { useEffect, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { ContactButtons, Countdown, DeliveryChat, Timeline } from '../components/Delivery'

import Price from '../components/Price'
import { Button, Empty, ErrorBox, Input, PhoneInput, Select, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useRealtime } from '../context/realtime'
import { useSiteConfig } from '../context/settings'
import { api, errorMessage } from '../lib/api'
import type { Paginated, RiderDelivery, RiderProfile } from '../lib/types'
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
        <li><strong>3.</strong> Vous passez « Disponible » : les boutiques de votre zone vous proposent leurs courses, vous acceptez ou refusez.</li>
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

function useRiderDeliveries(state: string, enabled = true) {
  return useQuery({
    queryKey: ['rider-deliveries', state],
    queryFn: async () => (await api.get<Paginated<RiderDelivery>>('/rider/deliveries/', { params: { etat: state } })).data.results,
    enabled,
  })
}

function useDeliveryNews() {
  const queryClient = useQueryClient()
  // Proposition, retrait, expiration : la liste se met à jour dès la notification
  useRealtime((event) => {
    if (event.type === 'notification' && event.notification.kind === 'delivery') {
      queryClient.invalidateQueries({ queryKey: ['rider-deliveries'] })
      queryClient.invalidateQueries({ queryKey: ['rider-profile'] })
    }
  })
}

export function RiderDashboard() {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [error, setError] = useState<string | null>(null)
  const { data: profile, isLoading } = useQuery({
    queryKey: ['rider-profile'],
    queryFn: async () => (await api.get<RiderProfile>('/rider/profile/')).data,
  })
  const approved = !!profile?.is_approved
  const { data: offers = [] } = useRiderDeliveries('propositions', approved)
  const { data: active = [] } = useRiderDeliveries('en-cours', approved)
  const { data: done = [] } = useRiderDeliveries('terminees', approved)
  const toHand = done.filter((d) => d.cash_for_shop > 0 && !d.cash_handed_at)
  const sharing = useShareLocation(active.some((d) => d.status === 'picked_up'))
  useDeliveryNews()

  const updateProfile = useMutation({
    mutationFn: async (changes: Partial<RiderProfile>) => (await api.patch<RiderProfile>('/rider/profile/', changes)).data,
    onSuccess: (p) => queryClient.setQueryData(['rider-profile'], p),
    onError: (e) => setError(errorMessage(e)),
  })
  const answer = useMutation({
    mutationFn: ({ id, step }: { id: number; step: 'accept' | 'decline' }) => api.post(`/rider/deliveries/${id}/${step}/`),
    onSuccess: (_, { id, step }) => {
      setError(null)
      queryClient.invalidateQueries({ queryKey: ['rider-deliveries'] })
      if (step === 'accept') navigate(`/livreur/courses/${id}`)
    },
    onError: (e) => {
      setError(errorMessage(e))
      queryClient.invalidateQueries({ queryKey: ['rider-deliveries'] })
    },
  })

  if (isLoading) return <Spinner />
  if (!approved) {
    return (
      <div className="mx-auto flex max-w-md gap-3 rounded-2xl bg-amber-50 p-5 text-amber-900">
        <Clock className="size-6 shrink-0" />
        <p>Votre candidature est en cours de vérification. Vous serez prévenu dès que vous pourrez recevoir des courses.</p>
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
                ? 'Plafond atteint : les boutiques ne peuvent plus vous confier de course payée en espèces.'
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
        <p className="min-w-48 flex-1 text-sm text-ink/60">
          {profile.is_available
            ? `Les boutiques de ${profile.city}${profile.communes ? ` (${profile.communes})` : ''} peuvent vous proposer leurs courses.`
            : 'Les boutiques ne vous voient plus dans la liste des livreurs.'}
        </p>
      </section>
      <ErrorBox message={error} />

      {toHand.length > 0 && (
        <section className="space-y-2 rounded-2xl bg-amber-100 p-4 text-amber-950">
          <h2 className="flex items-center gap-2 font-bold"><Banknote className="size-5" /> Argent à remettre aux boutiques</h2>
          {toHand.map((d) => (
            <Link key={d.id} to={`/livreur/courses/${d.id}`} className="flex items-center justify-between gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm">
              <span>#{d.order_id} · {d.pickup.shop_name}</span>
              <Price amount={d.cash_for_shop} className="font-bold" />
            </Link>
          ))}
          <p className="text-xs">La boutique confirme quand elle a reçu l'argent.</p>
        </section>
      )}

      <section className="space-y-3">
        <h2 className="font-semibold">Propositions des boutiques</h2>
        {offers.length ? (
          offers.map((d) => (
            <article key={d.id} className="card space-y-3 p-5 ring-2 ring-primary">
              <div className="flex flex-wrap items-start justify-between gap-2">
                <p className="font-bold">{d.pickup.shop_name} vous propose une course</p>
                {d.offer_expires_at && (
                  <span className="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">
                    <Clock className="mr-1 inline size-4" />
                    <Countdown until={d.offer_expires_at} onEnd={() => queryClient.invalidateQueries({ queryKey: ['rider-deliveries'] })} />
                  </span>
                )}
              </div>
              <TripSummary d={d} />
              <Money d={d} />
              <div className="flex flex-wrap gap-2">
                <Button loading={answer.isPending && answer.variables?.id === d.id && answer.variables.step === 'accept'} onClick={() => answer.mutate({ id: d.id, step: 'accept' })}>
                  Accepter la course
                </Button>
                <Button variant="outline" onClick={() => answer.mutate({ id: d.id, step: 'decline' })}>Refuser</Button>
                <Link to={`/livreur/courses/${d.id}`} className="self-center text-sm font-semibold text-primary-dark hover:underline">Détails et discussion</Link>
              </div>
            </article>
          ))
        ) : (
          <Empty icon={<Bike />} title="Aucune proposition pour le moment">
            <p className="text-sm text-ink/60">
              {profile.is_available ? 'Quand une boutique vous choisit, la course apparaît ici et vous êtes prévenu.' : 'Passez « Disponible » pour recevoir des propositions.'}
            </p>
          </Empty>
        )}
      </section>

      {active.length > 0 && (
        <section className="space-y-3">
          <h2 className="font-semibold">En cours {sharing && <span className="text-sm font-normal text-green-700">· position partagée avec le client</span>}</h2>
          {active.map((d) => (
            <Link key={d.id} to={`/livreur/courses/${d.id}`} className="card card-hover flex items-center gap-4 p-4">
              <div className="min-w-0 flex-1">
                <p className="font-bold">#{d.order_id} · {d.pickup.shop_name} → {d.dropoff.quartier}, {d.dropoff.commune || d.dropoff.city}</p>
                <p className="text-sm font-semibold text-primary-dark">{d.status === 'assigned' ? 'À récupérer à la boutique' : 'À remettre au client'}</p>
              </div>
              {d.unread > 0 && <span className="rounded-full bg-primary-dark px-2 text-xs font-bold text-white">{d.unread}</span>}
              <ChevronRight className="size-5 text-ink/30" />
            </Link>
          ))}
        </section>
      )}

      {done.length > 0 && (
        <section className="space-y-2">
          <h2 className="font-semibold">Dernières courses livrées</h2>
          {done.slice(0, 5).map((d) => (
            <Link key={d.id} to={`/livreur/courses/${d.id}`} className="flex items-center justify-between rounded-2xl bg-surface px-4 py-3 text-sm shadow-sm">
              <span>#{d.order_id} · {d.pickup.shop_name} → {d.dropoff.commune || d.dropoff.city}</span>
              <Price amount={d.fee} className="font-semibold text-green-700" />
            </Link>
          ))}
        </section>
      )}
    </div>
  )
}

/** Trajet : boutique (retrait) → client (livraison). */
function TripSummary({ d }: { d: RiderDelivery }) {
  return (
    <div className="grid gap-3 text-sm sm:grid-cols-2">
      <div className="flex gap-2">
        <Store className="mt-0.5 size-4 shrink-0 text-primary-dark" />
        <span>
          <span className="block font-semibold">Retrait · {d.pickup.shop_name}</span>
          {d.pickup.quartier}, {d.pickup.commune}
          <span className="block text-ink/60">{d.pickup.landmark}</span>
          {d.pickup.hours && <span className="block text-ink/60">{d.pickup.hours}</span>}
        </span>
      </div>
      <div className="flex gap-2">
        <MapPin className="mt-0.5 size-4 shrink-0 text-primary-dark" />
        <span>
          <span className="block font-semibold">Livraison{d.dropoff.name ? ` · ${d.dropoff.name}` : ''}</span>
          {d.dropoff.quartier}, {d.dropoff.commune || d.dropoff.city}
          {d.dropoff.landmark ? <span className="block text-ink/60">{d.dropoff.landmark}</span> : <span className="block text-ink/50">Adresse exacte et téléphone visibles après acceptation</span>}
        </span>
      </div>
    </div>
  )
}

function Money({ d }: { d: RiderDelivery }) {
  return (
    <div className="flex flex-wrap items-center gap-4 rounded-xl bg-background p-3 text-sm">
      <span>Vous gagnez : <Price amount={d.fee} className="font-bold text-green-700" /></span>
      {d.cash_to_collect > 0 && (
        <>
          <span className="flex items-center gap-1"><Banknote className="size-4" /> À encaisser chez le client : <Price amount={d.cash_to_collect} className="font-bold" /></span>
          <span>À remettre à la boutique : <Price amount={d.cash_for_shop} className="font-bold" /></span>
        </>
      )}
      <span className="text-ink/60">{d.items_count} article(s)</span>
    </div>
  )
}

/** Une course : retrait, livraison, discussion avec la boutique, étapes. */
export function RiderDeliveryPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [code, setCode] = useState('')
  const key = ['rider-deliveries', 'detail', id]
  useDeliveryNews()
  const { data: d, isLoading, error: loadError } = useQuery({
    queryKey: key,
    queryFn: async () => (await api.get<RiderDelivery>(`/rider/deliveries/${id}/`)).data,
    retry: false,
  })
  useShareLocation(d?.status === 'picked_up')
  const act = useMutation({
    mutationFn: async (step: 'accept' | 'decline' | 'pickup' | 'deliver' | 'release') =>
      (await api.post<RiderDelivery | ''>(`/rider/deliveries/${id}/${step}/`, step === 'deliver' ? { code } : {})).data,
    onSuccess: (updated) => {
      setError(null)
      queryClient.invalidateQueries({ queryKey: ['rider-deliveries'] })
      queryClient.invalidateQueries({ queryKey: ['rider-profile'] })
      if (!updated) navigate('/livreur') // course refusée ou rendue
      else queryClient.setQueryData(key, updated)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  if (isLoading) return <Spinner />
  if (!d || loadError) {
    return (
      <Empty icon={<Bike />} title="Cette course ne vous est plus attribuée">
        <Link to="/livreur" className="font-semibold text-primary-dark hover:underline">Retour à mes courses</Link>
      </Empty>
    )
  }
  const pending = (step: string) => act.isPending && act.variables === step
  const ongoing = d.status === 'offered' || d.status === 'assigned' || d.status === 'picked_up'

  return (
    <div className="mx-auto grid max-w-5xl gap-5 lg:grid-cols-[1fr_320px]">
      <div className="space-y-5">
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div>
            <h1 className="text-2xl font-extrabold">Course #{d.order_id}</h1>
            <p className="text-sm text-ink/60">{d.status_label}</p>
          </div>
          {d.status === 'offered' && d.offer_expires_at && (
            <span className="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">
              Répondre avant <Countdown until={d.offer_expires_at} onEnd={() => queryClient.invalidateQueries({ queryKey: key })} />
            </span>
          )}
        </div>
        <ErrorBox message={error} />

        <section className={`card space-y-3 p-5 ${d.status === 'assigned' ? 'ring-2 ring-primary' : ''}`}>
          <h2 className="flex items-center gap-2 font-bold"><Store className="size-5 text-primary-dark" /> 1. Récupérer le colis chez {d.pickup.shop_name}</h2>
          <p className="text-sm">
            {d.pickup.quartier}, {d.pickup.commune}, {d.pickup.city}
            <span className="block text-ink/70">{d.pickup.landmark}</span>
            {d.pickup.hours && <span className="block text-ink/60">Horaires : {d.pickup.hours}</span>}
          </p>
          <ul className="text-sm text-ink/70">
            {d.items.map((i, n) => <li key={n}>{i.quantity} × {i.name}</li>)}
          </ul>
          {ongoing && (
            <ContactButtons phone={d.pickup.phone} whatsapp={d.pickup.whatsapp} message={`Bonjour, je suis le livreur pour la commande #${d.order_id}.`} latitude={d.pickup.latitude} longitude={d.pickup.longitude} />
          )}
          {d.status === 'assigned' && (
            <div className="flex flex-wrap gap-2 pt-1">
              <Button size="lg" loading={pending('pickup')} onClick={() => act.mutate('pickup')}>J'ai récupéré le colis</Button>
              <Button variant="ghost" onClick={() => window.confirm('Rendre cette course ? La boutique choisira un autre livreur.') && act.mutate('release')}>
                Je ne peux plus la faire
              </Button>
            </div>
          )}
        </section>

        <section className={`card space-y-3 p-5 ${d.status === 'picked_up' ? 'ring-2 ring-primary' : ''}`}>
          <h2 className="flex items-center gap-2 font-bold"><MapPin className="size-5 text-primary-dark" /> 2. Livrer {d.dropoff.name ?? 'le client'}</h2>
          <p className="text-sm">
            {d.dropoff.quartier}, {d.dropoff.commune || d.dropoff.city}
            {d.dropoff.landmark ? <span className="block text-ink/70">{d.dropoff.landmark}</span> : <span className="block text-ink/50">Adresse exacte et téléphone du client visibles après acceptation.</span>}
          </p>
          {d.dropoff.phone && (d.status === 'assigned' || d.status === 'picked_up') && (
            <ContactButtons phone={d.dropoff.phone} message={`Bonjour, je suis votre livreur pour la commande #${d.order_id}.`} latitude={d.dropoff.latitude} longitude={d.dropoff.longitude} />
          )}
          {d.status === 'picked_up' && (
            <form
              onSubmit={(e) => {
                e.preventDefault()
                act.mutate('deliver')
              }}
              className="flex flex-wrap items-end gap-3 rounded-2xl bg-primary-light p-4"
            >
              <label className="flex-1 space-y-1">
                <span className="block text-sm font-semibold">Code donné par le client</span>
                <span className="block text-xs text-ink/60">
                  Remettez le colis{d.cash_to_collect > 0 ? <>, encaissez <Price amount={d.cash_to_collect} /></> : ''}, puis demandez son code à 4 chiffres.
                </span>
                <input
                  value={code}
                  onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 4))}
                  inputMode="numeric"
                  placeholder="••••"
                  className="w-40 rounded-2xl border border-ink/15 bg-surface px-4 py-2.5 text-center font-mono text-2xl tracking-[0.4em]"
                />
              </label>
              <Button type="submit" disabled={code.length !== 4} loading={pending('deliver')}>Colis livré</Button>
            </form>
          )}
        </section>

        <Money d={d} />
        {d.status === 'delivered' && d.cash_for_shop > 0 && (
          <section className={`flex flex-wrap items-center gap-3 rounded-2xl p-4 text-sm ${d.cash_handed_at ? 'bg-green-50 text-green-800' : 'bg-amber-100 text-amber-950'}`}>
            <Banknote className="size-6 shrink-0" />
            <p className="flex-1">
              {d.cash_handed_at ? (
                <>{d.pickup.shop_name} a confirmé avoir reçu <Price amount={d.cash_for_shop} className="font-bold" />. Merci !</>
              ) : (
                <>
                  3. Remettez <Price amount={d.cash_for_shop} className="font-bold" /> à {d.pickup.shop_name} (vous gardez <Price amount={d.fee} />).
                  La boutique confirmera la réception.
                </>
              )}
            </p>
            {!d.cash_handed_at && <ContactButtons phone={d.pickup.phone} whatsapp={d.pickup.whatsapp} latitude={d.pickup.latitude} longitude={d.pickup.longitude} />}
          </section>
        )}

        {d.status === 'offered' && (
          <div className="flex flex-wrap gap-2">
            <Button size="lg" loading={pending('accept')} onClick={() => act.mutate('accept')}>Accepter la course</Button>
            <Button size="lg" variant="outline" loading={pending('decline')} onClick={() => act.mutate('decline')}>Refuser</Button>
          </div>
        )}
      </div>

      <aside className="space-y-5">
        <section className="card space-y-3 p-5">
          <h2 className="font-bold">Discussion avec {d.pickup.shop_name}</h2>
          <DeliveryChat deliveryId={d.id} partner={d.pickup.shop_name} closed={!ongoing} />
        </section>
        <section className="card p-5">
          <h2 className="mb-4 font-bold">Étapes</h2>
          <Timeline steps={d.timeline} />
        </section>
      </aside>
    </div>
  )
}
