import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useCallback, useEffect, useMemo, type ReactNode } from 'react'

import { api, tokens } from '../lib/api'
import type { User } from '../lib/types'
import { AuthContext, type RegisterData, type RiderRegisterData, type VendorRegisterData } from './auth'

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()

  const { data: user = null, isLoading } = useQuery({
    queryKey: ['me'],
    queryFn: async () => (await api.get<User>('/auth/me/')).data,
    enabled: !!tokens.access,
    retry: false,
    staleTime: Infinity,
  })

  const fetchMe = useCallback(async () => {
    const me = (await api.get<User>('/auth/me/')).data
    queryClient.setQueryData(['me'], me)
    await queryClient.invalidateQueries({ predicate: (q) => q.queryKey[0] !== 'me' })
    return me
  }, [queryClient])

  const logout = useCallback(() => {
    tokens.clear()
    queryClient.setQueryData(['me'], null)
    queryClient.removeQueries({ predicate: (q) => q.queryKey[0] !== 'me' })
  }, [queryClient])

  useEffect(() => {
    window.addEventListener('auth:logout', logout)
    return () => window.removeEventListener('auth:logout', logout)
  }, [logout])

  const value = useMemo(
    () => ({
      user,
      loading: isLoading && !!tokens.access,
      logout,
      login: async (email: string, password: string) => {
        const { data } = await api.post('/auth/token/', { email, password })
        tokens.set(data.access, data.refresh)
        return fetchMe()
      },
      register: async (payload: RegisterData) => {
        const { data } = await api.post('/auth/register/', payload)
        tokens.set(data.tokens.access, data.tokens.refresh)
        return fetchMe()
      },
      registerVendor: async (payload: VendorRegisterData) => {
        const { data } = await api.post('/auth/register/vendor/', payload)
        tokens.set(data.tokens.access, data.tokens.refresh)
        return fetchMe()
      },
      registerRider: async (payload: RiderRegisterData) => {
        const { data } = await api.post('/auth/register/rider/', payload)
        tokens.set(data.tokens.access, data.tokens.refresh)
        return fetchMe()
      },
    }),
    [user, isLoading, logout, fetchMe],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
