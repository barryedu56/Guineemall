import { Share2 } from 'lucide-react'
import { useState } from 'react'

import { useAuth } from '../context/auth'
import { Button } from './ui'

/**
 * Partage d'un produit avec son image prête à poster (statut WhatsApp, Facebook, TikTok).
 * Si l'utilisateur est connecté, le lien contient son code ambassadeur : il touche une commission.
 */
export default function ShareButton({ slug, name }: { slug: string; name: string }) {
  const { user } = useAuth()
  const [copied, setCopied] = useState(false)
  const url = `${window.location.origin}/produit/${slug}${user ? `?ref=${user.referral_code}` : ''}`
  const text = `${name} sur GuinéeMall`

  async function share() {
    try {
      const blob = await (await fetch(`/api/products/${slug}/share-image/`)).blob()
      const file = new File([blob], `${slug}.jpg`, { type: 'image/jpeg' })
      if (navigator.canShare?.({ files: [file] })) {
        await navigator.share({ files: [file], title: name, text: `${text}\n${url}` })
        return
      }
      if (navigator.share) {
        await navigator.share({ title: name, text, url })
        return
      }
    } catch (e) {
      if ((e as DOMException).name === 'AbortError') return
    }
    // Ordinateur : copie du lien + ouverture de WhatsApp Web
    await navigator.clipboard?.writeText(url).catch(() => {})
    setCopied(true)
    setTimeout(() => setCopied(false), 2500)
    window.open(`https://wa.me/?text=${encodeURIComponent(`${text}\n${url}`)}`, '_blank', 'noopener')
  }

  return (
    <Button variant="outline" onClick={share} aria-label="Partager">
      <Share2 className="size-5" />
      {copied ? 'Lien copié' : 'Partager'}
    </Button>
  )
}
