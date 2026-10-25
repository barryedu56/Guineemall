// Hooks du back-office (listes, actions, suppression) : état des filtres dans l'adresse
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useSearchParams } from 'react-router-dom'

import { useToast } from '../../context/toast'
import { api, errorMessage } from '../../lib/api'
import type { Paginated } from '../../lib/types'

export const BO = '/backoffice'

/** Liste paginée d'une ressource du back-office ; recherche, filtres et page viennent de l'adresse. */
export function useAdminList<T>(resource: string, filterKeys: string[] = []) {
  const [params] = useSearchParams()
  const query: Record<string, string> = {}
  for (const key of ['search', 'page', ...filterKeys]) {
    const value = params.get(key)
    if (value) query[key] = value
  }
  return useQuery({
    queryKey: ['backoffice', resource, query],
    queryFn: async () => (await api.get<Paginated<T>>(`${BO}/${resource}/`, { params: query })).data,
    placeholderData: keepPreviousData,
  })
}

/** Liste complète (ressources sans pagination : catégories, zones…). */
export function useAdminAll<T>(resource: string) {
  return useQuery({
    queryKey: ['backoffice', resource, 'all'],
    queryFn: async () => (await api.get<T[]>(`${BO}/${resource}/`)).data,
  })
}

export function useUrlParam(key: string) {
  const [params, setParams] = useSearchParams()
  const set = (value: string) => {
    const next = new URLSearchParams(params)
    if (value) next.set(key, value)
    else next.delete(key)
    if (key !== 'page') next.delete('page')
    setParams(next, { replace: true })
  }
  return [params.get(key) ?? '', set] as const
}

/** POST /backoffice/<resource>/<id>/<action>/ puis rafraîchit les listes concernées. */
export function useAdminAction(resource: string) {
  const queryClient = useQueryClient()
  const toast = useToast()
  return useMutation({
    mutationFn: async ({ id, action, body, success }: { id: number; action: string; body?: object; success: string }) => {
      await api.post(`${BO}/${resource}/${id}/${action}/`, body ?? {})
      return success
    },
    onSuccess: (message) => {
      toast(message)
      queryClient.invalidateQueries({ queryKey: ['backoffice'] })
      queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
}

/** Suppression avec confirmation. */
export function useAdminDelete(resource: string, label: string) {
  const queryClient = useQueryClient()
  const toast = useToast()
  return useMutation({
    mutationFn: (id: number) => api.delete(`${BO}/${resource}/${id}/`),
    onSuccess: () => {
      toast(`${label} supprimé(e)`)
      queryClient.invalidateQueries({ queryKey: ['backoffice'] })
      queryClient.invalidateQueries({ queryKey: ['config'] })
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
}
