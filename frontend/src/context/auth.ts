import { createContext, useContext } from 'react'

import type { User } from '../lib/types'

export interface RegisterData {
  email: string
  name: string
  phone: string
  password: string
  referral?: string
}

export interface VendorRegisterData extends RegisterData {
  shop_name: string
  shop_description: string
  whatsapp: string
}

export interface RiderRegisterData extends RegisterData {
  city: string
  communes: string
  vehicle_plate: string
}

export interface AuthContextValue {
  user: User | null
  loading: boolean
  login: (email: string, password: string, otp?: string) => Promise<User>
  register: (data: RegisterData) => Promise<User>
  registerVendor: (data: VendorRegisterData) => Promise<User>
  registerRider: (data: RiderRegisterData) => Promise<User>
  logout: () => void
}

export const AuthContext = createContext<AuthContextValue | null>(null)

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth doit être utilisé dans <AuthProvider>')
  return ctx
}
