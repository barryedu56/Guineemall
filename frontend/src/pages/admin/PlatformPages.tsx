import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { MapPin, Pencil, Plus, Radio, ScrollText, Store, Tag, Trash2 } from 'lucide-react'
import { useState } from 'react'

import Price from '../../components/Price'
import { Badge, Button, Empty, ErrorBox, PageHeader, Spinner } from '../../components/ui'
import { useSiteConfig } from '../../context/settings'
import { useToast } from '../../context/toast'
import { api, errorMessage, formatDate, formatPhone } from '../../lib/api'
import { fetchAll } from '../../lib/queries'
import type { ProductSummary } from '../../lib/types'
import { BO, useAdminAll, useAdminDelete, useAdminList } from './hooks'
import { AdminTable, CrudForm, ListView, Toolbar, type Field } from './kit'
import type { AdminCampaign, AdminCoupon, AdminPickupPoint, AdminZone, AuditEntry } from './types'

// ---------------------------------------------------------------- Codes promo

export function AdminCoupons() {
  const { data: config } = useSiteConfig()
  const [now] = useState(() => new Date().toISOString())
  const query = useAdminList<AdminCoupon>('coupons', ['is_active', 'type'])
  const remove = useAdminDelete('coupons', 'Code promo')
  const [editing, setEditing] = useState<AdminCoupon | 'new' | null>(null)
  const fields: Field[] = [
    { name: 'code', label: 'Code', type: 'text', required: true, hint: 'Ex. TABASKI2026 (majuscules automatiques)' },
    { name: 'type', label: 'Type', type: 'select', required: true, options: config?.choices.coupon_type ?? [] },
    { name: 'value', label: 'Valeur (% ou GNF)', type: 'number', required: true },
    { name: 'max_discount', label: 'Réduction maximale (GNF)', type: 'amount', hint: 'Pour les pourcentages. Vide = sans limite' },
    { name: 'min_amount', label: 'Achat minimum (GNF)', type: 'amount' },
    { name: 'usage_limit', label: 'Utilisations au total', type: 'number', hint: 'Vide = illimité' },
    { name: 'per_user_limit', label: 'Utilisations par client', type: 'number', required: true },
    { name: 'starts_at', label: 'Début', type: 'datetime', required: true },
    { name: 'ends_at', label: 'Fin', type: 'datetime' },
    { name: 'description', label: 'Description', type: 'textarea' },
    { name: 'is_active', label: 'Actif', type: 'checkbox' },
  ]
  return (
    <>
      {editing && (
        <CrudForm
          title={editing === 'new' ? 'Nouveau code promo (offert par la plateforme)' : `Modifier ${editing.code}`}
          endpoint={`${BO}/coupons/`}
          initial={editing === 'new' ? { type: 'percent', per_user_limit: 1, min_amount: 0, starts_at: now } : editing}
          fields={fields}
          onDone={() => setEditing(null)}
        />
      )}
      <PageHeader title="Codes promo" subtitle="Les codes créés ici sont offerts par la plateforme ; ceux des boutiques apparaissent aussi.">
        <Button onClick={() => setEditing('new')}><Plus className="size-4" /> Nouveau code</Button>
      </PageHeader>
      <Toolbar searchPlaceholder="Code, description, boutique" filters={[{ key: 'is_active', label: 'État', options: [{ value: 'true', label: 'Actifs' }, { value: 'false', label: 'Inactifs' }] }]} />
      <ListView
        query={query}
        emptyIcon={<Tag />}
        emptyTitle="Aucun code promo"
        columns={[
          { label: 'Code', render: (c) => <><p className="font-mono font-bold">{c.code}</p><p className="text-xs text-ink/50">{c.description}</p></> },
          { label: 'Réduction', render: (c) => (c.type === 'percent' ? `-${c.value} %` : <>-<Price amount={c.value} /></>) },
          { label: 'Offert par', render: (c) => c.shop_name ?? <Badge tone="brand">Plateforme</Badge> },
          { label: 'Utilisé', render: (c) => `${c.used_count}${c.usage_limit ? ` / ${c.usage_limit}` : ''}`, className: 'text-right' },
          { label: 'Validité', render: (c) => <span className="text-xs">{formatDate(c.starts_at)}{c.ends_at ? ` → ${formatDate(c.ends_at)}` : ''}</span> },
          { label: 'État', render: (c) => c.is_active ? <Badge tone="success">Actif</Badge> : <Badge>Inactif</Badge> },
          { label: 'Actions', render: (c) => !c.shop_name && (
            <div className="flex gap-1">
              <Button size="sm" variant="ghost" onClick={() => setEditing(c)} aria-label="Modifier"><Pencil className="size-4" /></Button>
              <Button size="sm" variant="ghost" onClick={() => window.confirm(`Supprimer ${c.code} ?`) && remove.mutate(c.id)} aria-label="Supprimer"><Trash2 className="size-4" /></Button>
            </div>
          ) },
        ]}
      />
    </>
  )
}

