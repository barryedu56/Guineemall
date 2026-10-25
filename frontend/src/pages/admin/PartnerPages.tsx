import { BadgeCheck, Bike, Crown, Eye, Star, Store, Users, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'

import Price from '../../components/Price'
import { ShopAvatar } from '../../components/Statuts'
import { Badge, Button, Overlay, PageHeader, StatusBadge } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useSiteConfig } from '../../context/settings'
import { useToast } from '../../context/toast'
import { api, errorMessage, formatDate, formatPhone } from '../../lib/api'
import { BO, useAdminAction, useAdminList } from './hooks'
import { CrudForm, ListView, Toolbar } from './kit'
import { useAsk } from './useAsk'
import type { AdminRider, AdminUser, AdminVendor, AdminVerification } from './types'

const VENDOR_TONE: Record<string, string> = { approved: 'delivered', pending: 'pending', suspended: 'cancelled' }

export function AdminVendors() {
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminVendor>('vendors', ['status', 'is_verified', 'is_premium'])
  const action = useAdminAction('vendors')
  const [ask, dialog] = useAsk()
  const [editing, setEditing] = useState<AdminVendor | null>(null)

  async function suspend(v: AdminVendor) {
    const reason = await ask({ title: `Suspendre ${v.shop_name}`, label: 'Motif (envoyé à la boutique)', confirm: 'Suspendre', danger: true, required: true })
    if (reason) action.mutate({ id: v.id, action: 'suspend', body: { reason }, success: 'Boutique suspendue' })
  }
  async function revoke(v: AdminVendor) {
    const reason = await ask({ title: `Retirer le badge de ${v.shop_name}`, label: 'Motif (envoyé à la boutique)', confirm: 'Retirer le badge', danger: true, required: true })
    if (reason) action.mutate({ id: v.id, action: 'revoke-badge', body: { reason }, success: 'Badge retiré' })
  }

  return (
    <>
      {dialog}
      {editing && (
        <CrudForm
          title={`Conditions de ${editing.shop_name}`}
          endpoint={`${BO}/vendors/`}
          initial={{ ...editing, commission_rate: editing.commission_rate ?? '' }}
          onDone={() => setEditing(null)}
          fields={[
            { name: 'is_premium', label: 'Boutique premium (mise en avant)', type: 'checkbox' },
            { name: 'commission_rate', label: 'Commission propre (0,05 = 5 %)', type: 'text', hint: `Vide = taux de la plateforme (${Math.round((config?.commission_rate ?? 0) * 100)} %)` },
          ]}
        />
      )}
      <PageHeader title="Boutiques" subtitle="Approuver les nouvelles boutiques, suspendre, fixer les conditions." />
      <Toolbar
        searchPlaceholder="Boutique, propriétaire, email, téléphone"
        filters={[
          { key: 'status', label: 'Statut', options: config?.choices.vendor_status ?? [] },
          { key: 'is_verified', label: 'Badge', options: [{ value: 'true', label: 'Vérifiées' }, { value: 'false', label: 'Non vérifiées' }] },
        ]}
      />
      <ListView
        query={query}
        emptyIcon={<Store />}
        emptyTitle="Aucune boutique"
        columns={[
          { label: 'Boutique', render: (v) => (
            <div className="flex items-center gap-3">
              <ShopAvatar logo={v.logo} name={v.shop_name} className="size-10" />
              <div>
                <p className="flex items-center gap-1 font-semibold">{v.shop_name}{v.is_verified && <BadgeCheck className="size-4 text-primary-dark" />}{v.is_premium && <Crown className="size-4 text-primary-dark" />}</p>
                <p className="text-xs text-ink/50">{[v.owner.name, v.owner.email, formatPhone(v.owner.phone)].filter(Boolean).join(' · ')}</p>
              </div>
            </div>
          ) },
          { label: 'Produits', render: (v) => v.products_count, className: 'text-right' },
          { label: 'Ventes', render: (v) => <><Price amount={v.revenue ?? 0} /><p className="text-xs text-ink/50">{v.orders_count} commande(s)</p></>, className: 'text-right' },
          { label: 'Commission', render: (v) => v.commission_rate ? `${Math.round(Number(v.commission_rate) * 1000) / 10} %` : <span className="text-ink/50">par défaut</span> },
          { label: 'Statut', render: (v) => <StatusBadge status={VENDOR_TONE[v.status]} label={v.status_label} /> },
          { label: 'Actions', render: (v) => (
            <div className="flex flex-wrap gap-1.5">
              {v.status !== 'approved' && <Button size="sm" onClick={() => action.mutate({ id: v.id, action: 'approve', success: 'Boutique approuvée, propriétaire prévenu' })}>Approuver</Button>}
              {v.status === 'approved' && <Button size="sm" variant="ghost" onClick={() => suspend(v)}>Suspendre</Button>}
              {v.is_verified && <Button size="sm" variant="ghost" onClick={() => revoke(v)}>Retirer le badge</Button>}
              <Button size="sm" variant="outline" onClick={() => setEditing(v)}>Conditions</Button>
              {v.status === 'approved' && <Link to={`/boutique/${v.slug}`} target="_blank" className="self-center text-xs font-semibold text-primary-dark hover:underline">Voir</Link>}
            </div>
          ) },
        ]}
      />
    </>
  )
}

