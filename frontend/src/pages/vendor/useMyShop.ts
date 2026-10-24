import { useQuery } from '@tanstack/react-query'

import { api } from '../../lib/api'
import type { MyShop } from '../../lib/types'

export function useMyShop() {
  return useQuery({
    queryKey: ['my-shop'],
    queryFn: async () => (await api.get<MyShop>('/vendor/shop/')).data,
  })
}