// ---------------------------------------------------------------- Opérations commerciales

function ProductPicker({ selected, onChange }: { selected: string[]; onChange: (slugs: string[]) => void }) {
  const [filter, setFilter] = useState('')
  const { data: products = [] } = useQuery({
    queryKey: ['products', 'all-for-campaign'],
    queryFn: () => fetchAll<ProductSummary>('/products/'),
  })
  const shown = products.filter((p) => p.name.toLowerCase().includes(filter.toLowerCase()))
  return (
    <fieldset className="space-y-2">
      <legend className="text-sm font-semibold">Produits de l'opération ({selected.length})</legend>
      <input value={filter} onChange={(e) => setFilter(e.target.value)} placeholder="Filtrer…" className="w-full rounded-2xl border border-ink/10 px-4 py-2 text-sm" />
      <div className="max-h-48 space-y-1 overflow-y-auto rounded-2xl bg-background p-2">
        {shown.map((p) => (
          <label key={p.slug} className="flex items-center gap-2 rounded-lg px-2 py-1 text-sm hover:bg-ink/5">
            <input type="checkbox" checked={selected.includes(p.slug)} onChange={(e) => onChange(e.target.checked ? [...selected, p.slug] : selected.filter((s) => s !== p.slug))} className="accent-primary-dark" />
            <span className="flex-1">{p.name}</span>
            <Price amount={p.price} className="text-xs text-ink/50" />
          </label>
        ))}
        {!shown.length && <p className="p-2 text-sm text-ink/50">Aucun produit.</p>}
      </div>
    </fieldset>
  )
}

export function AdminCampaigns() {
  const { data: campaigns, isLoading } = useAdminAll<AdminCampaign>('campaigns')
  const remove = useAdminDelete('campaigns', 'Opération')
  const [editing, setEditing] = useState<AdminCampaign | 'new' | null>(null)
  const fields: Field[] = [
    { name: 'name', label: 'Nom', type: 'text', required: true, hint: 'Ex. Spécial Tabaski' },
    { name: 'color', label: 'Couleur de la bannière', type: 'color' },
    { name: 'starts_at', label: 'Début', type: 'datetime', required: true },
    { name: 'ends_at', label: 'Fin', type: 'datetime', required: true },
    { name: 'coupon_code', label: 'Code promo associé (facultatif)', type: 'text' },
    { name: 'description', label: 'Texte de la bannière', type: 'textarea' },
    { name: 'banner', label: 'Image de la bannière (facultatif)', type: 'image' },
    { name: 'is_active', label: 'Active', type: 'checkbox' },
  ]
  return (
    <>
      {editing && (
        <CrudForm
          title={editing === 'new' ? 'Nouvelle opération' : `Modifier « ${editing.name} »`}
          endpoint={`${BO}/campaigns/`}
          initial={editing === 'new' ? { color: '#ef6c00', product_slugs: [] } : { ...editing, coupon_code: editing.coupon_code ?? '' }}
          fields={fields}
          extraKeys={['product_slugs']}
          extra={(values, set) => <ProductPicker selected={(values.product_slugs as string[]) ?? []} onChange={(slugs) => set('product_slugs', slugs)} />}
          onDone={() => setEditing(null)}
        />
      )}
      <PageHeader title="Opérations commerciales" subtitle="Bannières de l'accueil (Tabaski, Ramadan, rentrée…), avec leurs produits et un code promo.">
        <Button onClick={() => setEditing('new')}><Plus className="size-4" /> Nouvelle opération</Button>
      </PageHeader>
      {isLoading ? <Spinner /> : !campaigns?.length ? <Empty icon={<Radio />} title="Aucune opération" /> : (
        <AdminTable
          rows={campaigns}
          columns={[
            { label: 'Opération', render: (c) => <div className="flex items-center gap-3"><span className="size-8 rounded-lg" style={{ backgroundColor: c.color }} /><span className="font-semibold">{c.name}</span></div> },
            { label: 'Période', render: (c) => <span className="text-xs">{formatDate(c.starts_at)} → {formatDate(c.ends_at)}</span> },
            { label: 'Produits', render: (c) => c.product_slugs.length, className: 'text-right' },
            { label: 'Code', render: (c) => c.coupon_code ?? '—' },
            { label: 'État', render: (c) => c.is_running ? <Badge tone="success">En cours</Badge> : c.is_active ? <Badge tone="info">Programmée / terminée</Badge> : <Badge>Inactive</Badge> },
            { label: 'Actions', render: (c) => (
              <div className="flex gap-1">
                <Button size="sm" variant="ghost" onClick={() => setEditing(c)} aria-label="Modifier"><Pencil className="size-4" /></Button>
                <Button size="sm" variant="ghost" onClick={() => window.confirm(`Supprimer « ${c.name} » ?`) && remove.mutate(c.id)} aria-label="Supprimer"><Trash2 className="size-4" /></Button>
              </div>
            ) },
          ]}
        />
      )}
    </>
  )
}

