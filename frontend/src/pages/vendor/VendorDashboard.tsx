import { useQuery } from '@tanstack/react-query'
import { BadgeCheck, CircleDot, Eye, ShoppingBag, TrendingUp, Wallet } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'

import Price from '../../components/Price'
import { Spinner } from '../../components/ui'
import { api } from '../../lib/api'
import type { Paginated, VendorSideOrder, VendorStats, VendorStatut } from '../../lib/types'
import { useMyShop } from './useMyShop'

export default function VendorDashboard() {
  const [days, setDays] = useState(30)
  const { data: shop } = useMyShop()
  const { data: stats, isLoading } = useQuery({
    queryKey: ['vendor-stats', days],
    queryFn: async () => (await api.get<VendorStats>('/vendor/stats/', { params: { days } })).data,
  })
  const { data: pending } = useQuery({
    queryKey: ['vendor-orders', 'pending'],
    queryFn: async () => (await api.get<Paginated<VendorSideOrder>>('/vendor/orders/', { params: { status: 'pending' } })).data,
  })
  const { data: statuts } = useQuery({
    queryKey: ['vendor-statuts'],
    queryFn: async () => (await api.get<Paginated<VendorStatut>>('/vendor/statuts/')).data,
  })
  const activeStatuts = statuts?.results.filter((s) => s.is_active).length ?? 0

  if (isLoading || !stats) return <Spinner />

  return (
    <div className="space-y-6">
      {(pending?.count ?? 0) > 0 && (
        <Link to="/vendeur/commandes?statut=pending" className="flex items-center gap-3 rounded-2xl bg-primary p-4 font-semibold">
          <ShoppingBag className="size-6" /> {pending!.count} commande(s) à confirmer →
        </Link>
      )}

      <div className="flex items-center gap-2">
        {[7, 30, 90].map((d) => (
          <button key={d} onClick={() => setDays(d)} className={`rounded-full px-3 py-1.5 text-sm ${days === d ? 'bg-ink text-white' : 'bg-surface ring-1 ring-ink/10'}`}>
            {d} jours
          </button>
        ))}
      </div>

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <Tile icon={<TrendingUp />} label="Ventes" value={<Price amount={stats.revenue} />} />
        <Tile icon={<ShoppingBag />} label="Commandes" value={stats.orders} />
        <Tile icon={<Eye />} label="Vues produits" value={stats.views} sub={stats.conversion !== null ? `${stats.conversion} % de conversion` : undefined} />
        <Tile icon={<Wallet />} label="Portefeuille" value={<Price amount={stats.balance} />} to="/vendeur/portefeuille" />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <section className="card p-5">
          <h2 className="mb-3 font-semibold">Produits les plus vendus</h2>
          {stats.top_products.length ? (
            <table className="w-full text-sm">
              <thead className="text-left text-ink/60">
                <tr><th className="pb-2 font-medium">Produit</th><th className="pb-2 text-right font-medium">Vendus</th><th className="pb-2 text-right font-medium">Montant</th></tr>
              </thead>
              <tbody className="divide-y divide-ink/5">
                {stats.top_products.map((p) => (
                  <tr key={p.product_name}>
                    <td className="py-2">{p.product_name}</td>
                    <td className="py-2 text-right tabular-nums">{p.sold}</td>
                    <td className="py-2 text-right tabular-nums"><Price amount={p.revenue} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <p className="text-sm text-ink/60">Pas encore de ventes sur la période.</p>
          )}
        </section>

        <section className="card p-5">
          <h2 className="mb-3 font-semibold">Ventes par jour</h2>
          {stats.by_day.length ? (
            <table className="w-full text-sm">
              <tbody className="divide-y divide-ink/5">
                {[...stats.by_day].reverse().slice(0, 10).map((d) => (
                  <tr key={d.day}>
                    <td className="py-2">{new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(d.day))}</td>
                    <td className="py-2 text-right tabular-nums">{d.orders} cmd</td>
                    <td className="py-2 text-right tabular-nums"><Price amount={d.revenue} /></td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <p className="text-sm text-ink/60">Aucune vente sur la période.</p>
          )}
        </section>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        {activeStatuts === 0 && (
          <Link to="/vendeur/statuts" className="block rounded-2xl bg-linear-to-r from-primary to-secondary p-5">
            <p className="flex items-center gap-2 font-bold"><CircleDot className="size-5" /> Publiez un statut aujourd'hui</p>
            <p className="text-sm text-ink/80">Vos statuts apparaissent en haut de l'accueil pendant 24 h.</p>
          </Link>
        )}
        {shop && !shop.is_verified && (
          <Link to="/vendeur/boutique" className="block rounded-2xl bg-surface p-5 ring-1 ring-primary-dark">
            <p className="flex items-center gap-2 font-bold"><BadgeCheck className="size-5 text-primary-dark" /> Obtenez le badge « Vendeur vérifié »</p>
            <p className="text-sm text-ink/70">Les clients achètent davantage auprès des boutiques vérifiées.</p>
          </Link>
        )}
      </div>
    </div>
  )
}

function Tile({ icon, label, value, sub, to }: { icon: ReactNode; label: string; value: ReactNode; sub?: string; to?: string }) {
  const body = (
    <>
      <span className="text-primary-dark [&>svg]:size-5">{icon}</span>
      <p className="mt-2 text-2xl font-extrabold tabular-nums">{value}</p>
      <p className="text-sm text-ink/60">{label}</p>
      {sub && <p className="text-xs text-ink/50">{sub}</p>}
    </>
  )
  const className = 'card p-4'
  return to ? <Link to={to} className={`${className} hover:ring-primary`}>{body}</Link> : <div className={className}>{body}</div>
}
