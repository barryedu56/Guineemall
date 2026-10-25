import { useQuery } from '@tanstack/react-query'
import {
  Banknote, BadgeCheck, Bike, KeyRound, ExternalLink, Package, RotateCcw, ShoppingBag, Store, TrendingUp, Truck, Users, Wallet,
} from 'lucide-react'
import type { ReactNode } from 'react'

import { Link } from 'react-router-dom'

import Price from '../components/Price'
import { PageHeader, Skeleton, StatusBadge } from '../components/ui'
import { useAuth } from '../context/auth'
import { useToast } from '../context/toast'
import { api, errorMessage, formatDate, formatNumber } from '../lib/api'

interface Dashboard {
  today: { orders: number; revenue: number }
  month: { orders: number; revenue: number; new_users: number; delivered: number }
  totals: { users: number; clients: number; shops: number; riders: number }
  todo: {
    vendors_pending: number
    verifications_pending: number
    riders_pending: number
    payouts_pending: number
    payouts_amount: number
    refunds_pending: number
    deliveries_open: number
    deposits_pending: number
    codes_blocked: number
  }
  recent_orders: { id: number; client: string; total: number; status: string; status_label: string; payment_label: string; created_at: string }[]
  top_shops: { shop_name: string; slug: string; revenue: number }[]
}

/** Ouvre une page de l'admin Django sans redemander le mot de passe (adresse secrète fournie par le serveur). */
function useOpenAdmin() {
  const toast = useToast()
  return async (path: string) => {
    // Onglet ouvert tout de suite (sinon bloqué par le navigateur), puis dirigé vers l'admin
    const tab = window.open('about:blank', '_blank')
    try {
      const { data } = await api.post<{ admin_url: string }>('/admin/session/')
      if (tab) tab.location.href = `${data.admin_url}${path}`
    } catch (e) {
      tab?.close()
      toast(errorMessage(e), 'error')
    }
  }
}