// ---------------------------------------------------------------- Livraison

export function AdminDelivery() {
  const zones = useAdminAll<AdminZone>('zones')
  const points = useAdminAll<AdminPickupPoint>('pickup-points')
  const removeZone = useAdminDelete('zones', 'Zone')
  const removePoint = useAdminDelete('pickup-points', 'Point relais')
  const [zone, setZone] = useState<AdminZone | 'new' | null>(null)
  const [point, setPoint] = useState<AdminPickupPoint | 'new' | null>(null)
  return (
    <div className="space-y-10">
      {zone && (
        <CrudForm
          title={zone === 'new' ? 'Nouvelle zone de livraison' : `Zone ${zone.commune || zone.city}`}
          endpoint={`${BO}/zones/`}
          initial={zone === 'new' ? { delay: '24 à 48 h' } : zone}
          onDone={() => setZone(null)}
          fields={[
            { name: 'city', label: 'Ville', type: 'text', required: true },
            { name: 'commune', label: 'Commune', type: 'text', hint: 'Vide = tarif pour toute la ville' },
            { name: 'fee', label: 'Frais de livraison (GNF)', type: 'amount', required: true },
            { name: 'delay', label: 'Délai indicatif', type: 'text', required: true },
            { name: 'is_active', label: 'Livraison ouverte', type: 'checkbox' },
          ]}
        />
      )}
      {point && (
        <CrudForm
          title={point === 'new' ? 'Nouveau point relais' : point.name}
          endpoint={`${BO}/pickup-points/`}
          initial={point === 'new' ? { city: 'Conakry', fee: 0, opening_hours: 'Lun-Sam, 8 h - 19 h' } : point}
          onDone={() => setPoint(null)}
          fields={[
            { name: 'name', label: 'Nom', type: 'text', required: true },
            { name: 'phone', label: 'Téléphone (9 chiffres)', type: 'text', required: true },
            { name: 'city', label: 'Ville', type: 'text', required: true },
            { name: 'commune', label: 'Commune', type: 'text' },
            { name: 'address', label: 'Adresse / repère', type: 'textarea', required: true },
            { name: 'opening_hours', label: 'Horaires', type: 'text' },
            { name: 'fee', label: 'Frais (GNF)', type: 'amount' },
            { name: 'is_active', label: 'Ouvert', type: 'checkbox' },
          ]}
        />
      )}
      <section>
        <PageHeader title="Zones et tarifs de livraison" subtitle="Les villes et communes proposées aux clients et aux livreurs viennent de cette liste.">
          <Button onClick={() => setZone('new')}><Plus className="size-4" /> Nouvelle zone</Button>
        </PageHeader>
        {zones.isLoading ? <Spinner /> : !zones.data?.length ? <Empty icon={<MapPin />} title="Aucune zone : la livraison est fermée" /> : (
          <AdminTable
            rows={zones.data}
            columns={[
              { label: 'Ville', render: (z) => <span className="font-semibold">{z.city}</span> },
              { label: 'Commune', render: (z) => z.commune || <span className="text-ink/50">toute la ville</span> },
              { label: 'Frais', render: (z) => <Price amount={z.fee} />, className: 'text-right' },
              { label: 'Délai', render: (z) => z.delay },
              { label: 'État', render: (z) => z.is_active ? <Badge tone="success">Ouverte</Badge> : <Badge>Fermée</Badge> },
              { label: 'Actions', render: (z) => (
                <div className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => setZone(z)} aria-label="Modifier"><Pencil className="size-4" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => window.confirm(`Supprimer la zone ${z.commune || z.city} ?`) && removeZone.mutate(z.id)} aria-label="Supprimer"><Trash2 className="size-4" /></Button>
                </div>
              ) },
            ]}
          />
        )}
      </section>
      <section>
        <PageHeader title="Points relais" subtitle="Lieux où les clients récupèrent leur colis (moins cher que la livraison).">
          <Button onClick={() => setPoint('new')}><Plus className="size-4" /> Nouveau point relais</Button>
        </PageHeader>
        {points.isLoading ? <Spinner /> : !points.data?.length ? <Empty icon={<Store />} title="Aucun point relais" /> : (
          <AdminTable
            rows={points.data}
            columns={[
              { label: 'Point relais', render: (p) => <><p className="font-semibold">{p.name}</p><p className="text-xs text-ink/50">{p.address}</p></> },
              { label: 'Lieu', render: (p) => `${p.commune ? `${p.commune}, ` : ''}${p.city}` },
              { label: 'Contact', render: (p) => <>{formatPhone(p.phone)}<p className="text-xs text-ink/50">{p.opening_hours}</p></> },
              { label: 'Frais', render: (p) => <Price amount={p.fee} />, className: 'text-right' },
              { label: 'État', render: (p) => p.is_active ? <Badge tone="success">Ouvert</Badge> : <Badge>Fermé</Badge> },
              { label: 'Actions', render: (p) => (
                <div className="flex gap-1">
                  <Button size="sm" variant="ghost" onClick={() => setPoint(p)} aria-label="Modifier"><Pencil className="size-4" /></Button>
                  <Button size="sm" variant="ghost" onClick={() => window.confirm(`Supprimer ${p.name} ?`) && removePoint.mutate(p.id)} aria-label="Supprimer"><Trash2 className="size-4" /></Button>
                </div>
              ) },
            ]}
          />
        )}
      </section>
    </div>
  )
}

