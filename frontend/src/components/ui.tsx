import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-react'
import { createPortal } from 'react-dom'

import { formatNumber } from '../lib/api'
import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react'

type Variant = 'primary' | 'dark' | 'outline' | 'ghost' | 'danger'
type Size = 'sm' | 'md' | 'lg'

const variants: Record<Variant, string> = {
  primary: 'bg-primary text-ink shadow-sm hover:bg-primary-hover active:scale-[0.98]',
  dark: 'bg-ink text-white shadow-sm hover:bg-ink/90 active:scale-[0.98]',
  outline: 'border border-ink/15 bg-surface hover:border-ink/30 hover:bg-ink/[0.03]',
  ghost: 'hover:bg-ink/5',
  danger: 'bg-danger text-white hover:bg-red-700',
}

const sizes: Record<Size, string> = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-5 py-2.5',
  lg: 'px-6 py-3.5 text-lg',
}

export function Button({
  variant = 'primary',
  size = 'md',
  loading,
  className = '',
  children,
  disabled,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant; size?: Size; loading?: boolean }) {
  return (
    <button
      className={`inline-flex items-center justify-center gap-2 rounded-full font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant]} ${sizes[size]} ${className}`}
      disabled={disabled || loading}
      {...props}
    >
      {loading && <Loader2 className="size-4 animate-spin" />}
      {children}
    </button>
  )
}

const fieldClass =
  'w-full rounded-2xl border border-ink/10 bg-surface px-4 py-3 outline-none transition placeholder:text-ink/35 hover:border-ink/20 focus:border-primary-dark focus:ring-4 focus:ring-primary/25 disabled:bg-ink/[0.03] disabled:text-ink/50'

function Field({ label, hint, children }: { label: string; hint?: string; children: ReactNode }) {
  return (
    <label className="block space-y-1.5">
      <span className="text-sm font-semibold">{label}</span>
      {children}
      {hint && <span className="block text-xs text-ink/55">{hint}</span>}
    </label>
  )
}

export function Input({ label, hint, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string; hint?: string }) {
  return (
    <Field label={label} hint={hint}>
      <input className={fieldClass} {...props} />
    </Field>
  )
}

const PHONE_DIGITS = 9

/**
 * Garde les 9 chiffres du numéro local et retire l'indicatif (+224, 00224), qu'il soit collé ou tapé.
 * Un numéro guinéen local commence par 6 (mobile) ou 3 (fixe), jamais par 224 : ce préfixe est donc l'indicatif.
 */
function localDigits(raw: string) {
  let d = raw.replace(/\D/g, '')
  if (d.startsWith('00224')) d = d.slice(5)
  else if (d.startsWith('224')) d = d.slice(3)
  return d.slice(0, PHONE_DIGITS)
}

/** Téléphone guinéen : chiffres uniquement, 9 maximum, affiché « 620 11 22 33 ». La valeur transmise est « 620112233 ». */
export function PhoneInput({
  label,
  hint,
  value,
  onValueChange,
  ...props
}: Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'onChange' | 'type'> & {
  label: string
  hint?: string
  value: string
  onValueChange: (digits: string) => void
}) {
  const digits = value.replace(/\D/g, '').slice(0, PHONE_DIGITS)
  const shown = [digits.slice(0, 3), digits.slice(3, 5), digits.slice(5, 7), digits.slice(7, 9)].filter(Boolean).join(' ')
  const incomplete = digits.length > 0 && digits.length < PHONE_DIGITS
  return (
    <Field label={label} hint={incomplete ? `Encore ${PHONE_DIGITS - digits.length} chiffre(s) — 9 chiffres au total` : hint ?? '9 chiffres, ex. 620 11 22 33'}>
      <input
        {...props}
        className={fieldClass}
        type="tel"
        inputMode="numeric"
        autoComplete="tel-national"
        placeholder="620 11 22 33"
        value={shown}
        maxLength={12}
        pattern="\d{3} \d{2} \d{2} \d{2}"
        title="9 chiffres, ex. 620 11 22 33"
        onChange={(e) => onValueChange(localDigits(e.target.value))}
      />
    </Field>
  )
}

/** Montant en GNF : affiché « 1 850 000 » pendant la saisie, transmis « 1850000 ». */
export function AmountInput({
  label,
  hint,
  value,
  onValueChange,
  ...props
}: Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'onChange' | 'type'> & {
  label: string
  hint?: string
  value: string
  onValueChange: (digits: string) => void
}) {
  const digits = value.replace(/\D/g, '')
  return (
    <Field label={label} hint={hint}>
      <input
        {...props}
        className={fieldClass}
        type="text"
        inputMode="numeric"
        value={digits ? formatNumber(Number(digits)) : ''}
        onChange={(e) => onValueChange(e.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, ''))}
      />
    </Field>
  )
}

export function Textarea({ label, hint, ...props }: TextareaHTMLAttributes<HTMLTextAreaElement> & { label: string; hint?: string }) {
  return (
    <Field label={label} hint={hint}>
      <textarea className={fieldClass} rows={3} {...props} />
    </Field>
  )
}

