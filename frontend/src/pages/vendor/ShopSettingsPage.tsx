import { useMutation, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, ExternalLink } from 'lucide-react'
import { useMemo, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'

import { ShopAvatar } from '../../components/Statuts'
import { Button, ErrorBox, Input, Textarea } from '../../components/ui'
import { api, errorMessage } from '../../lib/api'
import type { MyShop } from '../../lib/types'
import { useMyShop } from './useMyShop'

export default function ShopSettingsPage() {
  const { data: shop } = useMyShop()
  return shop ? <ShopSettingsForm shop={shop} /> : null
}

function ShopSettingsForm({ shop }: { shop: MyShop }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ shop_name: shop.shop_name, description: shop.description, whatsapp: shop.whatsapp })
  const [logo, setLogo] = useState<File | null>(null)
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const save = useMutation({
    mutationFn: async () => {
      const data = new FormData()
      Object.entries(form).forEach(([k, v]) => data.append(k, v))
      if (logo) data.append('logo', logo)
      return (await api.patch<MyShop>('/vendor/shop/', data)).data
    },
    onSuccess: (updated) => {
      queryClient.setQueryData(['my-shop'], updated)
      queryClient.invalidateQueries({ queryKey: ['me'] })
      setLogo(null)
      setSaved(true)
      setTimeout(() => setSaved(false), 2000)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const logoPreview = useMemo(() => (logo ? URL.createObjectURL(logo) : null), [logo])

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    save.mutate()
  }

  return (
    <form onSubmit={submit} className="max-w-2xl space-y-4 rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
      <div className="flex items-center gap-4">
        <ShopAvatar logo={logoPreview ?? shop.logo} name={shop.shop_name} className="size-20" />
        <label className="cursor-pointer text-sm font-medium text-primary-dark hover:underline">
          Changer le logo
          <input type="file" accept="image/*" className="hidden" onChange={(e) => setLogo(e.target.files?.[0] ?? null)} />
        </label>
        {shop.is_verified && (
          <span className="ml-auto flex items-center gap-1 text-sm font-medium text-primary-dark">
            <BadgeCheck className="size-5" /> Vendeur vérifié
          </span>
        )}
      </div>
      <Input label="Nom de la boutique" value={form.shop_name} onChange={(e) => setForm({ ...form, shop_name: e.target.value })} required />
      <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
      <Input label="Numéro WhatsApp" type="tel" value={form.whatsapp} onChange={(e) => setForm({ ...form, whatsapp: e.target.value })} hint="Affiché sur vos produits avec le bouton « Commander via WhatsApp »." />
      <ErrorBox message={error} />
      <div className="flex flex-wrap items-center gap-4">
        <Button type="submit" loading={save.isPending}>
          {saved ? 'Enregistré ✓' : 'Enregistrer'}
        </Button>
        {shop.status === 'approved' && (
          <Link to={`/boutique/${shop.slug}`} className="flex items-center gap-1 text-sm text-primary-dark hover:underline">
            Voir ma boutique <ExternalLink className="size-4" />
          </Link>
        )}
      </div>
    </form>
  )
}
