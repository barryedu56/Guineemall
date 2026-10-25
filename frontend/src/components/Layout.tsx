import {
  ArrowLeft,
  Bike,
  Heart,
  Home,
  LayoutGrid,
  LogOut,
  Mail,
  MapPin,
  MessageCircle,
  Package,
  Phone,
  Search,
  Settings,
  ShoppingBag,
  Store,
  User,
} from 'lucide-react'
import { useEffect, useState, type FormEvent, type ReactNode } from 'react'
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'

import { useAuth } from '../context/auth'
import { useCart } from '../context/cart'
import { setCurrency, useCurrency, useSiteConfig } from '../context/settings'
import { formatPhone } from '../lib/api'
import type { Currency } from '../lib/prefs'
import { useCategories, useLives } from '../lib/queries'
import { ROLE_HOME } from '../lib/roles'
import { useConversations } from '../pages/chat/useConversations'
import NotificationBell from './NotificationBell'
import VoiceButton from './VoiceButton'

export default function Layout() {
  const { data: config } = useSiteConfig()
  const { user } = useAuth()
  const { pathname } = useLocation()

  // Titre de l'onglet tiré des réglages du site
  useEffect(() => {
    if (config) document.title = `${config.site.name} · ${config.site.tagline}`
  }, [config])

  // Retour en haut de page à chaque navigation
  useEffect(() => {
    window.scrollTo({ top: 0 })
  }, [pathname])

  return (
    <div className="flex min-h-dvh flex-col pb-16 md:pb-0">
      {config?.site.announcement && (
        <div className="bg-ink px-4 py-2 text-center text-xs font-medium text-white sm:text-sm">{config.site.announcement}</div>
      )}
      <Header />
      <main className="mx-auto w-full max-w-7xl flex-1 animate-fade-in px-4 py-6 sm:py-8">
        <BackButton />
        <Outlet />
      </main>
      {/* Pied de page uniquement sur l'accueil des visiteurs (jamais une fois connecté) */}
      {pathname === '/' && !user && <Footer />}
      <MobileNav />
    </div>
  )
}

