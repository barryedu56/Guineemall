import { useMutation, useQuery } from '@tanstack/react-query'
import { FlaskConical } from 'lucide-react'
import { useNavigate, useParams } from 'react-router-dom'

import Price from '../components/Price'
import { Button, Spinner } from '../components/ui'
import { api } from '../lib/api'

/** Page de paiement simulée (développement) : remplace la page Orange Money / MTN / Stripe. */
export default function PaymentSimulationPage() {
  const { reference } = useParams()
  const navigate = useNavigate()
  const { data: payment, isLoading } = useQuery({
    queryKey: ['payment', reference],
    queryFn: async () =>
      (await api.get<{ order_id: number; amount: number; provider_label: string; status: string }>(`/payments/${reference}/`)).data,
  })
  const decide = useMutation({
    mutationFn: (result: 'success' | 'failed') => api.post(`/payments/simulation/${reference}/`, { result }),
    onSuccess: () => navigate(`/commandes/${payment!.order_id}?paiement=${reference}`, { replace: true }),
  })

  if (isLoading || !payment) return <Spinner />

  return (
    <div className="card mx-auto max-w-sm space-y-5 p-8 text-center">
      <FlaskConical className="mx-auto size-10 text-primary-dark" />
      <div>
        <p className="text-sm text-ink/60">Paiement simulé · {payment.provider_label}</p>
        <Price amount={payment.amount} className="mt-1 block text-3xl font-extrabold" />
      </div>
      <p className="text-sm text-ink/70">
        Mode essai : aucun argent n'est débité. En réel, c'est la page de l'opérateur, où le bénéficiaire affiché est le compte
        marchand de la plateforme ; la boutique est payée ensuite par la plateforme.
      </p>
      <div className="grid gap-2">
        <Button loading={decide.isPending && decide.variables === 'success'} onClick={() => decide.mutate('success')}>
          Simuler un paiement réussi
        </Button>
        <Button variant="outline" loading={decide.isPending && decide.variables === 'failed'} onClick={() => decide.mutate('failed')}>
          Simuler un refus
        </Button>
      </div>
    </div>
  )
}
