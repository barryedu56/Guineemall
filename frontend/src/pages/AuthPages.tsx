import { useMutation } from '@tanstack/react-query'
import { CheckCircle2, KeyRound, ShieldCheck, Smartphone, Store, Truck } from 'lucide-react'
import { useState, type FormEvent, type ReactNode } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'

import { Logo } from '../components/Layout'
import { Button, ErrorBox, Input, Textarea } from '../components/ui'
import { useAuth } from '../context/auth'
import { useSiteConfig } from '../context/settings'
import { api, errorMessage } from '../lib/api'
import { affiliateCode } from '../lib/prefs'
import type { User } from '../lib/types'

function useRedirectAfterAuth() {
  const navigate = useNavigate()
  const [params] = useSearchParams()
  return (user: User) => {
    const next = params.get('suivant')
    const home = { vendeur: '/vendeur', livreur: '/livreur', admin: '/', client: '/' }[user.role]
    navigate(next ?? home, { replace: true })
  }
}

/** Mise en page des écrans de compte : formulaire à gauche, arguments de la marque à droite. */
export function AuthLayout({ title, subtitle, children }: { title: string; subtitle?: ReactNode; children: ReactNode }) {
  const { data: config } = useSiteConfig()
  const points = [
    { icon: ShieldCheck, text: `Paiement protégé jusqu'à la réception` },
    { icon: Smartphone, text: config?.payment_methods.map((m) => m.label).join(' · ') ?? '' },
    { icon: Truck, text: 'Livraison à domicile ou en point relais' },
  ]
  return (
    <div className="card mx-auto grid max-w-5xl overflow-hidden lg:grid-cols-2">
      <div className="p-6 sm:p-10">
        <h1 className="text-3xl font-extrabold">{title}</h1>
        {subtitle && <p className="mt-2 text-ink/60">{subtitle}</p>}
        <div className="mt-8">{children}</div>
      </div>
      <div className="relative hidden flex-col justify-between overflow-hidden bg-ink p-10 text-white lg:flex">
        <div className="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-primary/30 blur-3xl" aria-hidden />
        <Logo />
        <div className="relative space-y-6">
          <p className="text-3xl font-extrabold leading-tight">{config?.site.tagline}</p>
          <ul className="space-y-4">
            {points.filter((p) => p.text).map(({ icon: Icon, text }) => (
              <li key={text} className="flex items-center gap-3 text-white/80">
                <span className="flex size-10 items-center justify-center rounded-xl bg-white/10 text-primary"><Icon className="size-5" /></span>
                {text}
              </li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  )
}

function useFormState<T extends Record<string, string>>(initial: T) {
  const [form, setForm] = useState(initial)
  const set = (key: keyof T) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })
  return [form, set] as const
}

function useSubmit(action: () => Promise<void>) {
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  async function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await action()
    } catch (err) {
      const msg = errorMessage(err)
      setError(msg.includes('No active account') ? 'Email ou mot de passe incorrect.' : msg)
    } finally {
      setLoading(false)
    }
  }
  return { submit, error, loading }
}

export function LoginPage() {
  const { login } = useAuth()
  const redirect = useRedirectAfterAuth()
  const [params] = useSearchParams()
  const [form, set] = useFormState({ email: '', password: '' })
  const { submit, error, loading } = useSubmit(async () => redirect(await login(form.email, form.password)))

  return (
    <AuthLayout title="Bon retour 👋" subtitle="Connectez-vous pour commander, suivre vos colis et discuter avec les boutiques.">
      <form onSubmit={submit} className="space-y-4">
        <Input label="Email" type="email" value={form.email} onChange={set('email')} required autoComplete="email" />
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required autoComplete="current-password" />
        <div className="text-right">
          <Link to="/mot-de-passe/oublie" className="text-sm font-semibold text-primary-dark hover:underline">Mot de passe oublié ?</Link>
        </div>
        <ErrorBox message={error} />
        <Button type="submit" size="lg" loading={loading} className="w-full">Se connecter</Button>
      </form>
      <p className="mt-8 text-center text-sm text-ink/60">
        Pas encore de compte ?{' '}
        <Link to={`/inscription?${params}`} className="font-bold text-ink hover:underline">Créer un compte</Link>
      </p>
    </AuthLayout>
  )
}

export function RegisterPage() {
  const { register } = useAuth()
  const redirect = useRedirectAfterAuth()
  const { data: config } = useSiteConfig()
  // Code parrain pré-rempli si le visiteur est arrivé par un lien ?ref=CODE
  const [form, set] = useFormState({ name: '', email: '', phone: '', password: '', referral: affiliateCode() })
  const { submit, error, loading } = useSubmit(async () => redirect(await register(form)))

  return (
    <AuthLayout title="Créer un compte" subtitle="Quelques secondes suffisent.">
      <form onSubmit={submit} className="space-y-4">
        <Input label="Nom complet" value={form.name} onChange={set('name')} required autoComplete="name" />
        <div className="grid gap-4 sm:grid-cols-2">
          <Input label="Email" type="email" value={form.email} onChange={set('email')} required autoComplete="email" />
          <Input label="Téléphone" type="tel" value={form.phone} onChange={set('phone')} placeholder="620 00 00 00" autoComplete="tel" />
        </div>
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required minLength={8} autoComplete="new-password" hint="8 caractères minimum" />
        <Input
          label="Code parrain (facultatif)"
          value={form.referral}
          onChange={set('referral')}
          hint={config ? `Un ami vous a invité ? Vous recevez un bon de ${new Intl.NumberFormat('fr-FR').format(config.referral_reward)} GNF.` : undefined}
        />
        <ErrorBox message={error} />
        <Button type="submit" size="lg" loading={loading} className="w-full">Créer mon compte</Button>
      </form>
      <p className="mt-8 text-center text-sm text-ink/60">
        Déjà inscrit ? <Link to="/connexion" className="font-bold text-ink hover:underline">Se connecter</Link>
      </p>
      <Link to="/devenir-vendeur" className="mt-3 flex items-center justify-center gap-2 text-sm font-semibold text-primary-dark hover:underline">
        <Store className="size-4" /> Vous voulez vendre ? Ouvrez votre boutique
      </Link>
    </AuthLayout>
  )
}

