import { useQuery } from '@tanstack/react-query'
import { CircleDot, Package, ShoppingBag } from 'lucide-react'
import { Link } from 'react-router-dom'

import { api } from '../../lib/api'
import type { Paginated, VendorProduct, VendorSideOrder, VendorStatut } from '../../lib/types'

export default function VendorDashboard() {
  const { data: pending } = useQuery({
    queryKey: ['vendor-orders', 'pending'],
    queryFn: async () => (await api.get<Paginated<VendorSideOrder>>('/vendor/orders/', { params: { status: 'pending' } })).data,
  })
  const { data: products } = useQuery({
    queryKey: ['vendor-products'],
    queryFn: async () => (await api.get<Paginated<VendorProduct>>('/vendor/products/')).data,
  })
  const { data: statuts } = useQuery({
    queryKey: ['vendor-statuts'],
    queryFn: async () => (await api.get<Paginated<VendorStatut>>('/vendor/statuts/')).data,
  })

  const outOfStock = products?.results.filter((p) => p.stock === 0).length ?? 0
  const activeStatuts = statuts?.results.filter((s) => s.is_active).length ?? 0

  const cards = [
    { to: '/vendeur/commandes', icon: ShoppingBag, value: pending?.count ?? 0, label: 'commande(s) à confirmer', alert: (pending?.count ?? 0) > 0 },
    { to: '/vendeur/produits', icon: Package, value: products?.count ?? 0, label: `produit(s)${outOfStock ? ` · ${outOfStock} épuisé(s)` : ''}`, alert: outOfStock > 0 },
    { to: '/vendeur/statuts', icon: CircleDot, value: activeStatuts, label: 'statut(s) en ligne', alert: false },
  ]

  return (
    <div className="space-y-6">
      <div className="grid gap-3 sm:grid-cols-3">
        {cards.map(({ to, icon: Icon, value, label, alert }) => (
          <Link key={to} to={to} className={`rounded-2xl bg-surface p-5 ring-1 hover:ring-primary ${alert ? 'ring-primary-dark' : 'ring-ink/5'}`}>
            <Icon className="mb-2 size-6 text-primary-dark" />
            <p className="text-3xl font-extrabold">{value}</p>
            <p className="text-sm text-ink/60">{label}</p>
          </Link>
        ))}
      </div>
      {activeStatuts === 0 && (
        <Link to="/vendeur/statuts" className="block rounded-2xl bg-gradient-to-r from-primary to-secondary p-5">
          <p className="font-bold">Publiez un statut aujourd'hui 📸</p>
          <p className="text-sm text-ink/80">
            Montrez une nouveauté ou une promo : vos statuts apparaissent en haut de la page d'accueil pendant 24 h.
          </p>
        </Link>
      )}
    </div>
  )
}
