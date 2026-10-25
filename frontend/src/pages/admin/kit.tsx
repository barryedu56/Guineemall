/**
 * Boîte à outils du back-office : listes paginées filtrables (état dans l'adresse),
 * tableau, actions avec confirmation, fenêtre de saisie (motif, référence) et formulaire générique.
 */
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Search, X } from 'lucide-react'
import { useState, type ReactNode } from 'react'

import { AmountInput, Button, Empty, ErrorBox, Input, Overlay, Pager, Select, Spinner, Textarea } from '../../components/ui'
import { useToast } from '../../context/toast'
import { api, errorMessage } from '../../lib/api'
import type { Choice } from '../../lib/types'
import { useAdminList, useUrlParam } from './hooks'

// ---------------------------------------------------------------- Listes

export function Toolbar({ searchPlaceholder, filters = [], children }: {
  searchPlaceholder?: string
  filters?: { key: string; label: string; options: Choice[] }[]
  children?: ReactNode
}) {
  const [search, setSearch] = useUrlParam('search')
  const [draft, setDraft] = useState(search)
  return (
    <div className="mb-4 flex flex-wrap items-center gap-2">
      {searchPlaceholder && (
        <form onSubmit={(e) => { e.preventDefault(); setSearch(draft.trim()) }} className="relative min-w-60 flex-1 sm:max-w-sm">
          <Search className="absolute left-4 top-1/2 size-4 -translate-y-1/2 text-ink/40" />
          <input
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            onBlur={() => draft !== search && setSearch(draft.trim())}
            placeholder={searchPlaceholder}
            className="w-full rounded-full bg-surface py-2.5 pl-10 pr-9 shadow-sm outline-none focus:ring-4 focus:ring-primary/25"
          />
          {search && (
            <button type="button" onClick={() => { setDraft(''); setSearch('') }} className="absolute right-3 top-1/2 -translate-y-1/2 text-ink/40" aria-label="Effacer">
              <X className="size-4" />
            </button>
          )}
        </form>
      )}
      {filters.map((f) => <FilterSelect key={f.key} filterKey={f.key} label={f.label} options={f.options} />)}
      <div className="ml-auto flex gap-2">{children}</div>
    </div>
  )
}