// ---------------------------------------------------------------- Réglages du site

const SETTINGS_SECTIONS: { title: string; fields: Field[] }[] = [
  { title: 'Identité', fields: [
    { name: 'site_name', label: 'Nom du site', type: 'text', required: true },
    { name: 'tagline', label: 'Slogan', type: 'text' },
    { name: 'announcement', label: "Bandeau d'annonce (vide = masqué)", type: 'text' },
  ] },
  { title: "Page d'accueil", fields: [
    { name: 'hero_title', label: 'Titre', type: 'text', required: true },
    { name: 'hero_subtitle', label: 'Texte', type: 'textarea' },
    { name: 'hero_image', label: 'Image de fond (facultatif)', type: 'image' },
  ] },
  { title: 'Contact', fields: [
    { name: 'contact_phone', label: 'Téléphone (9 chiffres)', type: 'text' },
    { name: 'contact_whatsapp', label: 'WhatsApp du service client (9 chiffres)', type: 'text' },
    { name: 'contact_email', label: 'Email', type: 'text' },
    { name: 'address', label: 'Adresse', type: 'text' },
  ] },
  { title: 'Réseaux sociaux', fields: [
    { name: 'facebook_url', label: 'Facebook', type: 'text' },
    { name: 'instagram_url', label: 'Instagram', type: 'text' },
    { name: 'tiktok_url', label: 'TikTok', type: 'text' },
  ] },
  { title: 'Modes de paiement proposés', fields: [
    { name: 'enable_orange_money', label: 'Orange Money', type: 'checkbox' },
    { name: 'enable_mtn_momo', label: 'MTN Mobile Money', type: 'checkbox' },
    { name: 'enable_cash_on_delivery', label: 'Paiement à la livraison', type: 'checkbox' },
    { name: 'enable_card', label: 'Carte bancaire (diaspora)', type: 'checkbox' },
  ] },
]

