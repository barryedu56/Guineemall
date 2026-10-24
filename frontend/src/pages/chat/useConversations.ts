import { useQuery } from '@tanstack/react-query'

import { useAuth } from '../../context/auth'
import { api } from '../../lib/api'
import type { Conversation } from '../../lib/types'

export function useConversations() {
  const { user } = useAuth()
  return useQuery({
    queryKey: ['conversations'],
    queryFn: async () => (await api.get<Conversation[]>('/conversations/')).data,
    enabled: !!user,
  })
}