function FilterSelect({ filterKey, label, options }: { filterKey: string; label: string; options: Choice[] }) {
  const [value, setValue] = useUrlParam(filterKey)
  return (
    <select value={value} onChange={(e) => setValue(e.target.value)} className="rounded-full border border-ink/10 bg-surface px-4 py-2.5 text-sm shadow-sm" aria-label={label}>
      <option value="">{label} : tous</option>
      {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
    </select>
  )
}

export type Column<T> = { label: string; render: (row: T) => ReactNode; className?: string }

export function AdminTable<T extends { id: number }>({ columns, rows, onRowClick }: {
  columns: Column<T>[]
  rows: T[]
  onRowClick?: (row: T) => void
}) {
  return (
    <div className="card overflow-x-auto">
      <table className="w-full min-w-[640px] text-sm">
        <thead className="border-b border-ink/5 text-left text-xs uppercase tracking-wider text-ink/45">
          <tr>{columns.map((c) => <th key={c.label} className={`px-4 py-3 font-semibold ${c.className ?? ''}`}>{c.label}</th>)}</tr>
        </thead>
        <tbody className="divide-y divide-ink/5">
          {rows.map((row) => (
            <tr key={row.id} onClick={() => onRowClick?.(row)} className={onRowClick ? 'cursor-pointer hover:bg-ink/[0.02]' : ''}>
              {columns.map((c) => <td key={c.label} className={`px-4 py-3 align-middle ${c.className ?? ''}`}>{c.render(row)}</td>)}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

/** Liste complète : chargement, vide, tableau, pagination. */
export function ListView<T extends { id: number }>({ query, columns, emptyIcon, emptyTitle, onRowClick }: {
  query: ReturnType<typeof useAdminList<T>>
  columns: Column<T>[]
  emptyIcon: ReactNode
  emptyTitle: string
  onRowClick?: (row: T) => void
}) {
  const [, setPage] = useUrlParam('page')
  if (query.isLoading) return <Spinner />
  if (!query.data?.results.length) return <Empty icon={emptyIcon} title={emptyTitle} />
  return (
    <>
      <p className="mb-2 text-sm text-ink/55">{query.data.count} résultat{query.data.count > 1 ? 's' : ''}</p>
      <AdminTable columns={columns} rows={query.data.results} onRowClick={onRowClick} />
      <Pager page={query.data.page} totalPages={query.data.total_pages} onChange={(p) => setPage(String(p))} />
    </>
  )
}

// ---------------------------------------------------------------- Formulaire générique

export type Field = {
  name: string
  label: string
  type: 'text' | 'textarea' | 'number' | 'amount' | 'select' | 'checkbox' | 'image' | 'datetime' | 'color'
  options?: Choice[]
  hint?: string
  required?: boolean
}

type Values = Record<string, unknown>

function toLocalInput(value: unknown) {
  if (!value) return ''
  const d = new Date(String(value))
  return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16)
}

/**
 * Création / modification d'un objet. Envoie toujours TOUS les champs (une case décochée
 * vaut « faux », jamais « absente ») ; FormData seulement s'il y a une image à envoyer.
 */
export function CrudForm({ title, fields, initial: initialObject, endpoint, onDone, extra, extraKeys = [] }: {
  title: string
  fields: Field[]
  initial?: object | null
  endpoint: string
  onDone: () => void
  /** Champs spéciaux rendus par la page (ex. choix des produits), envoyés sous les noms `extraKeys` */
  extra?: (values: Values, set: (name: string, value: unknown) => void) => ReactNode
  extraKeys?: string[]
}) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const initial = initialObject as Values | null | undefined
  const [values, setValues] = useState<Values>(() => {
    const v: Values = {}
    for (const f of fields) {
      const raw = initial?.[f.name]
      v[f.name] =
        f.type === 'checkbox' ? (raw ?? true)
        : f.type === 'datetime' ? toLocalInput(raw)
        : f.type === 'image' ? null
        // Liste obligatoire sans valeur : la 1re option affichée est aussi la valeur réellement envoyée
        : f.type === 'select' && f.required && (raw === undefined || raw === null || raw === '') ? (f.options?.[0]?.value ?? '')
        : (raw ?? '')
    }
    return { ...initial, ...v }
  })
  const set = (name: string, value: unknown) => setValues((v) => ({ ...v, [name]: value }))

  const save = useMutation({
    mutationFn: async () => {
      const payload: Values = { ...values }
      for (const f of fields) {
        if (f.type === 'datetime') payload[f.name] = values[f.name] ? new Date(String(values[f.name])).toISOString() : null
        if ((f.type === 'number' || f.type === 'amount') && values[f.name] === '') payload[f.name] = null
      }
      const file = fields.find((f) => f.type === 'image' && values[f.name] instanceof File)
      const keys = [...fields.filter((f) => f.type !== 'image').map((f) => f.name), ...extraKeys]
      let body: unknown = Object.fromEntries(keys.map((k) => [k, payload[k]]))
      if (file) {
        const data = new FormData()
        for (const [k, v] of Object.entries(body as Values)) {
          if (Array.isArray(v)) v.forEach((x) => data.append(k, String(x)))
          else if (v !== null && v !== undefined) data.append(k, String(v))
        }
        data.append(file.name, values[file.name] as File)
        body = data
      }
      const id = initial?.id
      return id ? api.patch(`${endpoint}${id}/`, body) : api.post(endpoint, body)
    },
    onSuccess: () => {
      toast(initial?.id ? 'Modifications enregistrées' : 'Créé avec succès')
      queryClient.invalidateQueries({ queryKey: ['backoffice'] })
      queryClient.invalidateQueries({ queryKey: ['config'] })
      onDone()
    },
  })

  return (
    <Overlay>
      <div className="fixed inset-0 z-[80] flex items-end justify-center bg-ink/40 p-0 sm:items-center sm:p-4" role="dialog" aria-modal>
        <form
          onSubmit={(e) => { e.preventDefault(); save.mutate() }}
          className="card max-h-[92dvh] w-full max-w-2xl animate-fade-up space-y-4 overflow-y-auto rounded-b-none p-6 sm:rounded-b-3xl"
        >
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-extrabold">{title}</h2>
            <button type="button" onClick={onDone} className="rounded-full p-2 hover:bg-ink/5" aria-label="Fermer"><X className="size-5" /></button>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            {fields.map((f) => {
              const value = values[f.name]
              const wide = f.type === 'textarea' || f.type === 'image' ? 'sm:col-span-2' : ''
              if (f.type === 'checkbox') {
                return (
                  <label key={f.name} className={`flex items-center gap-3 rounded-2xl bg-background p-3 text-sm font-semibold ${wide}`}>
                    <input type="checkbox" checked={!!value} onChange={(e) => set(f.name, e.target.checked)} className="size-5 accent-primary-dark" />
                    {f.label}
                  </label>
                )
              }
              const common = { label: f.label, hint: f.hint, required: f.required }
              return (
                <div key={f.name} className={wide}>
                  {f.type === 'textarea' ? <Textarea {...common} value={String(value ?? '')} onChange={(e) => set(f.name, e.target.value)} />
                    : f.type === 'select' ? (
                      <Select label={f.label} value={String(value ?? '')} onChange={(e) => set(f.name, e.target.value)} required={f.required}>
                        {!f.required && <option value="">—</option>}
                        {f.options?.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                      </Select>
                    )
                    : f.type === 'amount' ? <AmountInput {...common} value={String(value ?? '')} onValueChange={(v) => set(f.name, v)} />
                    : f.type === 'image' ? (
                      <label className="block space-y-1.5">
                        <span className="text-sm font-semibold">{f.label}</span>
                        {typeof initial?.[f.name] === 'string' && !value && <img src={String(initial[f.name])} alt="" className="h-24 rounded-xl object-cover" />}
                        <input type="file" accept="image/*" onChange={(e) => set(f.name, e.target.files?.[0] ?? null)} className="block w-full text-sm" />
                        {f.hint && <span className="block text-xs text-ink/55">{f.hint}</span>}
                      </label>
                    )
                    : <Input {...common} type={f.type === 'datetime' ? 'datetime-local' : f.type} value={String(value ?? '')} onChange={(e) => set(f.name, e.target.value)} />}
                </div>
              )
            })}
          </div>
          {extra?.(values, set)}
          <ErrorBox message={save.error ? errorMessage(save.error) : null} />
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={onDone}>Annuler</Button>
            <Button type="submit" loading={save.isPending}>Enregistrer</Button>
          </div>
        </form>
      </div>
    </Overlay>
  )
}