export function AdminSettings() {
  const queryClient = useQueryClient()
  const toast = useToast()
  const { data: site, isLoading } = useQuery({
    queryKey: ['backoffice', 'settings'],
    queryFn: async () => (await api.get<Record<string, unknown>>(`${BO}/settings/`)).data,
  })
  const [values, setValues] = useState<Record<string, unknown> | null>(null)
  const [heroImage, setHeroImage] = useState<File | null>(null)
  const current = values ?? site
  const save = useMutation({
    mutationFn: async () => {
      const data = new FormData()
      for (const section of SETTINGS_SECTIONS) {
        for (const f of section.fields) {
          if (f.type === 'image') continue
          data.append(f.name, String(current?.[f.name] ?? (f.type === 'checkbox' ? false : '')))
        }
      }
      if (heroImage) data.append('hero_image', heroImage)
      return api.patch(`${BO}/settings/`, data)
    },
    onSuccess: () => {
      toast('Réglages enregistrés : le site est à jour')
      setValues(null)
      setHeroImage(null)
      queryClient.invalidateQueries({ queryKey: ['backoffice', 'settings'] })
      queryClient.invalidateQueries({ queryKey: ['config'] })
    },
  })
  if (isLoading || !current) return <Spinner />
  const set = (name: string, value: unknown) => setValues({ ...current, [name]: value })

  return (
    <form onSubmit={(e) => { e.preventDefault(); save.mutate() }} className="space-y-6">
      <PageHeader title="Réglages du site" subtitle="Textes, contacts et paiements affichés partout sur le site.">
        <Button type="submit" loading={save.isPending} disabled={!values && !heroImage}>Enregistrer</Button>
      </PageHeader>
      {SETTINGS_SECTIONS.map((section) => (
        <section key={section.title} className="card space-y-4 p-6">
          <h2 className="font-bold">{section.title}</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            {section.fields.map((f) => f.type === 'checkbox' ? (
              <label key={f.name} className="flex items-center gap-3 rounded-2xl bg-background p-3 text-sm font-semibold">
                <input type="checkbox" checked={!!current[f.name]} onChange={(e) => set(f.name, e.target.checked)} className="size-5 accent-primary-dark" />
                {f.label}
              </label>
            ) : f.type === 'image' ? (
              <label key={f.name} className="block space-y-1.5 sm:col-span-2">
                <span className="text-sm font-semibold">{f.label}</span>
                {typeof current[f.name] === 'string' && !!current[f.name] && !heroImage && <img src={String(current[f.name])} alt="" className="h-28 rounded-2xl object-cover" />}
                <input type="file" accept="image/*" onChange={(e) => setHeroImage(e.target.files?.[0] ?? null)} className="block text-sm" />
              </label>
            ) : (
              <label key={f.name} className={`block space-y-1.5 ${f.type === 'textarea' ? 'sm:col-span-2' : ''}`}>
                <span className="text-sm font-semibold">{f.label}</span>
                {f.type === 'textarea'
                  ? <textarea rows={3} value={String(current[f.name] ?? '')} onChange={(e) => set(f.name, e.target.value)} className="w-full rounded-2xl border border-ink/10 px-4 py-3" />
                  : <input value={String(current[f.name] ?? '')} onChange={(e) => set(f.name, e.target.value)} required={f.required} className="w-full rounded-2xl border border-ink/10 px-4 py-3" />}
              </label>
            ))}
          </div>
        </section>
      ))}
      <ErrorBox message={save.error ? errorMessage(save.error) : null} />
    </form>
  )
}

// ---------------------------------------------------------------- Journal

export function AdminAudit() {
  const query = useAdminList<AuditEntry>('audit', ['action'])
  return (
    <>
      <PageHeader title="Journal d'audit" subtitle="Qui a fait quoi : argent, validations, sécurité. Rien ne peut y être modifié ni supprimé." />
      <Toolbar searchPlaceholder="Action, objet, personne, adresse IP" />
      <ListView
        query={query}
        emptyIcon={<ScrollText />}
        emptyTitle="Journal vide"
        columns={[
          { label: 'Date', render: (a) => <span className="whitespace-nowrap">{formatDate(a.created_at)}</span> },
          { label: 'Qui', render: (a) => a.actor ?? <span className="text-ink/50">système</span> },
          { label: 'Action', render: (a) => <code className="rounded bg-ink/5 px-1.5 py-0.5 text-xs">{a.action}</code> },
          { label: 'Objet', render: (a) => a.target },
          { label: 'Détails', render: (a) => Object.keys(a.data).length ? <span className="text-xs text-ink/60">{Object.entries(a.data).map(([k, v]) => `${k} : ${Array.isArray(v) ? v.join(', ') : String(v)}`).join(' · ')}</span> : '' },
          { label: 'IP', render: (a) => <span className="text-xs text-ink/50">{a.ip}</span> },
        ]}
      />
    </>
  )
}