export function Select({ label, children, ...props }: SelectHTMLAttributes<HTMLSelectElement> & { label: string }) {
  return (
    <Field label={label}>
      <select className={`${fieldClass} appearance-none bg-[url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%231b1d21' stroke-width='2'%3E%3Cpath d='m4 6 4 4 4-4'/%3E%3C/svg%3E")] bg-[length:16px] bg-[right_1rem_center] bg-no-repeat pr-10`} {...props}>
        {children}
      </select>
    </Field>
  )
}

export function Spinner() {
  return (
    <div className="flex justify-center py-16">
      <Loader2 className="size-8 animate-spin text-primary-dark" />
    </div>
  )
}

export function Skeleton({ className = '' }: { className?: string }) {
  return <div className={`skeleton ${className}`} />
}

export function Empty({ icon, title, children }: { icon: ReactNode; title: string; children?: ReactNode }) {
  return (
    <div className="flex flex-col items-center gap-3 px-4 py-16 text-center">
      <div className="flex size-20 items-center justify-center rounded-full bg-primary-light text-primary-dark [&>svg]:size-9">{icon}</div>
      <p className="text-lg font-bold">{title}</p>
      {children}
    </div>
  )
}

export function ErrorBox({ message }: { message: string | null }) {
  if (!message) return null
  return <p className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{message}</p>
}

type Tone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'brand'

export function Badge({ children, tone = 'neutral' }: { children: ReactNode; tone?: Tone }) {
  const tones: Record<Tone, string> = {
    neutral: 'bg-ink/5 text-ink/70',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-amber-100 text-amber-800',
    danger: 'bg-red-100 text-red-700',
    info: 'bg-sky-100 text-sky-800',
    brand: 'bg-primary text-ink',
  }
  return <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold ${tones[tone]}`}>{children}</span>
}

// Couleur de la pastille selon le statut (présentation uniquement : les libellés viennent de l'API)
const STATUS_TONES: Record<string, Tone> = {
  pending: 'warning',
  confirmed: 'info',
  preparing: 'info',
  ready: 'info',
  processing: 'info',
  delivered: 'success',
  cancelled: 'danger',
}

export function StatusBadge({ status, label }: { status: string; label: string }) {
  return <Badge tone={STATUS_TONES[status] ?? 'neutral'}>{label}</Badge>
}

/** Titre de page avec sous-titre et actions à droite. */
export function PageHeader({ title, subtitle, children }: { title: ReactNode; subtitle?: ReactNode; children?: ReactNode }) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 className="text-2xl font-extrabold sm:text-3xl">{title}</h1>
        {subtitle && <p className="mt-1 text-ink/60">{subtitle}</p>}
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  )
}

/** Titre de section avec lien « voir tout ». */
export function SectionHeader({ title, action }: { title: ReactNode; action?: ReactNode }) {
  return (
    <div className="mb-4 flex items-end justify-between gap-3">
      <h2 className="text-xl font-extrabold">{title}</h2>
      {action && <div className="text-sm font-semibold text-primary-dark">{action}</div>}
    </div>
  )
}

/** Pagination numérotée (le nombre de pages vient de l'API). */
export function Pager({ page, totalPages, onChange }: { page: number; totalPages: number; onChange: (page: number) => void }) {
  if (totalPages <= 1) return null
  const pages = Array.from({ length: totalPages }, (_, i) => i + 1).filter(
    (p) => p === 1 || p === totalPages || Math.abs(p - page) <= 1,
  )
  return (
    <nav className="mt-8 flex items-center justify-center gap-1" aria-label="Pagination">
      <button disabled={page <= 1} onClick={() => onChange(page - 1)} className="rounded-full p-2 hover:bg-ink/5 disabled:opacity-30" aria-label="Page précédente">
        <ChevronLeft className="size-5" />
      </button>
      {pages.map((p, i) => (
        <span key={p} className="flex items-center">
          {i > 0 && p - pages[i - 1] > 1 && <span className="px-1 text-ink/40">…</span>}
          <button
            onClick={() => onChange(p)}
            className={`size-10 rounded-full text-sm font-semibold ${p === page ? 'bg-ink text-white' : 'hover:bg-ink/5'}`}
            aria-current={p === page ? 'page' : undefined}
          >
            {p}
          </button>
        </span>
      ))}
      <button disabled={page >= totalPages} onClick={() => onChange(page + 1)} className="rounded-full p-2 hover:bg-ink/5 disabled:opacity-30" aria-label="Page suivante">
        <ChevronRight className="size-5" />
      </button>
    </nav>
  )
}

/** Bouton « Charger plus » pour les listes en défilement continu. */
export function LoadMore({ hasMore, loading, onClick }: { hasMore: boolean; loading: boolean; onClick: () => void }) {
  if (!hasMore) return null
  return (
    <div className="mt-6 flex justify-center">
      <Button variant="outline" loading={loading} onClick={onClick}>Voir plus</Button>
    </div>
  )
}

/**
 * Affiche son contenu à la racine du document (au-dessus de tout, plein écran fiable),
 * quel que soit l'endroit où le composant est utilisé dans la page.
 */
export function Overlay({ children }: { children: ReactNode }) {
  return createPortal(children, document.body)
}
