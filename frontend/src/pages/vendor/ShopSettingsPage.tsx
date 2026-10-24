import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { BadgeCheck, ExternalLink } from 'lucide-react'
import { useMemo, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'

import { ShopAvatar } from '../../components/Statuts'
import { Button, ErrorBox, Input, Select, Textarea } from '../../components/ui'
import { api, errorMessage, formatDate } from '../../lib/api'
import type { MyShop } from '../../lib/types'
import { useMyShop } from './useMyShop'

export default function ShopSettingsPage() {
  const { data: shop } = useMyShop()
  if (!shop) return null
  return (
    <div className="grid max-w-5xl gap-6 lg:grid-cols-2">
      <ShopSettingsForm shop={shop} />
      {!shop.is_verified && <VerificationForm />}
    </div>
  )
}

interface VerificationRequest {
  id: number
  status: 'pending' | 'approved' | 'rejected'
  status_label: string
  admin_note: string
  created_at: string
}

function VerificationForm() {
  const queryClient = useQueryClient()
  const [type, setType] = useState('id_card')
  const [number, setNumber] = useState('')
  const [files, setFiles] = useState<Record<string, File | null>>({ document_front: null, document_back: null, shop_photo: null })
  const [error, setError] = useState<string | null>(null)
  const { data: requests = [] } = useQuery({
    queryKey: ['verification'],
    queryFn: async () => (await api.get<VerificationRequest[]>('/vendor/verification/')).data,
  })
  const last = requests[0]
  const send = useMutation({
    mutationFn: () => {
      const data = new FormData()
      data.append('document_type', type)
      data.append('document_number', number)
      Object.entries(files).forEach(([k, f]) => f && data.append(k, f))
      return api.post('/vendor/verification/', data)
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['verification'] }),
    onError: (e) => setError(errorMessage(e)),
  })

  if (last?.status === 'pending') {
    return (
      <div className="h-fit rounded-2xl bg-amber-50 p-5 text-amber-900">
        <p className="font-semibold">Demande de badge en cours d'examen</p>
        <p className="text-sm">Envoyée le {formatDate(last.created_at)}. Vous serez prévenu de la décision.</p>
      </div>
    )
  }

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        setError(null)
        send.mutate()
      }}
      className="h-fit space-y-3 rounded-2xl bg-surface p-5 ring-1 ring-ink/5"
    >
      <h2 className="flex items-center gap-2 text-lg font-semibold"><BadgeCheck className="size-5 text-primary-dark" /> Badge « Vendeur vérifié »</h2>
      <p className="text-sm text-ink/70">Vos documents sont vus uniquement par l'équipe GuinéeMall et ne sont jamais affichés.</p>
      {last?.status === 'rejected' && (
        <p className="rounded-xl bg-red-50 p-3 text-sm text-red-700">Demande précédente refusée{last.admin_note ? ` : ${last.admin_note}` : '.'}</p>
      )}
      <Select label="Document" value={type} onChange={(e) => setType(e.target.value)}>
        <option value="id_card">Carte d'identité</option>
        <option value="passport">Passeport</option>
        <option value="rccm">Registre du commerce (RCCM)</option>
      </Select>
      <Input label="Numéro du document" value={number} onChange={(e) => setNumber(e.target.value)} required />
      {[
        ['document_front', 'Photo du recto *'],
        ['document_back', 'Photo du verso'],
        ['shop_photo', 'Photo de votre boutique'],
      ].map(([key, label]) => (
        <label key={key} className="block text-sm">
          <span className="font-medium">{label}</span>
          <input type="file" accept="image/*" required={key === 'document_front'} onChange={(e) => setFiles({ ...files, [key]: e.target.files?.[0] ?? null })} className="mt-1 block w-full text-sm" />
        </label>
      ))}
      <ErrorBox message={error} />
      <Button type="submit" loading={send.isPending}>Envoyer ma demande</Button>
    </form>
  )
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
    <form onSubmit={submit} className="h-fit space-y-4 rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
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
