// Requêtes partagées entre plusieurs composants
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import { useAuth } from '../context/auth'
import { api } from './api'
import type { Campaign, Category, Live, Notification, Paginated, ProductSummary } from './types'

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

export function useCategories() {
  return useQuery({
    queryKey: ['categories'],
    queryFn: async () => (await api.get<Category[]>('/categories/')).data,
    staleTime: 10 * 60_000,
  })
}

/** Favoris de l'utilisateur connecté : ensemble des slugs + bascule. */
export function useFavorites() {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const { data } = useQuery({
    queryKey: ['favorites'],
    queryFn: async () => (await api.get<Paginated<ProductSummary>>('/favorites/', { params: { page_size: 100 } })).data,
    enabled: !!user,
  })
  const slugs = new Set(data?.results.map((p) => p.slug))
  const toggle = useMutation({
    mutationFn: (slug: string) => (slugs.has(slug) ? api.delete(`/favorites/${slug}/`) : api.post(`/favorites/${slug}/`)),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['favorites'] }),
  })
  return { enabled: !!user, isFavorite: (slug: string) => slugs.has(slug), toggle }
}

/** Tous les éléments d'une liste paginée (pour les listes déroulantes : 100 max). */
export async function fetchAll<T>(url: string) {
  return (await api.get<Paginated<T>>(url, { params: { page_size: 100 } })).data.results
}
