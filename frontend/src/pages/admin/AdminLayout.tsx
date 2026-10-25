import { useQuery } from '@tanstack/react-query'
import {
  BadgeCheck, Banknote, Bike, FolderTree, LayoutDashboard, MessageSquareQuote, Package, Radio, ScrollText, Settings,
  ShoppingBag, Store, Tag, Truck, Users, Wallet,
} from 'lucide-react'
import { NavLink, Outlet } from 'react-router-dom'

import TwoFactor from '../../components/TwoFactor'
import { PageHeader } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { api } from '../../lib/api'

type Todo = Record<string, number>

const SECTIONS: { title: string; links: { to: string; label: string; icon: typeof Store; todo?: string }[] }[] = [
  { title: '', links: [{ to: '/administration', label: 'Tableau de bord', icon: LayoutDashboard }] },
  {
    title: 'Ventes',
    links: [
      { to: '/administration/commandes', label: 'Commandes', icon: ShoppingBag, todo: 'refunds_pending' },
      { to: '/administration/retraits', label: 'Retraits', icon: Wallet, todo: 'payouts_pending' },
      { to: '/administration/versements', label: "Versements d'espèces", icon: Banknote, todo: 'deposits_pending' },
    ],
  },
  {
    title: 'Partenaires',
    links: [
      { to: '/administration/boutiques', label: 'Boutiques', icon: Store, todo: 'vendors_pending' },
      { to: '/administration/verifications', label: 'Badges vérifiés', icon: BadgeCheck, todo: 'verifications_pending' },
      { to: '/administration/livreurs', label: 'Livreurs', icon: Bike, todo: 'riders_pending' },
      { to: '/administration/utilisateurs', label: 'Utilisateurs', icon: Users },
    ],
  },
  {
    title: 'Catalogue',
    links: [
      { to: '/administration/produits', label: 'Produits', icon: Package },
      { to: '/administration/categories', label: 'Catégories', icon: FolderTree },
      { to: '/administration/avis', label: 'Avis', icon: MessageSquareQuote },
    ],
  },
  {
    title: 'Marketing',
    links: [
      { to: '/administration/codes-promo', label: 'Codes promo', icon: Tag },
      { to: '/administration/operations', label: 'Opérations', icon: Radio },
    ],
  },
  {
    title: 'Plateforme',
    links: [
      { to: '/administration/livraison', label: 'Livraison', icon: Truck },
      { to: '/administration/reglages', label: 'Réglages du site', icon: Settings },
      { to: '/administration/journal', label: "Journal d'audit", icon: ScrollText },
    ],
  },
]

/** Back-office : double authentification obligatoire, menu par rubrique avec les tâches en attente. */
export default function AdminLayout() {
  const { user } = useAuth()
  const { data: dashboard } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: async () => (await api.get<{ todo: Todo }>('/admin/dashboard/')).data,
    enabled: !!user?.has_2fa,
    refetchInterval: 60_000,
  })

  if (!user?.has_2fa) {
    return (
      <div className="mx-auto max-w-2xl space-y-4">
        <PageHeader title="Sécurisez votre compte" subtitle="L'administration donne accès à l'argent de la plateforme : la double authentification est obligatoire." />
        <TwoFactor />
      </div>
    )
  }

  const todo = dashboard?.todo ?? {}
  return (
    <div className="grid gap-6 lg:grid-cols-[230px_1fr]">
      <nav className="no-scrollbar -mx-4 flex gap-1 overflow-x-auto px-4 lg:sticky lg:top-36 lg:mx-0 lg:block lg:h-fit lg:space-y-5 lg:overflow-visible lg:px-0" aria-label="Administration">
        {SECTIONS.map((section) => (
          <div key={section.title} className="flex gap-1 lg:block lg:space-y-0.5">
            {section.title && <p className="hidden px-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-ink/40 lg:block">{section.title}</p>}
            {section.links.map(({ to, label, icon: Icon, todo: key }) => {
              const count = key ? todo[key] ?? 0 : 0
              return (
                <NavLink
                  key={to}
                  to={to}
                  end={to === '/administration'}
                  className={({ isActive }) =>
                    `flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition ${isActive ? 'bg-ink text-white' : 'text-ink/70 hover:bg-ink/5 hover:text-ink'}`
                  }
                >
                  <Icon className="size-4" />
                  <span className="whitespace-nowrap">{label}</span>
                  {count > 0 && <span className="ml-auto rounded-full bg-primary px-1.5 text-xs font-bold text-ink">{count}</span>}
                </NavLink>
              )
            })}
          </div>
        ))}
      </nav>
      <div className="min-w-0">
        <Outlet />
      </div>
    </div>
  )
}
