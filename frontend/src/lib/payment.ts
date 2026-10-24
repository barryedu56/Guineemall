import type { NavigateFunction } from 'react-router-dom'

import { api } from './api'

/**
 * Lance le paiement en ligne d'une commande :
 * - Orange Money / carte / simulation : redirection vers la page de paiement ;
 * - MTN : le client valide sur son téléphone, on revient au suivi qui vérifie le statut.
 */
export async function startPayment(orderId: number, phone: string, navigate: NavigateFunction) {
  const { data } = await api.post<{ reference: string; redirect_url: string; message: string }>(
    `/orders/${orderId}/pay/`,
    { phone },
  )
  if (data.redirect_url) {
    const url = new URL(data.redirect_url, window.location.origin)
    if (url.origin === window.location.origin) navigate(url.pathname, { replace: true })
    else window.location.assign(data.redirect_url)
    return
  }
  navigate(`/commandes/${orderId}?paiement=${data.reference}&message=${encodeURIComponent(data.message)}`, { replace: true })
}
