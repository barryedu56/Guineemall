import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'

import { api } from '../lib/api'
import type { CartLine, ProductSummary } from '../lib/types'
import { useAuth } from './auth'
import { CartContext } from './cart'

/**
 * Panier unifié :
 * - invité : gardé dans le navigateur (localStorage) ;
 * - connecté : gardé sur le serveur. Le panier invité y est fusionné à la connexion.
 */

const GUEST_KEY = 'guest_cart'

type GuestLine = { product: ProductSummary; quantity: number }

function readGuestCart(): GuestLine[] {
  try {
    return JSON.parse(localStorage.getItem(GUEST_KEY) ?? '[]')
  } catch {
    return []
  }
}

function writeGuestCart(lines: GuestLine[]) {
  try {
    localStorage.setItem(GUEST_KEY, JSON.stringify(lines))
  } catch {
    // stockage indisponible (navigation privée) : le panier reste en mémoire
  }
}

export function CartProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [guest, setGuest] = useState<GuestLine[]>(readGuestCart)
  const merging = useRef(false)

  const { data, isLoading } = useQuery({
    queryKey: ['cart'],
    queryFn: async () => (await api.get<{ items: CartLine[]; total: number }>('/cart/')).data,
    enabled: !!user,
  })

  const refresh = useCallback(() => queryClient.invalidateQueries({ queryKey: ['cart'] }), [queryClient])

  // Fusion du panier invité après connexion
  useEffect(() => {
    if (!user || guest.length === 0 || merging.current) return
    merging.current = true
    Promise.allSettled(
      guest.map((l) => api.post('/cart/', { product_slug: l.product.slug, quantity: l.quantity })),
    ).finally(() => {
      setGuest([])
      writeGuestCart([])
      merging.current = false
      refresh()
    })
  }, [user, guest, refresh])

  const updateGuest = useCallback((next: GuestLine[]) => {
    setGuest(next)
    writeGuestCart(next)
  }, [])

  const value = useMemo(() => {
    const lines: CartLine[] = user
      ? (data?.items ?? [])
      : guest.map((l) => ({
          id: l.product.id,
          product: l.product,
          quantity: l.quantity,
          subtotal: l.product.price * l.quantity,
        }))

    return {
      lines,
      total: lines.reduce((sum, l) => sum + l.subtotal, 0),
      count: lines.reduce((sum, l) => sum + l.quantity, 0),
      loading: !!user && isLoading,
      add: async (product: ProductSummary, quantity = 1) => {
        if (user) {
          await api.post('/cart/', { product_slug: product.slug, quantity })
          await refresh()
          return
        }
        const existing = guest.find((l) => l.product.id === product.id)
        const wanted = Math.min((existing?.quantity ?? 0) + quantity, product.stock)
        updateGuest(
          existing
            ? guest.map((l) => (l === existing ? { ...l, quantity: wanted } : l))
            : [...guest, { product, quantity: wanted }],
        )
      },
      setQuantity: async (line: CartLine, quantity: number) => {
        if (user) {
          await api.patch(`/cart/${line.id}/`, { quantity })
          await refresh()
          return
        }
        updateGuest(guest.map((l) => (l.product.id === line.product.id ? { ...l, quantity } : l)))
      },
      remove: async (line: CartLine) => {
        if (user) {
          await api.delete(`/cart/${line.id}/`)
          await refresh()
          return
        }
        updateGuest(guest.filter((l) => l.product.id !== line.product.id))
      },
    }
  }, [user, data, guest, isLoading, refresh, updateGuest])

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}
