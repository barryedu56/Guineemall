import { usePrice } from '../context/settings'

/** Montant en GNF, avec l'équivalent € / $ en petit pour la diaspora. */
export default function Price({ amount, className = '' }: { amount: number; className?: string }) {
  const price = usePrice()(amount)
  return (
    <span className={className}>
      {price.main}
      {price.approx && <span className="ml-1 text-[0.75em] font-normal text-ink/50">{price.approx}</span>}
    </span>
  )
}
