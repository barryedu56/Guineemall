import { Heart, Home, LayoutGrid, LogOut, Package, Search, ShoppingCart, Store, User } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom'

import { useAuth } from '../context/auth'
import { useCart } from '../context/cart'

export default function Layout() {
  return (
    <div className="flex min-h-dvh flex-col pb-16 md:pb-0">
      <Header />
      <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-6">
        <Outlet />
      </main>
      <Footer />
      <MobileNav />
    </div>
  )
}

export function Logo() {
  return (
    <Link to="/" className="shrink-0 text-2xl font-extrabold tracking-tight">
      Guinée<span className="text-primary-dark">Mall</span>
    </Link>
  )
}

function SearchBar({ className = '' }: { className?: string }) {
  const navigate = useNavigate()
  const [q, setQ] = useState('')
  function submit(e: FormEvent) {
    e.preventDefault()
    navigate(`/produits?search=${encodeURIComponent(q.trim())}`)
  }
  return (
    <form onSubmit={submit} className={`relative ${className}`}>
      <Search className="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-ink/40" />
      <input
        value={q}
        onChange={(e) => setQ(e.target.value)}
        placeholder="Rechercher un produit, une boutique…"
        className="w-full rounded-full border border-ink/10 bg-background py-2.5 pl-10 pr-4 outline-none focus:border-primary-dark focus:ring-2 focus:ring-primary/40"
      />
    </form>
  )
}

function CartLink() {
  const { count } = useCart()
  return (
    <Link to="/panier" className="relative rounded-full p-2 hover:bg-ink/5" aria-label="Panier">
      <ShoppingCart className="size-6" />
      {count > 0 && (
        <span className="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-secondary-dark px-1 text-xs font-bold text-white">
          {count}
        </span>
      )}
    </Link>
  )
}

function Header() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  return (
    <header className="sticky top-0 z-40 border-b border-ink/5 bg-surface/95 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
        <Logo />
        <SearchBar className="hidden flex-1 md:block" />
        <nav className="ml-auto flex items-center gap-1">
          {user?.role === 'vendeur' && (
            <Link to="/vendeur" className="hidden items-center gap-1.5 rounded-full px-3 py-2 font-medium hover:bg-ink/5 md:flex">
              <Store className="size-5" /> Ma boutique
            </Link>
          )}
          {user && (
            <Link to="/favoris" className="hidden rounded-full p-2 hover:bg-ink/5 md:block" aria-label="Favoris">
              <Heart className="size-6" />
            </Link>
          )}
          <CartLink />
          {user ? (
            <div className="hidden items-center gap-1 md:flex">
              <Link to="/compte" className="flex items-center gap-1.5 rounded-full px-3 py-2 font-medium hover:bg-ink/5">
                <User className="size-5" /> {user.name.split(' ')[0]}
              </Link>
              <button
                onClick={() => {
                  logout()
                  navigate('/')
                }}
                className="rounded-full p-2 hover:bg-ink/5"
                aria-label="Se déconnecter"
              >
                <LogOut className="size-5" />
              </button>
            </div>
          ) : (
            <Link to="/connexion" className="ml-1 rounded-full bg-primary px-4 py-2 font-semibold hover:bg-primary-hover">
              Connexion
            </Link>
          )}
        </nav>
      </div>
      <div className="px-4 pb-3 md:hidden">
        <SearchBar />
      </div>
    </header>
  )
}

function MobileNav() {
  const { user } = useAuth()
  const items = [
    { to: '/', label: 'Accueil', icon: Home },
    { to: '/produits', label: 'Produits', icon: LayoutGrid },
    user?.role === 'vendeur'
      ? { to: '/vendeur', label: 'Boutique', icon: Store }
      : { to: '/commandes', label: 'Commandes', icon: Package },
    { to: user ? '/compte' : '/connexion', label: 'Compte', icon: User },
  ]
  return (
    <nav className="fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-ink/10 bg-surface md:hidden">
      {items.map(({ to, label, icon: Icon }) => (
        <NavLink
          key={to}
          to={to}
          end={to === '/'}
          className={({ isActive }) =>
            `flex flex-col items-center gap-0.5 py-2 text-xs ${isActive ? 'font-semibold text-primary-dark' : 'text-ink/60'}`
          }
        >
          <Icon className="size-5" />
          {label}
        </NavLink>
      ))}
    </nav>
  )
}

const YEAR = new Date().getFullYear()

function Footer() {
  return (
    <footer className="mt-12 hidden border-t border-ink/5 bg-surface md:block">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 text-sm text-ink/60">
        <span>© {YEAR} GuinéeMall · Le marché guinéen, dans votre poche.</span>
        <Link to="/devenir-vendeur" className="font-medium text-primary-dark hover:underline">
          Vendre sur GuinéeMall
        </Link>
      </div>
    </footer>
  )
}