export default function AdminDashboard() {
  const { user } = useAuth()
  const openAdmin = useOpenAdmin()
  const { data, isLoading } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: async () => (await api.get<Dashboard>('/admin/dashboard/')).data,
    refetchInterval: 60_000,
  })

  if (isLoading || !data) {
    return <div className="grid gap-4 sm:grid-cols-4">{Array.from({ length: 8 }, (_, i) => <Skeleton key={i} className="h-28" />)}</div>
  }

  // Chaque tâche ouvre la rubrique du back-office, déjà filtrée sur ce qui attend une action
  const todo = [
    { count: data.todo.vendors_pending, label: 'boutique(s) à approuver', icon: Store, to: '/administration/boutiques?status=pending' },
    { count: data.todo.verifications_pending, label: 'demande(s) de badge vérifié', icon: BadgeCheck, to: '/administration/verifications?status=pending' },
    { count: data.todo.riders_pending, label: 'livreur(s) à valider', icon: Bike, to: '/administration/livreurs?is_approved=false' },
    { count: data.todo.payouts_pending, label: 'retrait(s) à payer', icon: Wallet, to: '/administration/retraits?status=pending', amount: data.todo.payouts_amount },
    { count: data.todo.refunds_pending, label: 'remboursement(s) à faire', icon: RotateCcw, to: '/administration/commandes?payment_status=refund_pending' },
    { count: data.todo.deliveries_open, label: 'course(s) sans livreur', icon: Truck, to: '/administration/commandes?status=processing' },
    { count: data.todo.deposits_pending, label: "versement(s) d'espèces à vérifier", icon: Banknote, to: '/administration/versements?status=pending' },
    { count: data.todo.codes_blocked, label: 'code(s) de livraison bloqué(s)', icon: KeyRound, to: '/administration/commandes?status=processing' },
  ]
  const pending = todo.filter((t) => t.count > 0)

  return (
    <div className="space-y-8">
      <PageHeader title={`Bonjour ${user?.name.split(' ')[0] ?? ''} 👋`} subtitle="Voici l'activité de la plateforme.">
        <button onClick={() => openAdmin('')} className="inline-flex items-center gap-2 rounded-full border border-ink/15 bg-surface px-4 py-2 text-sm font-semibold hover:bg-ink/5" title="Interface technique Django, pour les cas avancés">
          Admin technique <ExternalLink className="size-4" />
        </button>
      </PageHeader>

      <section>
        <h2 className="mb-3 text-lg font-extrabold">À traiter</h2>
        {pending.length ? (
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {pending.map(({ count, label, icon: Icon, to, amount }) => (
              <Link key={label} to={to} className="card card-hover flex items-center gap-4 p-5 text-left">
                <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-ink"><Icon className="size-6" /></span>
                <span>
                  <span className="block text-2xl font-extrabold">{formatNumber(count)}</span>
                  <span className="text-sm text-ink/60">{label}{amount ? <> · <Price amount={amount} /></> : null}</span>
                </span>
              </Link>
            ))}
          </div>
        ) : (
          <p className="card p-5 text-ink/60">Rien en attente. Tout est à jour ✅</p>
        )}
      </section>

      <section className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Tile icon={<ShoppingBag />} label="Commandes aujourd'hui" value={data.today.orders} sub={<Price amount={data.today.revenue} />} />
        <Tile icon={<TrendingUp />} label="Ventes sur 30 jours" value={<Price amount={data.month.revenue} />} sub={`${data.month.orders} commandes · ${data.month.delivered} livraisons`} />
        <Tile icon={<Users />} label="Utilisateurs" value={data.totals.users} sub={`+${data.month.new_users} ce mois-ci`} />
        <Tile icon={<Store />} label="Boutiques actives" value={data.totals.shops} sub={`${data.totals.riders} livreur(s)`} />
      </section>

      <div className="grid gap-4 lg:grid-cols-[1fr_340px]">
        <section className="card p-5">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-extrabold">Dernières commandes</h2>
            <Link to="/administration/commandes" className="text-sm font-semibold text-primary-dark hover:underline">Tout voir</Link>
          </div>
          {data.recent_orders.length ? (
            <ul className="divide-y divide-ink/5">
              {data.recent_orders.map((o) => (
                <li key={o.id}>
                  <Link to={`/administration/commandes/${o.id}`} className="flex w-full items-center gap-3 py-3 text-left hover:bg-ink/[0.02]">
                    <Package className="size-5 text-ink/40" />
                    <span className="flex-1">
                      <span className="block font-semibold">#{o.id} · {o.client}</span>
                      <span className="text-sm text-ink/55">{formatDate(o.created_at)} · {o.payment_label}</span>
                    </span>
                    <span className="text-right">
                      <Price amount={o.total} className="block font-bold" />
                      <StatusBadge status={o.status} label={o.status_label} />
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-ink/60">Aucune commande pour le moment.</p>
          )}
        </section>

        <section className="card h-fit p-5">
          <h2 className="mb-3 font-extrabold">Meilleures boutiques (30 jours)</h2>
          {data.top_shops.length ? (
            <ol className="space-y-3">
              {data.top_shops.map((s, i) => (
                <li key={s.slug} className="flex items-center gap-3">
                  <span className="flex size-7 items-center justify-center rounded-full bg-primary-light text-sm font-bold text-primary-dark">{i + 1}</span>
                  <Link to="/administration/boutiques" className="flex-1 font-semibold hover:underline">{s.shop_name}</Link>
                  <Price amount={s.revenue} className="text-sm font-bold" />
                </li>
              ))}
            </ol>
          ) : (
            <p className="text-sm text-ink/60">Pas encore de ventes.</p>
          )}
        </section>
      </div>
    </div>
  )
}

function Tile({ icon, label, value, sub }: { icon: ReactNode; label: string; value: ReactNode; sub?: ReactNode }) {
  return (
    <div className="card p-5">
      <span className="text-primary-dark [&>svg]:size-5">{icon}</span>
      <p className="mt-2 text-2xl font-extrabold tabular-nums">{typeof value === 'number' ? formatNumber(value) : value}</p>
      <p className="text-sm font-medium">{label}</p>
      {sub && <p className="text-xs text-ink/55">{sub}</p>}
    </div>
  )
}
