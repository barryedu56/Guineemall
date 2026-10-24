// Préférences gardées dans le navigateur (accès protégé : navigation privée possible)

function read(key: string) {
  try {
    return localStorage.getItem(key)
  } catch {
    return null
  }
}

function write(key: string, value: string | null) {
  try {
    if (value === null) localStorage.removeItem(key)
    else localStorage.setItem(key, value)
  } catch {
    // stockage indisponible
  }
}

const AFFILIATE_KEY = 'affiliate_ref'
const AFFILIATE_DAYS = 30

/** Mémorise le code ambassadeur d'un lien ?ref=CODE pendant 30 jours. */
export function captureAffiliateCode() {
  const code = new URLSearchParams(window.location.search).get('ref')
  if (code) write(AFFILIATE_KEY, JSON.stringify({ code: code.toUpperCase(), at: Date.now() }))
}

export function affiliateCode(): string {
  const raw = read(AFFILIATE_KEY)
  if (!raw) return ''
  try {
    const { code, at } = JSON.parse(raw)
    return Date.now() - at < AFFILIATE_DAYS * 86_400_000 ? code : ''
  } catch {
    return ''
  }
}

export type Currency = 'GNF' | 'EUR' | 'USD'

export const currencyPref = {
  get: (): Currency => (read('currency') as Currency) || 'GNF',
  set: (c: Currency) => write('currency', c),
}