/** Pièce d'identité lue via l'API protégée (jamais d'adresse publique), affichée le temps de la consultation. */
function DocumentViewer({ request, onClose }: { request: AdminVerification; onClose: () => void }) {
  const toast = useToast()
  const [urls, setUrls] = useState<{ field: string; url: string }[]>([])
  useEffect(() => {
    let created: string[] = []
    Promise.all(request.documents.map(async (field) => {
      const { data } = await api.get(`${BO}/verifications/${request.id}/document/${field}/`, { responseType: 'blob' })
      const url = URL.createObjectURL(data)
      created.push(url)
      return { field, url }
    })).then(setUrls).catch((e) => toast(errorMessage(e), 'error'))
    return () => { created.forEach((u) => URL.revokeObjectURL(u)); created = [] }
  }, [request, toast])
  const labels: Record<string, string> = { document_front: 'Recto', document_back: 'Verso', shop_photo: 'Boutique' }
  return (
    <Overlay>
      <div className="fixed inset-0 z-[80] flex items-center justify-center bg-ink/80 p-4" role="dialog" aria-modal onClick={onClose}>
        <div className="card max-h-[92dvh] w-full max-w-4xl space-y-4 overflow-y-auto p-6" onClick={(e) => e.stopPropagation()}>
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold">{request.shop_name} · {request.document_type_label} n° {request.document_number}</h2>
            <button onClick={onClose} className="rounded-full p-2 hover:bg-ink/5" aria-label="Fermer"><X className="size-5" /></button>
          </div>
          <p className="text-xs text-ink/55">Document confidentiel : cette consultation est enregistrée dans le journal d'audit.</p>
          <div className="grid gap-4 sm:grid-cols-2">
            {urls.map(({ field, url }) => (
              <figure key={field}><img src={url} alt={labels[field]} className="w-full rounded-2xl border border-ink/10" /><figcaption className="mt-1 text-sm text-ink/60">{labels[field]}</figcaption></figure>
            ))}
          </div>
        </div>
      </div>
    </Overlay>
  )
}

export function AdminVerifications() {
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminVerification>('verifications', ['status'])
  const action = useAdminAction('verifications')
  const [ask, dialog] = useAsk()
  const [viewing, setViewing] = useState<AdminVerification | null>(null)

  async function reject(vr: AdminVerification) {
    const note = await ask({ title: `Refuser le badge de ${vr.shop_name}`, label: 'Motif (ex. « photo illisible », envoyé à la boutique)', confirm: 'Refuser', danger: true, required: true })
    if (note) action.mutate({ id: vr.id, action: 'reject', body: { note }, success: 'Demande refusée, boutique prévenue' })
  }

  return (
    <>
      {dialog}
      {viewing && <DocumentViewer request={viewing} onClose={() => setViewing(null)} />}
      <PageHeader title="Badges « Vendeur vérifié »" subtitle="Comparez la pièce d'identité au propriétaire avant d'accorder le badge." />
      <Toolbar searchPlaceholder="Boutique, n° de document" filters={[{ key: 'status', label: 'Statut', options: config?.choices.verification_status ?? [] }]} />
      <ListView
        query={query}
        emptyIcon={<BadgeCheck />}
        emptyTitle="Aucune demande"
        columns={[
          { label: 'Date', render: (vr) => formatDate(vr.created_at) },
          { label: 'Boutique', render: (vr) => <><p className="font-semibold">{vr.shop_name}</p><p className="text-xs text-ink/50">{vr.owner.name} · {formatPhone(vr.owner.phone)}</p></> },
          { label: 'Document', render: (vr) => <>{vr.document_type_label}<p className="text-xs text-ink/50">n° {vr.document_number}</p></> },
          { label: 'Statut', render: (vr) => <><StatusBadge status={vr.status === 'approved' ? 'delivered' : vr.status === 'rejected' ? 'cancelled' : 'pending'} label={vr.status_label} />{vr.admin_note && <p className="text-xs text-ink/50">{vr.admin_note}</p>}</> },
          { label: 'Actions', render: (vr) => (
            <div className="flex flex-wrap gap-1.5">
              <Button size="sm" variant="outline" onClick={() => setViewing(vr)}><Eye className="size-4" /> Documents</Button>
              {vr.status === 'pending' && <>
                <Button size="sm" onClick={() => action.mutate({ id: vr.id, action: 'approve', success: 'Badge accordé' })}>Accorder</Button>
                <Button size="sm" variant="ghost" onClick={() => reject(vr)}>Refuser</Button>
              </>}
            </div>
          ) },
        ]}
      />
    </>
  )
}

