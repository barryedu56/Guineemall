import { useMutation, useQueryClient } from '@tanstack/react-query'
import { FolderTree, ImageOff, MessageSquareQuote, Package, Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'

import Price from '../../components/Price'
import Stars from '../../components/Stars'
import { Badge, Button, Empty, PageHeader, Spinner } from '../../components/ui'
import { useToast } from '../../context/toast'
import { api, errorMessage, formatDate } from '../../lib/api'
import { useCategories } from '../../lib/queries'
import { BO, useAdminAll, useAdminDelete, useAdminList } from './hooks'
import { AdminTable, CrudForm, ListView, Toolbar } from './kit'
import type { AdminCategory, AdminProduct, AdminReview } from './types'

const VISIBILITY = [{ value: 'active', label: 'Visibles' }, { value: 'inactive', label: 'Masqués' }]

/** PATCH d'un champ (masquer un produit, un avis…) avec journalisation côté serveur. */
function usePatch(resource: string) {
  const queryClient = useQueryClient()
  const toast = useToast()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: object; success: string }) => api.patch(`${BO}/${resource}/${id}/`, data),
    onSuccess: (_, { success }) => {
      toast(success)
      queryClient.invalidateQueries({ queryKey: ['backoffice'] })
      queryClient.invalidateQueries({ queryKey: ['products'] })
    },
    onError: (e) => toast(errorMessage(e), 'error'),
  })
}

export function AdminProducts() {
  const { data: categories = [] } = useCategories()
  const query = useAdminList<AdminProduct>('products', ['status', 'category__slug'])
  const patch = usePatch('products')
  return (
    <>
      <PageHeader title="Produits" subtitle="Modération : masquez un produit interdit ou trompeur. Le contenu reste géré par la boutique." />
      <Toolbar
        searchPlaceholder="Produit ou boutique"
        filters={[
          { key: 'status', label: 'Visibilité', options: VISIBILITY },
          { key: 'category__slug', label: 'Catégorie', options: categories.map((c) => ({ value: c.slug, label: c.name })) },
        ]}
      />
      <ListView
        query={query}
        emptyIcon={<Package />}
        emptyTitle="Aucun produit"
        columns={[
          { label: 'Produit', render: (p) => (
            <div className="flex items-center gap-3">
              <span className="relative size-12 shrink-0 overflow-hidden rounded-xl bg-ink/5">
                {p.image ? <img src={p.image} alt="" className="size-full object-cover" /> : <ImageOff className="absolute inset-0 m-auto size-5 text-ink/20" />}
              </span>
              <div><Link to={`/produit/${p.slug}`} target="_blank" className="font-semibold hover:underline">{p.name}</Link><p className="text-xs text-ink/50">{p.category_name}</p></div>
            </div>
          ) },
          { label: 'Boutique', render: (p) => p.shop_name },
          { label: 'Prix', render: (p) => <Price amount={p.price} />, className: 'text-right' },
          { label: 'Stock', render: (p) => p.stock, className: 'text-right' },
          { label: 'Visibilité', render: (p) => p.status === 'active' ? <Badge tone="success">Visible</Badge> : <Badge>Masqué</Badge> },
          { label: 'Action', render: (p) => p.status === 'active'
            ? <Button size="sm" variant="ghost" onClick={() => window.confirm(`Masquer « ${p.name} » pour tous les clients ?`) && patch.mutate({ id: p.id, data: { status: 'inactive' }, success: 'Produit masqué' })}>Masquer</Button>
            : <Button size="sm" variant="outline" onClick={() => patch.mutate({ id: p.id, data: { status: 'active' }, success: 'Produit de nouveau visible' })}>Rendre visible</Button> },
        ]}
      />
    </>
  )
}

