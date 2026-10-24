import { Compass } from 'lucide-react'
import { Link } from 'react-router-dom'

import { Empty } from '../components/ui'

export default function NotFound() {
  return (
    <Empty icon={<Compass />} title="Cette page n'existe pas">
      <p className="text-ink/60">Le lien est peut-être ancien ou mal recopié.</p>
      <Link to="/" className="rounded-full bg-ink px-6 py-3 font-semibold text-white hover:bg-ink/90">Retour à l'accueil</Link>
    </Empty>
  )
}
