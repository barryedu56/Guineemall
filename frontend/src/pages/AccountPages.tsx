import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Gift, Heart, LogOut, MapPin, MessageCircle, Package, Store, Trash2, Wallet } from 'lucide-react'
import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import AddressForm from '../components/AddressForm'
import { CurrencySwitcher } from '../components/Layout'
import { ProductGrid } from '../components/ProductCard'
import { Button, Empty, ErrorBox, Input, Spinner } from '../components/ui'
import { useAuth } from '../context/auth'
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

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    saveProfile.mutate()
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <h1 className="text-2xl font-bold">Mon compte</h1>

      <nav className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <QuickLink to="/commandes" icon={<Package />} label="Mes commandes" />
        <QuickLink to="/favoris" icon={<Heart />} label="Mes favoris" />
        <QuickLink to="/messages" icon={<MessageCircle />} label="Messages" />
        <QuickLink to="/parrainage" icon={<Gift />} label="Inviter des amis" />
        <QuickLink to="/portefeuille" icon={<Wallet />} label="Portefeuille" />
        {user?.role === 'vendeur' && <QuickLink to="/vendeur" icon={<Store />} label="Ma boutique" />}
      </nav>

      <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
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

      <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold">
          <MapPin className="size-5 text-primary-dark" /> Mes adresses
        </h2>
        <ul className="space-y-2">
          {addresses.map((a) => (
            <li key={a.id} className="flex items-start gap-3 rounded-xl p-3 ring-1 ring-ink/10">
              <div className="flex-1">
                <p className="font-medium">
                  {a.label} · {a.quartier}, {a.commune || a.city} {a.is_default && <span className="text-xs text-primary-dark">(par défaut)</span>}
                </p>
                <p className="text-sm text-ink/60">{a.landmark}</p>
              </div>
              <button onClick={() => deleteAddress.mutate(a.id)} className="p-1 text-ink/40 hover:text-red-600" aria-label="Supprimer">
                <Trash2 className="size-5" />
              </button>
            </li>
          ))}
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

      <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
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

function QuickLink({ to, icon, label }: { to: string; icon: ReactNode; label: string }) {
  return (
    <Link to={to} className="flex flex-col items-center gap-2 rounded-2xl bg-surface p-4 text-center font-medium ring-1 ring-ink/5 hover:ring-primary [&>svg]:size-7 [&>svg]:text-primary-dark">
      {icon}
      {label}
    </Link>
  )
}

export function FavoritesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/favorites/')).data,
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
      <h1 className="mb-4 text-2xl font-bold">Mes favoris</h1>
      <ProductGrid products={data.results} />
    </>
  )
}