export function AdminCategories() {
  const { data: categories, isLoading } = useAdminAll<AdminCategory>('categories')
  const remove = useAdminDelete('categories', 'Catégorie')
  const [editing, setEditing] = useState<AdminCategory | 'new' | null>(null)
  const parents = (categories ?? []).filter((c) => editing === 'new' || c.id !== (editing as AdminCategory | null)?.id).map((c) => ({ value: String(c.id), label: c.name }))
  return (
    <>
      {editing && (
        <CrudForm
          title={editing === 'new' ? 'Nouvelle catégorie' : `Modifier « ${editing.name} »`}
          endpoint={`${BO}/categories/`}
          initial={editing === 'new' ? null : { ...editing, parent: editing.parent ? String(editing.parent) : '' }}
          onDone={() => setEditing(null)}
          fields={[
            { name: 'name', label: 'Nom', type: 'text', required: true },
            { name: 'parent', label: 'Catégorie parente', type: 'select', options: parents },
            { name: 'description', label: 'Description', type: 'textarea' },
            { name: 'image', label: 'Image (affichée sur l\'accueil)', type: 'image' },
            { name: 'status', label: 'État', type: 'select', required: true, options: [{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Désactivée' }] },
          ]}
        />
      )}
      <PageHeader title="Catégories" subtitle="Les catégories vides ne s'affichent pas aux clients.">
        <Button onClick={() => setEditing('new')}><Plus className="size-4" /> Nouvelle catégorie</Button>
      </PageHeader>
      {isLoading ? <Spinner /> : !categories?.length ? <Empty icon={<FolderTree />} title="Aucune catégorie" /> : (
        <AdminTable
          rows={categories}
          columns={[
            { label: 'Catégorie', render: (c) => (
              <div className="flex items-center gap-3">
                {c.image ? <img src={c.image} alt="" className="size-10 rounded-xl object-cover" /> : <span className="flex size-10 items-center justify-center rounded-xl bg-primary-light font-bold text-primary-dark">{c.name[0]}</span>}
                <div><p className="font-semibold">{c.name}</p>{c.parent && <p className="text-xs text-ink/50">dans {categories.find((p) => p.id === c.parent)?.name}</p>}</div>
              </div>
            ) },
            { label: 'Produits', render: (c) => c.products_count, className: 'text-right' },
            { label: 'État', render: (c) => c.status === 'active' ? <Badge tone="success">Active</Badge> : <Badge>Désactivée</Badge> },
            { label: 'Actions', render: (c) => (
              <div className="flex gap-1">
                <Button size="sm" variant="ghost" onClick={() => setEditing(c)} aria-label="Modifier"><Pencil className="size-4" /></Button>
                <Button size="sm" variant="ghost" disabled={c.products_count > 0} title={c.products_count ? 'Contient des produits : désactivez-la plutôt' : 'Supprimer'} onClick={() => window.confirm(`Supprimer « ${c.name} » ?`) && remove.mutate(c.id)} aria-label="Supprimer"><Trash2 className="size-4" /></Button>
              </div>
            ) },
          ]}
        />
      )}
    </>
  )
}

export function AdminReviews() {
  const query = useAdminList<AdminReview>('reviews', ['is_visible', 'rating'])
  const patch = usePatch('reviews')
  return (
    <>
      <PageHeader title="Avis clients" subtitle="Masquez un avis injurieux ou hors sujet (il reste enregistré)." />
      <Toolbar
        searchPlaceholder="Texte, produit, auteur"
        filters={[
          { key: 'is_visible', label: 'Visibilité', options: [{ value: 'true', label: 'Visibles' }, { value: 'false', label: 'Masqués' }] },
          { key: 'rating', label: 'Note', options: [1, 2, 3, 4, 5].map((n) => ({ value: String(n), label: `${n} ★` })) },
        ]}
      />
      <ListView
        query={query}
        emptyIcon={<MessageSquareQuote />}
        emptyTitle="Aucun avis"
        columns={[
          { label: 'Date', render: (r) => formatDate(r.created_at) },
          { label: 'Produit', render: (r) => <Link to={`/produit/${r.product_slug}`} target="_blank" className="font-medium hover:underline">{r.product_name}</Link> },
          { label: 'Avis', render: (r) => <><Stars rating={r.rating} /><p className="max-w-md text-ink/80">{r.comment || <span className="text-ink/40">(sans commentaire)</span>}</p><p className="text-xs text-ink/50">par {r.author}</p></> },
          { label: 'Visibilité', render: (r) => r.is_visible ? <Badge tone="success">Visible</Badge> : <Badge>Masqué</Badge> },
          { label: 'Action', render: (r) => r.is_visible
            ? <Button size="sm" variant="ghost" onClick={() => patch.mutate({ id: r.id, data: { is_visible: false }, success: 'Avis masqué' })}>Masquer</Button>
            : <Button size="sm" variant="outline" onClick={() => patch.mutate({ id: r.id, data: { is_visible: true }, success: 'Avis de nouveau visible' })}>Afficher</Button> },
        ]}
      />
    </>
  )
}
