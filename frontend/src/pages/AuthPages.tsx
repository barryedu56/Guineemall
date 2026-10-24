import { Store } from 'lucide-react'
import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'

import { Button, ErrorBox, Input, Textarea } from '../components/ui'
import { useAuth } from '../context/auth'
import { errorMessage } from '../lib/api'
import type { User } from '../lib/types'

function useRedirectAfterAuth() {
  const navigate = useNavigate()
  const [params] = useSearchParams()
  return (user: User) => {
    const next = params.get('suivant')
    navigate(next ?? (user.role === 'vendeur' ? '/vendeur' : '/'), { replace: true })
  }
}

function AuthCard({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div className="mx-auto max-w-md rounded-3xl bg-surface p-6 shadow-sm ring-1 ring-ink/5 sm:p-8">
      <h1 className="mb-6 text-2xl font-bold">{title}</h1>
      {children}
    </div>
  )
}

export function LoginPage() {
  const { login } = useAuth()
  const redirect = useRedirectAfterAuth()
  const [params] = useSearchParams()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  async function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      redirect(await login(email, password))
    } catch (err) {
      const msg = errorMessage(err)
      setError(msg.includes('No active account') ? 'Email ou mot de passe incorrect.' : msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthCard title="Connexion">
      <form onSubmit={submit} className="space-y-4">
        <Input label="Email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" />
        <Input label="Mot de passe" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required autoComplete="current-password" />
        <ErrorBox message={error} />
        <Button type="submit" loading={loading} className="w-full">
          Se connecter
        </Button>
      </form>
      <p className="mt-6 text-center text-sm">
        Pas encore de compte ?{' '}
        <Link to={`/inscription?${params}`} className="font-semibold text-primary-dark hover:underline">
          Créer un compte
        </Link>
      </p>
    </AuthCard>
  )
}

export function RegisterPage() {
  const { register } = useAuth()
  const redirect = useRedirectAfterAuth()
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '' })
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  async function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      redirect(await register(form))
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthCard title="Créer un compte">
      <form onSubmit={submit} className="space-y-4">
        <Input label="Nom complet" value={form.name} onChange={set('name')} required autoComplete="name" />
        <Input label="Email" type="email" value={form.email} onChange={set('email')} required autoComplete="email" />
        <Input label="Téléphone" type="tel" value={form.phone} onChange={set('phone')} placeholder="620 00 00 00" autoComplete="tel" />
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required minLength={8} autoComplete="new-password" hint="8 caractères minimum" />
        <ErrorBox message={error} />
        <Button type="submit" loading={loading} className="w-full">
          Créer mon compte
        </Button>
      </form>
      <p className="mt-6 text-center text-sm">
        Déjà inscrit ?{' '}
        <Link to="/connexion" className="font-semibold text-primary-dark hover:underline">
          Se connecter
        </Link>
      </p>
      <Link to="/devenir-vendeur" className="mt-3 flex items-center justify-center gap-2 text-sm text-ink/60 hover:underline">
        <Store className="size-4" /> Vous voulez vendre ? Ouvrez votre boutique
      </Link>
    </AuthCard>
  )
}

export function VendorRegisterPage() {
  const { registerVendor } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    name: '', email: '', phone: '', password: '', shop_name: '', shop_description: '', whatsapp: '',
  })
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  async function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await registerVendor(form)
      navigate('/vendeur', { replace: true })
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthCard title="Ouvrir ma boutique">
      <p className="-mt-3 mb-6 text-ink/70">
        Vendez à toute la Guinée. Votre boutique sera visible dès qu'elle aura été validée par notre équipe.
      </p>
      <form onSubmit={submit} className="space-y-4">
        <Input label="Nom de la boutique" value={form.shop_name} onChange={set('shop_name')} required />
        <Textarea label="Description de la boutique" value={form.shop_description} onChange={set('shop_description')} placeholder="Ce que vous vendez, où vous êtes…" />
        <Input label="Numéro WhatsApp de la boutique" type="tel" value={form.whatsapp} onChange={set('whatsapp')} placeholder="620 00 00 00" hint="Les clients pourront vous écrire directement." />
        <hr className="border-ink/10" />
        <Input label="Votre nom complet" value={form.name} onChange={set('name')} required />
        <Input label="Email" type="email" value={form.email} onChange={set('email')} required />
        <Input label="Téléphone" type="tel" value={form.phone} onChange={set('phone')} />
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required minLength={8} hint="8 caractères minimum" />
        <ErrorBox message={error} />
        <Button type="submit" loading={loading} className="w-full">
          Créer ma boutique
        </Button>
      </form>
    </AuthCard>
  )
}