export function VendorRegisterPage() {
  const { registerVendor } = useAuth()
  const navigate = useNavigate()
  const { data: config } = useSiteConfig()
  const [form, set] = useFormState({ name: '', email: '', phone: '', password: '', shop_name: '', shop_description: '', whatsapp: '' })
  const { submit, error, loading } = useSubmit(async () => {
    await registerVendor(form)
    navigate('/vendeur', { replace: true })
  })

  return (
    <AuthLayout
      title="Ouvrir ma boutique"
      subtitle={`Vendez dans toute la Guinée. Votre boutique est visible dès sa validation par l'équipe ${config?.site.name ?? ''}.`}
    >
      <form onSubmit={submit} className="space-y-4">
        <p className="text-xs font-bold uppercase tracking-wider text-ink/45">Votre boutique</p>
        <Input label="Nom de la boutique" value={form.shop_name} onChange={set('shop_name')} required />
        <Textarea label="Description" value={form.shop_description} onChange={set('shop_description')} placeholder="Ce que vous vendez, où vous êtes…" />
        <Input label="Numéro WhatsApp" type="tel" value={form.whatsapp} onChange={set('whatsapp')} placeholder="620 00 00 00" hint="Les clients pourront vous écrire directement." />
        <p className="pt-2 text-xs font-bold uppercase tracking-wider text-ink/45">Vous</p>
        <Input label="Nom complet" value={form.name} onChange={set('name')} required />
        <div className="grid gap-4 sm:grid-cols-2">
          <Input label="Email" type="email" value={form.email} onChange={set('email')} required />
          <Input label="Téléphone" type="tel" value={form.phone} onChange={set('phone')} />
        </div>
        <Input label="Mot de passe" type="password" value={form.password} onChange={set('password')} required minLength={8} hint="8 caractères minimum" />
        <ErrorBox message={error} />
        <Button type="submit" size="lg" loading={loading} className="w-full">Créer ma boutique</Button>
      </form>
    </AuthLayout>
  )
}

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const send = useMutation({ mutationFn: async () => (await api.post<{ detail: string }>('/auth/password/reset/', { email })).data })

  return (
    <AuthLayout title="Mot de passe oublié" subtitle="Indiquez votre email : nous vous envoyons un lien pour en choisir un nouveau.">
      {send.isSuccess ? (
        <div className="flex gap-3 rounded-2xl bg-green-50 p-4 text-green-800">
          <CheckCircle2 className="size-6 shrink-0" />
          <p>{send.data.detail}</p>
        </div>
      ) : (
        <form onSubmit={(e) => { e.preventDefault(); send.mutate() }} className="space-y-4">
          <Input label="Email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" />
          <ErrorBox message={send.error ? errorMessage(send.error) : null} />
          <Button type="submit" size="lg" loading={send.isPending} className="w-full">Envoyer le lien</Button>
        </form>
      )}
      <p className="mt-8 text-center text-sm"><Link to="/connexion" className="font-bold hover:underline">Retour à la connexion</Link></p>
    </AuthLayout>
  )
}

export function ResetPasswordPage() {
  const [params] = useSearchParams()
  const [password, setPassword] = useState('')
  const reset = useMutation({
    mutationFn: () => api.post('/auth/password/reset/confirm/', { uid: params.get('uid'), token: params.get('token'), password }),
  })

  return (
    <AuthLayout title="Nouveau mot de passe">
      {reset.isSuccess ? (
        <div className="space-y-4">
          <div className="flex gap-3 rounded-2xl bg-green-50 p-4 text-green-800">
            <KeyRound className="size-6 shrink-0" />
            <p>Votre mot de passe a été changé.</p>
          </div>
          <Link to="/connexion" className="flex w-full items-center justify-center rounded-full bg-primary px-6 py-3.5 text-lg font-semibold hover:bg-primary-hover">
            Se connecter
          </Link>
        </div>
      ) : (
        <form onSubmit={(e) => { e.preventDefault(); reset.mutate() }} className="space-y-4">
          <Input label="Nouveau mot de passe" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={8} autoComplete="new-password" hint="8 caractères minimum" />
          <ErrorBox message={reset.error ? errorMessage(reset.error) : null} />
          <Button type="submit" size="lg" loading={reset.isPending} className="w-full">Enregistrer</Button>
        </form>
      )}
    </AuthLayout>
  )
}
