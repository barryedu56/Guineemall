import { FlaskConical, ShieldCheck } from 'lucide-react'

import { useSiteConfig } from '../context/settings'
import type { PaymentMethod } from '../lib/types'
import Price from './Price'

/**
 * Explique au client à qui il paie : toujours le compte marchand de la plateforme, jamais la boutique.
 * La plateforme reconnaît la commande grâce à sa référence, garde l'argent, puis paie la boutique.
 */
export default function PaymentInfo({ method, amount }: { method: PaymentMethod; amount?: number }) {
  const { data: config } = useSiteConfig()
  if (!config || method === 'cash_on_delivery' || method === 'store_credit') return null
  const name = config.site.name
  const merchant = config.payment_merchant
  const how: Partial<Record<PaymentMethod, string>> = {
    orange_money: `Vous passez sur la page officielle d'Orange Money : le bénéficiaire affiché est « ${name} »${merchant.orange_money ? ` (compte marchand ${merchant.orange_money})` : ''}. Vous y saisissez votre numéro et votre code secret, jamais sur ${name}.`,
    mtn_momo: `Une demande de paiement au nom de « ${name} »${merchant.mtn_momo ? ` (${merchant.mtn_momo})` : ''} arrive sur votre téléphone MTN : validez-la avec votre code secret.`,
    card: `Paiement par carte sécurisé (Stripe) au profit de « ${name} ».`,
  }
  return (
    <div className="space-y-2 rounded-2xl bg-sky-50 p-4 text-sm text-sky-950">
      <p className="font-semibold">
        Vous payez {name}{amount !== undefined && <> : <Price amount={amount} /></>}
      </p>
      <p>{how[method]}</p>
      <p className="flex gap-2">
        <ShieldCheck className="size-5 shrink-0 text-green-700" />
        La référence de votre commande accompagne le paiement : {name} sait qu'il s'agit de votre commande, garde l'argent, puis paie la boutique
        quand vous confirmez la réception (ou {config.escrow_days} jours après la livraison).
      </p>
      {config.payment_simulation && (
        <p className="flex gap-2 text-amber-800">
          <FlaskConical className="size-5 shrink-0" /> Mode essai : une page de simulation remplace celle de l'opérateur, aucun argent n'est débité.
        </p>
      )}
    </div>
  )
}
