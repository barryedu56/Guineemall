import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'

export const api = axios.create({ baseURL: '/api' })

const ACCESS = 'access_token'
const REFRESH = 'refresh_token'

export const tokens = {
  get access() {
    return localStorage.getItem(ACCESS)
  },
  get refresh() {
    return localStorage.getItem(REFRESH)
  },
  set(access: string, refresh?: string) {
    localStorage.setItem(ACCESS, access)
    if (refresh) localStorage.setItem(REFRESH, refresh)
  },
  clear() {
    localStorage.removeItem(ACCESS)
    localStorage.removeItem(REFRESH)
  },
}

api.interceptors.request.use((config) => {
  if (tokens.access) config.headers.Authorization = `Bearer ${tokens.access}`
  return config
})

// Le jeton d'accès dure 30 min : on le renouvelle automatiquement une fois, puis on rejoue la requête.
let refreshing: Promise<string> | null = null

api.interceptors.response.use(undefined, async (error: AxiosError) => {
  const original = error.config as (InternalAxiosRequestConfig & { _retried?: boolean }) | undefined
  if (error.response?.status !== 401 || !original || original._retried || !tokens.refresh) {
    throw error
  }
  original._retried = true
  try {
    refreshing ??= axios
      .post('/api/auth/token/refresh/', { refresh: tokens.refresh })
      .then(({ data }) => {
        tokens.set(data.access, data.refresh)
        return data.access as string
      })
      .finally(() => {
        refreshing = null
      })
    const access = await refreshing
    original.headers.Authorization = `Bearer ${access}`
    return api(original)
  } catch {
    tokens.clear()
    window.dispatchEvent(new Event('auth:logout'))
    throw error
  }
})

const NBSP = '\u00a0'

/** Nombre avec séparateur de milliers (espace insécable bien visible) : 1000 → « 1 000 » */
export function formatNumber(value: number) {
  return Math.round(value).toString().replace(/\B(?=(\d{3})+(?!\d))/g, NBSP)
}

/** Montant en francs guinéens : 150000 → « 150 000 GNF » */
export function formatGNF(amount: number) {
  return `${formatNumber(amount)}${NBSP}GNF`
}

/** Téléphone guinéen à 9 chiffres : 620112233 → « 620 11 22 33 » */
export function formatPhone(phone: string | null | undefined) {
  const d = (phone ?? '').replace(/\D/g, '')
  return d.length === 9 ? `${d.slice(0, 3)} ${d.slice(3, 5)} ${d.slice(5, 7)} ${d.slice(7)}` : (phone ?? '')
}

/** Extrait un message lisible d'une erreur de l'API Django REST Framework. */
export function errorMessage(error: unknown): string {
  if (error instanceof AxiosError) {
    if (error.response?.status === 429) return 'Trop de tentatives. Patientez quelques minutes avant de réessayer.'
    const data = error.response?.data
    if (typeof data === 'string') return 'Erreur du serveur, réessayez.'
    if (data && typeof data === 'object') {
      const first = Object.entries(data)[0]
      if (first) {
        const [field, value] = first
        const text = Array.isArray(value) ? value[0] : String(value)
        return field === 'detail' || field === 'non_field_errors' ? text : `${field} : ${text}`
      }
    }
    if (!error.response) return 'Connexion impossible. Vérifiez votre réseau.'
  }
  return 'Une erreur est survenue.'
}

export function formatDate(iso: string) {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso))
}

export function timeAgo(iso: string) {
  const minutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000)
  if (minutes < 1) return "à l'instant"
  if (minutes < 60) return `il y a ${minutes} min`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `il y a ${hours} h`
  return `il y a ${Math.floor(hours / 24)} j`
}

/** Code d'erreur métier renvoyé par l'API (ex. « otp_required »). */
export function errorCode(error: unknown): string | null {
  if (error instanceof AxiosError) {
    const data = error.response?.data
    if (data && typeof data === 'object' && 'code' in data) return String((data as { code: unknown }).code)
  }
  return null
}
