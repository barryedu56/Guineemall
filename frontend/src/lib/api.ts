import axios from 'axios'

export const api = axios.create({ baseURL: '/api' })

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

/** Formate un montant en francs guinéens : 150000 → « 150 000 GNF » */
export function formatGNF(amount: number) {
  return `${new Intl.NumberFormat('fr-FR').format(amount)} GNF`
}
