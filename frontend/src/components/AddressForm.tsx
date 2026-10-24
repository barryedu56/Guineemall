import { useMutation, useQueryClient } from '@tanstack/react-query'
import { LocateFixed } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import { useSiteConfig } from '../context/settings'
import { api, errorMessage } from '../lib/api'
import type { Address } from '../lib/types'
import { Button, ErrorBox, Input, Select, Textarea } from './ui'

/**
 * Création ou modification d'une adresse. Les villes et communes proposées sont
 * celles où la livraison est réellement assurée (zones définies dans l'admin).
 */
export default function AddressForm({ address, onSaved, onCancel }: { address?: Address; onSaved: (address: Address) => void; onCancel?: () => void }) {
  const queryClient = useQueryClient()
  const { data: config } = useSiteConfig()
  const locations = config?.delivery_locations ?? []
  const [form, setForm] = useState({
    label: address?.label ?? 'Maison',
    city: address?.city ?? locations[0]?.city ?? '',
    commune: address?.commune ?? '',
    quartier: address?.quartier ?? '',
    landmark: address?.landmark ?? '',
    contact_phone: address?.contact_phone ?? '',
    latitude: address?.latitude ?? '',
    longitude: address?.longitude ?? '',
    is_default: address?.is_default ?? true,
  })
  const [locating, setLocating] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const city = form.city || locations[0]?.city || ''
  const communes = locations.find((l) => l.city === city)?.communes ?? []

  const save = useMutation({
    mutationFn: async () => {
      const payload = { ...form, city, latitude: form.latitude || null, longitude: form.longitude || null }
      const request = address ? api.patch<Address>(`/auth/addresses/${address.id}/`, payload) : api.post<Address>('/auth/addresses/', payload)
      return (await request).data
    },
    onSuccess: (saved) => {
      queryClient.invalidateQueries({ queryKey: ['addresses'] })
      onSaved(saved)
    },
    onError: (e) => setError(errorMessage(e)),
  })

  const set = (key: keyof typeof form) => (e: { target: { value: string } }) => setForm({ ...form, [key]: e.target.value })

  function locate() {
    if (!navigator.geolocation) return setError("La géolocalisation n'est pas disponible sur cet appareil.")
    setLocating(true)
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setForm((f) => ({ ...f, latitude: pos.coords.latitude.toFixed(6), longitude: pos.coords.longitude.toFixed(6) }))
        setLocating(false)
      },
      () => {
        setError("Impossible d'obtenir votre position. Le repère suffit.")
        setLocating(false)
      },
      { enableHighAccuracy: true, timeout: 10000 },
    )
  }

  function submit(e: FormEvent) {
    e.preventDefault()
    setError(null)
    save.mutate()
  }

  if (!locations.length) {
    return <ErrorBox message="La livraison n'est pas encore ouverte. Choisissez un point relais." />
  }

  return (
    <form onSubmit={submit} className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2">
        <Select label="Ville" value={city} onChange={(e) => setForm({ ...form, city: e.target.value, commune: '' })}>
          {locations.map((l) => <option key={l.city}>{l.city}</option>)}
        </Select>
        {communes.length > 0 ? (
          <Select label="Commune" value={form.commune} onChange={set('commune')}>
            <option value="">Autre commune de {city}</option>
            {communes.map((c) => <option key={c}>{c}</option>)}
          </Select>
        ) : (
          <Input label="Commune / sous-préfecture" value={form.commune} onChange={set('commune')} />
        )}
        <Input label="Quartier" value={form.quartier} onChange={set('quartier')} placeholder="Ex. : Kipé" required />
        <Input label="Téléphone à appeler" type="tel" value={form.contact_phone} onChange={set('contact_phone')} placeholder="620 00 00 00" required />
        <Input label="Nom de l'adresse" value={form.label} onChange={set('label')} placeholder="Maison, Bureau…" />
      </div>
      <Textarea
        label="Repère"
        value={form.landmark}
        onChange={set('landmark')}
        placeholder="Ex. : derrière la grande mosquée, portail bleu, 2e maison à gauche"
        hint="Décrivez comment trouver chez vous : le livreur vous appellera aussi."
        required
      />
      <div className="flex flex-wrap items-center gap-3">
        <Button type="button" variant="outline" size="sm" onClick={locate} loading={locating}>
          <LocateFixed className="size-4" /> Utiliser ma position
        </Button>
        {form.latitude && <span className="text-sm font-medium text-success">Position enregistrée ✓</span>}
        <label className="ml-auto flex items-center gap-2 text-sm">
          <input type="checkbox" checked={form.is_default} onChange={(e) => setForm({ ...form, is_default: e.target.checked })} className="size-4 accent-primary-dark" />
          Adresse par défaut
        </label>
      </div>
      <ErrorBox message={error} />
      <div className="flex gap-2">
        <Button type="submit" loading={save.isPending} className="flex-1">{address ? 'Enregistrer les modifications' : "Enregistrer l'adresse"}</Button>
        {onCancel && <Button type="button" variant="ghost" onClick={onCancel}>Annuler</Button>}
      </div>
    </form>
  )
}
