import { useMutation, useQueryClient } from '@tanstack/react-query'
import { LocateFixed } from 'lucide-react'
import { useState, type FormEvent } from 'react'

import { Button, ErrorBox, Input, Select, Textarea } from './ui'
import { api, errorMessage } from '../lib/api'
import type { Address } from '../lib/types'

const COMMUNES_CONAKRY = ['Kaloum', 'Dixinn', 'Matam', 'Ratoma', 'Matoto', 'Sonfonia', 'Gbessia', 'Tombolia', 'Lambanyi']
const VILLES = ['Conakry', 'Coyah', 'Dubréka', 'Kindia', 'Boké', 'Mamou', 'Labé', 'Kankan', 'Faranah', 'Siguiri', 'Kissidougou', 'Guéckédou', 'Macenta', "N'Zérékoré"]

export default function AddressForm({ onSaved }: { onSaved: (address: Address) => void }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    label: 'Maison',
    city: 'Conakry',
    commune: '',
    quartier: '',
    landmark: '',
    contact_phone: '',
    latitude: '',
    longitude: '',
    is_default: true,
  })
  const [locating, setLocating] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const save = useMutation({
    mutationFn: async () =>
      (
        await api.post<Address>('/auth/addresses/', {
          ...form,
          latitude: form.latitude || null,
          longitude: form.longitude || null,
        })
      ).data,
    onSuccess: (address) => {
      queryClient.invalidateQueries({ queryKey: ['addresses'] })
      onSaved(address)
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

  return (
    <form onSubmit={submit} className="space-y-3">
      <div className="grid gap-3 sm:grid-cols-2">
        <Select label="Ville" value={form.city} onChange={set('city')}>
          {VILLES.map((v) => (
            <option key={v}>{v}</option>
          ))}
        </Select>
        {form.city === 'Conakry' ? (
          <Select label="Commune" value={form.commune} onChange={set('commune')} required>
            <option value="">Choisir…</option>
            {COMMUNES_CONAKRY.map((c) => (
              <option key={c}>{c}</option>
            ))}
          </Select>
        ) : (
          <Input label="Commune / sous-préfecture" value={form.commune} onChange={set('commune')} />
        )}
        <Input label="Quartier" value={form.quartier} onChange={set('quartier')} placeholder="Ex. : Kipé" required />
        <Input label="Téléphone à appeler" type="tel" value={form.contact_phone} onChange={set('contact_phone')} placeholder="620 00 00 00" required />
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
        <Button type="button" variant="outline" onClick={locate} loading={locating}>
          <LocateFixed className="size-4" /> Utiliser ma position
        </Button>
        {form.latitude && <span className="text-sm text-green-700">Position enregistrée ✓</span>}
      </div>
      <ErrorBox message={error} />
      <Button type="submit" loading={save.isPending} className="w-full">
        Enregistrer l'adresse
      </Button>
    </form>
  )
}
