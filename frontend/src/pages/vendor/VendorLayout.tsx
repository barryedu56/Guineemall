import { Clock, CircleDot, LayoutDashboard, Package, ShoppingBag, Store } from 'lucide-react'
import { NavLink, Outlet } from 'react-router-dom'

import { Spinner } from '../../components/ui'
import ShopSettingsPage from './ShopSettingsPage'
import { useMyShop } from './useMyShop'

const TABS = [
  { to: '/vendeur', label: 'Accueil', icon: LayoutDashboard, end: true },
  { to: '/vendeur/commandes', label: 'Commandes', icon: ShoppingBag },
  { to: '/vendeur/produits', label: 'Produits', icon: Package },
  { to: '/vendeur/statuts', label: 'Statuts', icon: CircleDot },
  { to: '/vendeur/boutique', label: 'Boutique', icon: Store },
]

export default function VendorLayout() {
  const { data: shop, isLoading } = useMyShop()

  if (isLoading) return <Spinner />
  if (!shop) return <p className="py-16 text-center">Boutique introuvable.</p>

  const approved = shop.status === 'approved'

  return (
    <div>
      <h1 className="mb-4 text-2xl font-bold">{shop.shop_name}</h1>

      {!approved && (
        <div className="mb-6 flex gap-3 rounded-2xl bg-amber-50 p-4 text-amber-900">
          <Clock className="size-6 shrink-0" />
          <div>
            <p className="font-semibold">
              {shop.status === 'pending' ? 'Votre boutique est en cours de validation' : 'Votre boutique est suspendue'}
            </p>
            <p className="text-sm">
              {shop.status === 'pending'
                ? "Notre équipe vérifie vos informations. En attendant, vous pouvez compléter la présentation de votre boutique."
                : "Contactez l'équipe GuinéeMall pour en savoir plus."}
            </p>
          </div>
        </div>
      )}

      <nav className="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4">
        {TABS.filter((t) => approved || t.to === '/vendeur/boutique').map(({ to, label, icon: Icon, end }) => (
          <NavLink
            key={to}
            to={to}
            end={end}
            className={({ isActive }) =>
              `flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium ${isActive ? 'bg-ink text-white' : 'bg-surface ring-1 ring-ink/10 hover:bg-ink/5'}`
            }
          >
            <Icon className="size-4" />
            {label}
          </NavLink>
        ))}
      </nav>

      {/* Tant que la boutique n'est pas approuvée, seule la page « Boutique » est utile */}
      {approved ? <Outlet /> : <ShopSettingsPage />}
    </div>
  )
}
