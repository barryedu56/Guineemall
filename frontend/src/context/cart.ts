import { createContext, useContext } from 'react'

import type { CartLine, ProductSummary } from '../lib/types'

export interface CartContextValue {
  lines: CartLine[]
  total: number
  count: number
  loading: boolean
  add: (product: ProductSummary, quantity?: number) => Promise<void>
  setQuantity: (line: CartLine, quantity: number) => Promise<void>
  remove: (line: CartLine) => Promise<void>
}

export const CartContext = createContext<CartContextValue | null>(null)

export function useCart() {
  const ctx = useContext(CartContext)
  if (!ctx) throw new Error('useCart doit être utilisé dans <CartProvider>')
  return ctx
}
