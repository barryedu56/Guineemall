// Requêtes partagées entre plusieurs composants
import { useQuery } from '@tanstack/react-query'

import { api } from './api'
import type { Campaign, Live, Notification, Paginated } from './types'

export function useNotifications() {
  return useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await api.get<Paginated<Notification> & { unread: number }>('/notifications/')).data,
    refetchInterval: 120_000, // filet de sécurité si le temps réel est coupé
  })
}

export function useCampaigns() {
  return useQuery({
    queryKey: ['campaigns'],
    queryFn: async () => (await api.get<Campaign[]>('/campaigns/')).data,
  })
}

export function useLives() {
  return useQuery({
    queryKey: ['lives'],
    queryFn: async () => (await api.get<Live[]>('/lives/')).data,
    refetchInterval: 60_000,
  })
}