export function AdminRiders() {
  const query = useAdminList<AdminRider>('riders', ['is_approved', 'is_available'])
  const action = useAdminAction('riders')
  return (
    <>
      <PageHeader title="Livreurs" subtitle="Valider les candidatures, suivre les notes et les espèces non reversées." />
      <Toolbar
        searchPlaceholder="Nom, email, téléphone, plaque"
        filters={[{ key: 'is_approved', label: 'Validation', options: [{ value: 'false', label: 'À valider' }, { value: 'true', label: 'Validés' }] }]}
      />
      <ListView
        query={query}
        emptyIcon={<Bike />}
        emptyTitle="Aucun livreur"
        columns={[
          { label: 'Livreur', render: (r) => <><p className="font-semibold">{r.user.name}</p><p className="text-xs text-ink/50">{formatPhone(r.user.phone)} · {r.user.email}</p></> },
          { label: 'Zone', render: (r) => <>{r.city}<p className="text-xs text-ink/50">{r.communes || 'toute la ville'}</p></> },
          { label: 'Moto', render: (r) => r.vehicle_plate || '—' },
          { label: 'Courses', render: (r) => r.deliveries_done, className: 'text-right' },
          { label: 'Note', render: (r) => r.rating.rating ? <span className="flex items-center gap-1"><Star className="size-4 fill-primary text-primary" />{r.rating.rating} ({r.rating.ratings_count})</span> : '—' },
          { label: 'Espèces dues', render: (r) => <Price amount={r.cash_debt} className={r.cash_debt ? 'font-semibold text-red-700' : 'text-ink/50'} /> },
          { label: 'Statut', render: (r) => r.is_approved ? <Badge tone="success">Validé{r.is_available ? ' · dispo' : ''}</Badge> : <Badge tone="warning">À valider</Badge> },
          { label: 'Actions', render: (r) => r.is_approved
            ? <Button size="sm" variant="ghost" onClick={() => window.confirm(`Suspendre ${r.user.name} ?`) && action.mutate({ id: r.id, action: 'suspend', success: 'Livreur suspendu' })}>Suspendre</Button>
            : <Button size="sm" onClick={() => action.mutate({ id: r.id, action: 'approve', success: 'Livreur validé, il est prévenu' })}>Valider</Button> },
        ]}
      />
    </>
  )
}

export function AdminUsers() {
  const { user: me } = useAuth()
  const { data: config } = useSiteConfig()
  const query = useAdminList<AdminUser>('users', ['role', 'is_active'])
  const action = useAdminAction('users')
  return (
    <>
      <PageHeader title="Utilisateurs" subtitle="Tous les comptes : désactiver un compte abusif, réinitialiser une double authentification (téléphone perdu)." />
      <Toolbar
        searchPlaceholder="Nom, email, téléphone"
        filters={[
          { key: 'role', label: 'Rôle', options: config?.choices.user_role ?? [] },
          { key: 'is_active', label: 'État', options: [{ value: 'true', label: 'Actifs' }, { value: 'false', label: 'Désactivés' }] },
        ]}
      />
      <ListView
        query={query}
        emptyIcon={<Users />}
        emptyTitle="Aucun utilisateur"
        columns={[
          { label: 'Nom', render: (u) => <><p className="font-semibold">{u.name}</p><p className="text-xs text-ink/50">{u.email}</p></> },
          { label: 'Téléphone', render: (u) => formatPhone(u.phone) || '—' },
          { label: 'Rôle', render: (u) => <>{u.role_label}{u.shop && <p className="text-xs text-ink/50">{u.shop}</p>}</> },
          { label: 'Inscrit', render: (u) => formatDate(u.date_joined) },
          { label: 'Dernière connexion', render: (u) => (u.last_login ? formatDate(u.last_login) : '—') },
          { label: 'Sécurité', render: (u) => u.has_2fa ? <Badge tone="success">2FA</Badge> : <span className="text-xs text-ink/40">—</span> },
          { label: 'Actions', render: (u) => u.id === me?.id ? <span className="text-xs text-ink/40">vous</span> : (
            <div className="flex flex-wrap gap-1.5">
              {u.is_active
                ? <Button size="sm" variant="ghost" onClick={() => window.confirm(`Désactiver ${u.name} ? Il sera déconnecté de tous ses appareils.`) && action.mutate({ id: u.id, action: 'deactivate', success: 'Compte désactivé' })}>Désactiver</Button>
                : <Button size="sm" onClick={() => action.mutate({ id: u.id, action: 'activate', success: 'Compte réactivé' })}>Réactiver</Button>}
              {u.has_2fa && <Button size="sm" variant="ghost" onClick={() => window.confirm(`Réinitialiser la double authentification de ${u.name} ? À faire seulement après avoir vérifié son identité.`) && action.mutate({ id: u.id, action: 'reset-2fa', success: 'Double authentification réinitialisée' })}>Réinit. 2FA</Button>}
            </div>
          ) },
        ]}
      />
    </>
  )
}
