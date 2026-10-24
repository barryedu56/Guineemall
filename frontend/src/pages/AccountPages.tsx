import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Gift, Heart, KeyRound, LogOut, MapPin, MessageCircle, Package, Pencil, Store, Trash2, Wallet } from 'lucide-react'
import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import AddressForm from '../components/AddressForm'
import { CurrencySwitcher } from '../components/Layout'
import { ProductGrid } from '../components/ProductCard'
import { Badge, Button, Empty, ErrorBox, Input, PageHeader, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
import { useToast } from '../context/toast'
import { api, errorMessage } from '../lib/api'
import type { Address, Paginated, ProductSummary, User } from '../lib/types'

export function AccountPage() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ name: user?.name ?? '', phone: user?.phone ?? '' })
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [showAddressForm, setShowAddressForm] = useState(false)
  const [editing, setEditing] = useState<Address | null>(null)

  const { data: addresses = [] } = useQuery({
    queryKey: ['addresses'],
    queryFn: async () => (await api.get<Address[]>('/auth/addresses/')).data,
  })

  const saveProfile = useMutation({
    mutationFn: async () => (await api.patch<User>('/auth/me/', form)).data,
    onSuccess: (me) => {
      queryClient.setQueryData(['me'], me)
      setSaved(true)
      setTimeout(() => setSaved(false), 2000)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const deleteAddress = useMutation({
    mutationFn: (id: number) => api.delete(`/auth/addresses/${id}/`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['addresses'] }),
  })
  const makeDefault = useMutation({
    mutationFn: (id: number) => api.patch(`/auth/addresses/${id}/`, { is_default: true }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['addresses'] }),
  })

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    saveProfile.mutate()
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <PageHeader title={`Bonjour ${user?.name.split(' ')[0] ?? ''} 👋`} subtitle={user?.email} />

      <nav className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <QuickLink to="/commandes" icon={<Package />} label="Mes commandes" />
        <QuickLink to="/favoris" icon={<Heart />} label="Mes favoris" />
        <QuickLink to="/messages" icon={<MessageCircle />} label="Messages" />
        <QuickLink to="/parrainage" icon={<Gift />} label="Inviter des amis" />
        <QuickLink to="/portefeuille" icon={<Wallet />} label="Portefeuille" />
        {user?.role === 'vendeur' && <QuickLink to="/vendeur" icon={<Store />} label="Ma boutique" />}
      </nav>

      <section className="card p-6">
        <h2 className="mb-4 text-lg font-semibold">Mes informations</h2>
        <form onSubmit={submit} className="space-y-3">
          <Input label="Email" value={user?.email ?? ''} disabled />
          <Input label="Nom complet" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
          <Input label="Téléphone" type="tel" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} />
          <ErrorBox message={error} />
          <Button type="submit" loading={saveProfile.isPending}>
            {saved ? 'Enregistré ✓' : 'Enregistrer'}
          </Button>
        </form>
      </section>

      <section className="card p-6">
        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
          <MapPin className="size-5 text-primary-dark" /> Mes adresses
        </h2>
        <ul className="space-y-2">
          {addresses.map((a) =>
            editing?.id === a.id ? (
              <li key={a.id} className="rounded-2xl bg-background p-4">
                <AddressForm address={a} onSaved={() => setEditing(null)} onCancel={() => setEditing(null)} />
              </li>
            ) : (
              <li key={a.id} className="flex items-start gap-3 rounded-2xl p-4 ring-1 ring-ink/10">
                <div className="flex-1">
                  <p className="font-semibold">
                    {a.label} · {a.quartier}, {a.commune || a.city} {a.is_default && <Badge tone="brand">Par défaut</Badge>}
                  </p>
                  <p className="text-sm text-ink/60">{a.landmark}</p>
                  <p className="text-sm text-ink/60">{a.contact_phone}</p>
                  {!a.is_default && (
                    <button onClick={() => makeDefault.mutate(a.id)} className="mt-1 text-sm font-semibold text-primary-dark hover:underline">
                      Utiliser par défaut
                    </button>
                  )}
                </div>
                <button onClick={() => setEditing(a)} className="rounded-full p-2 text-ink/50 hover:bg-ink/5" aria-label="Modifier">
                  <Pencil className="size-4" />
                </button>
                <button onClick={() => window.confirm('Supprimer cette adresse ?') && deleteAddress.mutate(a.id)} className="rounded-full p-2 text-ink/40 hover:bg-red-50 hover:text-danger" aria-label="Supprimer">
                  <Trash2 className="size-4" />
                </button>
              </li>
            ),
          )}
        </ul>
        {showAddressForm ? (
          <div className="mt-4">
            <AddressForm onSaved={() => setShowAddressForm(false)} />
          </div>
        ) : (
          <Button variant="ghost" className="mt-2" onClick={() => setShowAddressForm(true)}>
            + Ajouter une adresse
          </Button>
        )}
      </section>

      <ChangePassword />

      <section className="card p-6">
        <h2 className="mb-2 font-semibold">Vous êtes à l'étranger ?</h2>
        <CurrencySwitcher />
      </section>

      <Button
        variant="outline"
        className="w-full"
        onClick={() => {
          logout()
          navigate('/')
        }}
      >
        <LogOut className="size-5" /> Se déconnecter
      </Button>
    </div>
  )
}

function ChangePassword() {
  const toast = useToast()
  const [form, setForm] = useState({ current_password: '', new_password: '' })
  const change = useMutation({
    mutationFn: () => api.post('/auth/password/change/', form),
    onSuccess: () => {
      setForm({ current_password: '', new_password: '' })
      toast('Mot de passe modifié')
    },
  })
  return (
    <section className="card p-6">
      <h2 className="mb-4 flex items-center gap-2 text-lg font-bold"><KeyRound className="size-5 text-primary-dark" /> Mot de passe</h2>
      <form onSubmit={(e) => { e.preventDefault(); change.mutate() }} className="grid gap-4 sm:grid-cols-2">
        <Input label="Mot de passe actuel" type="password" value={form.current_password} onChange={(e) => setForm({ ...form, current_password: e.target.value })} required autoComplete="current-password" />
        <Input label="Nouveau mot de passe" type="password" value={form.new_password} onChange={(e) => setForm({ ...form, new_password: e.target.value })} required minLength={8} autoComplete="new-password" />
        <div className="sm:col-span-2">
          <ErrorBox message={change.error ? errorMessage(change.error) : null} />
        </div>
        <Button type="submit" variant="outline" loading={change.isPending} className="w-fit">Changer le mot de passe</Button>
      </form>
    </section>
  )
}

function QuickLink({ to, icon, label }: { to: string; icon: ReactNode; label: string }) {
  return (
    <Link to={to} className="card card-hover flex flex-col items-center gap-2 p-5 text-center text-sm font-semibold [&>svg]:size-7 [&>svg]:text-primary-dark">
      {icon}
      {label}
    </Link>
  )
}

export function FavoritesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/favorites/', { params: { page_size: 100 } })).data,
  })

  if (isLoading) return <Spinner />
  if (!data?.results.length) {
    return (
      <Empty icon={<Heart />} title="Aucun favori">
        <p className="text-ink/60">Touchez le cœur sur une fiche produit pour la retrouver ici.</p>
      </Empty>
    )
  }
  return (
    <>
      <PageHeader title="Mes favoris" subtitle={`${data.count} produit${data.count > 1 ? 's' : ''}`} />
      <ProductGrid products={data.results} />
    </>
  )
}
