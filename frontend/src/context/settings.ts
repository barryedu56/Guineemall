import { useQuery } from '@tanstack/react-query'
import { useSyncExternalStore } from 'react'

import { api, formatGNF } from '../lib/api'
import { currencyPref, type Currency } from '../lib/prefs'
import type { SiteConfig } from '../lib/types'

export function useSiteConfig() {
  return useQuery({
    queryKey: ['config'],
    queryFn: async () => (await api.get<SiteConfig>('/config/')).data,
    staleTime: Infinity,
  })
}

// Devise d'affichage partagée entre tous les composants (diaspora)
const listeners = new Set<() => void>()
let current: Currency = currencyPref.get()

export function setCurrency(c: Currency) {
  current = c
  currencyPref.set(c)
  listeners.forEach((l) => l())
}

export function useCurrency() {
  return useSyncExternalStore(
    (l) => {
      listeners.add(l)
      return () => listeners.delete(l)
    },
    () => current,
  )
}

/** Prix en GNF, avec l'équivalent approximatif en € ou $ si la diaspora l'a choisi. */
export function usePrice() {
  const currency = useCurrency()
  const { data: config } = useSiteConfig()
  return (amount: number) => {
    if (currency === 'GNF' || !config) return { main: formatGNF(amount), approx: null as string | null }
    const value = amount / config.exchange_rates[currency]
    const approx = new Intl.NumberFormat('fr-FR', { style: 'currency', currency, maximumFractionDigits: value < 100 ? 2 : 0 }).format(value)
    return { main: formatGNF(amount), approx: `≈ ${approx}` }
  }
}
