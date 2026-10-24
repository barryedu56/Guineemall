import { Star } from 'lucide-react'

export default function Stars({ rating, count, size = 'sm' }: { rating: number | null; count?: number; size?: 'sm' | 'md' }) {
  if (!rating) return null
  const icon = size === 'sm' ? 'size-3.5' : 'size-5'
  return (
    <span className="inline-flex items-center gap-1 text-sm" aria-label={`${rating.toFixed(1)} sur 5`}>
      <span className="flex">
        {[1, 2, 3, 4, 5].map((i) => (
          <Star key={i} className={`${icon} ${i <= Math.round(rating) ? 'fill-primary text-primary' : 'text-ink/20'}`} />
        ))}
      </span>
      <span className="text-ink/60">
        {rating.toFixed(1)}
        {count !== undefined && ` (${count})`}
      </span>
    </span>
  )
}

export function StarInput({ value, onChange }: { value: number; onChange: (v: number) => void }) {
  return (
    <div className="flex gap-1">
      {[1, 2, 3, 4, 5].map((i) => (
        <button key={i} type="button" onClick={() => onChange(i)} aria-label={`${i} étoile(s)`}>
          <Star className={`size-8 ${i <= value ? 'fill-primary text-primary' : 'text-ink/20'}`} />
        </button>
      ))}
    </div>
  )
}