// Page parente quand on arrive directement par un lien (pas de page précédente sur le site)
const PARENTS: [RegExp, string][] = [
  [/^\/produit\//, '/produits'],
  [/^\/boutique\//, '/produits'],
  [/^\/commandes\/.+/, '/commandes'],
  [/^\/messages\/.+/, '/messages'],
  [/^\/lives\/.+/, '/lives'],
  [/^\/vendeur\/messages\/.+/, '/vendeur/messages'],
  [/^\/vendeur\/commandes\/.+/, '/vendeur/commandes'],
  [/^\/livreur\/.+/, '/livreur'],
  [/^\/vendeur\/.+/, '/vendeur'],
  [/^\/administration\/commandes\/.+/, '/administration/commandes'],
  [/^\/administration\/.+/, '/administration'],
  [/^\/paiement\//, '/commandes'],
  [/^\/mot-de-passe\//, '/connexion'],
]

function parentOf(pathname: string, home: string) {
  return PARENTS.find(([pattern]) => pattern.test(pathname))?.[1] ?? home
}

/**
 * Bouton « Retour » en haut de chaque page, sauf sur l'accueil (public ou de l'espace du rôle).
 * Revient à la page précédente du site ; si on est arrivé directement par un lien, remonte d'un niveau.
 */
function BackButton() {
  const navigate = useNavigate()
  const { user } = useAuth()
  const { pathname } = useLocation()
  const home = user ? ROLE_HOME[user.role] : '/'
  if (pathname === '/' || pathname === home) return null

  function back() {
    // react-router garde la position dans l'historique : idx > 0 = une page du site avant celle-ci
    if ((window.history.state?.idx ?? 0) > 0) navigate(-1)
    else navigate(parentOf(pathname, home))
  }

  return (
    <button onClick={back} className="mb-4 inline-flex items-center gap-1.5 rounded-full py-1.5 pl-2 pr-4 text-sm font-semibold text-ink/70 transition hover:bg-ink/5 hover:text-ink">
      <ArrowLeft className="size-4" /> Retour
    </button>
  )
}

/** Nom du site en deux couleurs : « Guinée » + « Mall » (coupé avant la dernière majuscule). */
export function Logo({ className = '' }: { className?: string }) {
  const { data: config } = useSiteConfig()
  const name = config?.site.name ?? ''
  const split = Math.max(0, name.search(/[A-Z][a-z]*$/))
  return (
    <Link to="/" className={`flex shrink-0 items-center gap-2 text-2xl font-extrabold tracking-tight ${className}`}>
      <span className="flex size-9 items-center justify-center rounded-xl bg-primary text-base font-black text-ink shadow-sm">
        <ShoppingBag className="size-5" />
      </span>
      <span>
        {name.slice(0, split)}
        <span className="text-primary-dark">{name.slice(split)}</span>
      </span>
    </Link>
  )
}

function SearchBar({ className = '' }: { className?: string }) {
  const navigate = useNavigate()
  const [q, setQ] = useState('')
  function search(text: string) {
    navigate(`/produits?search=${encodeURIComponent(text.trim())}`)
  }
  function submit(e: FormEvent) {
    e.preventDefault()
    search(q)
  }
  return (
    <form onSubmit={submit} className={`relative ${className}`} role="search">
      <Search className="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink/40" />
      <input
        value={q}
        onChange={(e) => setQ(e.target.value)}
        placeholder="Rechercher un produit, une boutique…"
        aria-label="Rechercher"
        className="w-full rounded-full border border-transparent bg-background py-3 pl-12 pr-12 outline-none transition focus:border-primary-dark focus:bg-surface focus:ring-4 focus:ring-primary/25"
      />
      <VoiceButton
        onResult={(text) => {
          setQ(text)
          search(text)
        }}
      />
    </form>
  )
}

function CountBadge({ count, tone = 'brand' }: { count: number; tone?: 'brand' | 'alert' }) {
  if (count <= 0) return null
  return (
    <span
      className={`absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full px-1 text-[11px] font-bold ring-2 ring-surface ${tone === 'brand' ? 'bg-primary-dark text-white' : 'bg-danger text-white'}`}
    >
      {count > 99 ? '99+' : count}
    </span>
  )
}

const iconButton = 'relative rounded-full p-2.5 transition hover:bg-ink/5'

function CartLink() {
  const { count } = useCart()
  return (
    <Link to="/panier" className={iconButton} aria-label="Panier">
      <ShoppingBag className="size-6" />
      <CountBadge count={count} />
    </Link>
  )
}

function MessagesLink() {
  const { user } = useAuth()
  const { data: conversations = [] } = useConversations()
  const unread = conversations.reduce((n, c) => n + c.unread, 0)
  return (
    <Link to={user?.role === 'vendeur' ? '/vendeur/messages' : '/messages'} className={iconButton} aria-label="Messages">
      <MessageCircle className="size-6" />
      <CountBadge count={unread} tone="alert" />
    </Link>
  )
}

/** Lien vers l'espace propre au rôle (boutique, courses, administration). */
function RoleLink() {
  const { user } = useAuth()
  if (!user) return null
  const links = {
    vendeur: { to: '/vendeur', label: 'Ma boutique', icon: Store },
    livreur: { to: '/livreur', label: 'Mes courses', icon: Bike },
    admin: { to: '/administration', label: 'Administration', icon: Settings },
    client: null,
  } as const
  const link = links[user.role]
  if (!link) return null
  const Icon = link.icon
  const className = 'hidden items-center gap-2 rounded-full bg-primary-light px-4 py-2 text-sm font-semibold text-ink transition hover:bg-primary lg:flex'
  return <Link to={link.to} className={className}><Icon className="size-4" /> {link.label}</Link>
}

function Header() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  return (
    <header className="sticky top-0 z-40 border-b border-ink/5 bg-surface/90 backdrop-blur-xl">
      <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
        <Logo />
        <SearchBar className="mx-4 hidden flex-1 md:block" />
        <nav className="ml-auto flex items-center gap-0.5">
          <RoleLink />
          {user && (
            <Link to="/favoris" className={`${iconButton} hidden md:block`} aria-label="Favoris">
              <Heart className="size-6" />
            </Link>
          )}
          {user && <MessagesLink />}
          {user && <NotificationBell />}
          <CartLink />
          {user ? (
            <div className="ml-1 hidden items-center gap-1 md:flex">
              <Link to="/compte" className="flex items-center gap-2 rounded-full py-1.5 pl-1.5 pr-3 transition hover:bg-ink/5">
                <Avatar name={user.name} />
                <span className="text-sm font-semibold">{user.name.split(' ')[0]}</span>
              </Link>
              <button
                onClick={() => {
                  logout()
                  navigate('/')
                }}
                className={iconButton}
                aria-label="Se déconnecter"
                title="Se déconnecter"
              >
                <LogOut className="size-5" />
              </button>
            </div>
          ) : (
            <Link to="/connexion" className="ml-2 rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ink/90">
              Connexion
            </Link>
          )}
        </nav>
      </div>
      <div className="px-4 pb-3 md:hidden">
        <SearchBar />
      </div>
      <CategoryBar />
    </header>
  )
}

export function Avatar({ name, className = 'size-8 text-xs' }: { name: string; className?: string }) {
  const initials = name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()
  return <span className={`flex shrink-0 items-center justify-center rounded-full bg-ink font-bold text-primary ${className}`}>{initials}</span>
}

/** Catégories (depuis l'API) en barre défilante sous l'en-tête. */
function CategoryBar() {
  const { data: config } = useSiteConfig()
  const { data: categories = [] } = useCategories()
  const { data: lives = [] } = useLives()
  const { pathname, search } = useLocation()
  const active = pathname === '/produits' ? new URLSearchParams(search).get('category') : null
  if (!categories.length) return null
  return (
    <nav className="no-scrollbar mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 pb-2">
      {!!config?.products_on_sale && (
        <CategoryLink to="/produits?on_sale=true" active={pathname === '/produits' && search.includes('on_sale')}>🔥 Promos</CategoryLink>
      )}
      {categories.filter((c) => c.products_count > 0).map((c) => (
        <CategoryLink key={c.slug} to={`/produits?category=${c.slug}`} active={active === c.slug}>
          {c.name}
        </CategoryLink>
      ))}
      {lives.length > 0 && <CategoryLink to="/lives" active={pathname.startsWith('/lives')}>🔴 Directs</CategoryLink>}
    </nav>
  )
}

function CategoryLink({ to, active, children }: { to: string; active: boolean; children: ReactNode }) {
  return (
    <Link
      to={to}
      className={`shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium transition ${active ? 'bg-ink text-white' : 'text-ink/70 hover:bg-ink/5 hover:text-ink'}`}
    >
      {children}
    </Link>
  )
}

function MobileNav() {
  const { user } = useAuth()
  const { count } = useCart()
  const items = [
    { to: '/', label: 'Accueil', icon: Home, badge: 0 },
    { to: '/produits', label: 'Explorer', icon: LayoutGrid, badge: 0 },
    { to: '/panier', label: 'Panier', icon: ShoppingBag, badge: count },
    user?.role === 'vendeur'
      ? { to: '/vendeur', label: 'Boutique', icon: Store, badge: 0 }
      : user?.role === 'livreur'
        ? { to: '/livreur', label: 'Courses', icon: Bike, badge: 0 }
        : user?.role === 'admin'
          ? { to: '/administration', label: 'Admin', icon: Settings, badge: 0 }
          : { to: '/commandes', label: 'Commandes', icon: Package, badge: 0 },
    { to: user ? '/compte' : '/connexion', label: 'Compte', icon: User, badge: 0 },
  ]
  return (
    <nav className="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-ink/5 bg-surface/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl md:hidden">
      {items.map(({ to, label, icon: Icon, badge }) => (
        <NavLink
          key={to}
          to={to}
          end={to === '/'}
          className={({ isActive }) =>
            `flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium transition ${isActive ? 'text-ink' : 'text-ink/45'}`
          }
        >
          {({ isActive }) => (
            <>
              <span className={`relative rounded-full px-4 py-1 transition ${isActive ? 'bg-primary' : ''}`}>
                <Icon className="size-5" />
                <CountBadge count={badge} />
              </span>
              {label}
            </>
          )}
        </NavLink>
      ))}
    </nav>
  )
}

export function CurrencySwitcher() {
  const currency = useCurrency()
  return (
    <label className="flex items-center gap-2 text-sm">
      Afficher aussi les prix en
      <select
        value={currency}
        onChange={(e) => setCurrency(e.target.value as Currency)}
        className="rounded-full border border-current/20 bg-transparent px-3 py-1.5"
      >
        {/* Couleurs explicites : la liste déroulante du système est toujours claire, même sur fond sombre */}
        <option value="GNF" className="bg-surface text-ink">GNF uniquement</option>
        <option value="EUR" className="bg-surface text-ink">Euros (€)</option>
        <option value="USD" className="bg-surface text-ink">Dollars ($)</option>
      </select>
    </label>
  )
}

const YEAR = new Date().getFullYear()

const SOCIAL_LABELS = { facebook: 'Facebook', instagram: 'Instagram', tiktok: 'TikTok' } as const

function Footer() {
  const { data: config } = useSiteConfig()
  const { data: categories = [] } = useCategories()
  const { data: lives = [] } = useLives()
  const site = config?.site
  const digits = site?.contact_whatsapp?.replace(/\D/g, '') ?? ''
  const whatsapp = digits ? digits.replace(/^(?!224)/, '224') : null

  return (
    <footer className="mt-16 bg-ink text-white/70">
      <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
        <div className="space-y-3">
          <div className="text-white"><Logo /></div>
          <p className="text-sm">{site?.tagline}</p>
          {config && config.payment_methods.length > 0 && (
            <div className="flex flex-wrap gap-2 pt-2">
              {config.payment_methods.map((m) => (
                <span key={m.value} className="rounded-lg bg-white/10 px-2.5 py-1 text-xs font-medium text-white">{m.label}</span>
              ))}
            </div>
          )}
        </div>

        <FooterColumn title="Acheter">
          {categories.filter((c) => c.products_count > 0).slice(0, 5).map((c) => (
            <Link key={c.slug} to={`/produits?category=${c.slug}`} className="hover:text-white">{c.name}</Link>
          ))}
          {!!config?.products_on_sale && <Link to="/produits?on_sale=true" className="hover:text-white">Promotions</Link>}
          {lives.length > 0 && <Link to="/lives" className="hover:text-white">Ventes en direct</Link>}
        </FooterColumn>

        <FooterColumn title="Gagner avec nous">
          <Link to="/devenir-vendeur" className="hover:text-white">Ouvrir ma boutique</Link>
          <Link to="/devenir-livreur" className="hover:text-white">Devenir livreur</Link>
          <Link to="/parrainage" className="hover:text-white">Inviter des amis</Link>
        </FooterColumn>

        <FooterColumn title="Nous contacter">
          {site?.contact_phone && (
            <a href={`tel:${site.contact_phone}`} className="flex items-center gap-2 hover:text-white"><Phone className="size-4" /> {formatPhone(site.contact_phone)}</a>
          )}
          {whatsapp && (
            <a href={`https://wa.me/${whatsapp}`} target="_blank" rel="noreferrer" className="flex items-center gap-2 hover:text-white">
              <MessageCircle className="size-4" /> WhatsApp
            </a>
          )}
          {site?.contact_email && (
            <a href={`mailto:${site.contact_email}`} className="flex items-center gap-2 hover:text-white"><Mail className="size-4" /> {site.contact_email}</a>
          )}
          {site?.address && <span className="flex items-center gap-2"><MapPin className="size-4" /> {site.address}</span>}
          {site && Object.entries(site.social).length > 0 && (
            <div className="flex flex-wrap gap-3 pt-1">
              {Object.entries(site.social).map(([network, url]) => (
                <a key={network} href={url} target="_blank" rel="noreferrer" className="font-semibold text-white hover:text-primary">
                  {SOCIAL_LABELS[network as keyof typeof SOCIAL_LABELS]}
                </a>
              ))}
            </div>
          )}
        </FooterColumn>
      </div>
      <div className="border-t border-white/10">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-5 text-sm">
          <span>© {YEAR} {site?.name}</span>
          <CurrencySwitcher />
        </div>
      </div>
    </footer>
  )
}

function FooterColumn({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="space-y-3">
      <p className="font-bold text-white">{title}</p>
      <div className="flex flex-col gap-2 text-sm">{children}</div>
    </div>
  )
}
