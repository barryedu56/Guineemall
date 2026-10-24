import { useQuery } from '@tanstack/react-query'
import { Copy, Gift, Share2, Users } from 'lucide-react'
import { useState, type ReactNode } from 'react'
import { Link, useParams } from 'react-router-dom'

import Price from '../components/Price'
import { ProductGrid } from '../components/ProductCard'
import { Button, Spinner } from '../components/ui'
import { api } from '../lib/api'
import { useCampaigns } from '../lib/queries'
import type { Campaign, Referral } from '../lib/types'

export function CampaignBanners() {
  const { data: campaigns = [] } = useCampaigns()
  if (!campaigns.length) return null
  return (
    <section className="mb-6 grid gap-3 sm:grid-cols-2">
      {campaigns.map((c) => (
        <Link
          key={c.id}
          to={`/promo/${c.slug}`}
          style={{ backgroundColor: c.color }}
          className="relative overflow-hidden rounded-3xl p-5 text-white shadow-sm"
        >
          {c.banner && <img src={c.banner} alt="" className="absolute inset-0 size-full object-cover opacity-40" />}
          <div className="relative">
            <p className="text-2xl font-extrabold">{c.name}</p>
            {c.description && <p className="mt-1 line-clamp-2 text-white/90">{c.description}</p>}
            {c.coupon_code && (
              <p className="mt-3 inline-block rounded-full bg-white px-3 py-1 text-sm font-bold text-ink">Code : {c.coupon_code}</p>
            )}
          </div>
        </Link>
      ))}
    </section>
  )
}

export function CampaignPage() {
  const { slug } = useParams()
  const { data: campaign, isLoading } = useQuery({
    queryKey: ['campaign', slug],
    queryFn: async () => (await api.get<Campaign>(`/campaigns/${slug}/`)).data,
  })
  if (isLoading) return <Spinner />
  if (!campaign) return <p className="py-16 text-center">Cette opération est terminée.</p>
  const end = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long' }).format(new Date(campaign.ends_at))

  return (
    <>
      <section style={{ backgroundColor: campaign.color }} className="mb-6 rounded-3xl p-6 text-white sm:p-10">
        <h1 className="text-3xl font-extrabold sm:text-4xl">{campaign.name}</h1>
        {campaign.description && <p className="mt-2 max-w-xl text-white/90">{campaign.description}</p>}
        <p className="mt-4 text-sm">Jusqu'au {end}</p>
        {campaign.coupon_code && (
          <p className="mt-3 inline-block rounded-full bg-white px-4 py-1.5 font-bold text-ink">
            Code promo : {campaign.coupon_code}
          </p>
        )}
      </section>
      <ProductGrid products={campaign.products ?? []} />
    </>
  )
}

export function ReferralPage() {
  const [copied, setCopied] = useState(false)
  const { data, isLoading } = useQuery({
    queryKey: ['referral'],
    queryFn: async () => (await api.get<Referral>('/auth/referral/')).data,
  })
  if (isLoading || !data) return <Spinner />

  const message = `Rejoins-moi sur GuinéeMall, le marché guinéen en ligne ! Avec mon code ${data.code}, tu reçois un bon de ${new Intl.NumberFormat('fr-FR').format(data.reward)} GNF : ${data.link}`

  async function share() {
    if (navigator.share) {
      await navigator.share({ title: 'GuinéeMall', text: message }).catch(() => {})
      return
    }
    window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank', 'noopener')
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <section className="rounded-3xl bg-gradient-to-br from-primary to-secondary p-6 text-center">
        <Gift className="mx-auto size-12" />
        <h1 className="mt-2 text-2xl font-extrabold">Invitez vos amis, gagnez des bons</h1>
        <p className="mt-2 text-ink/80">
          Votre ami reçoit <Price amount={data.reward} className="font-bold" /> à l'inscription, et vous aussi dès sa première commande livrée.
        </p>
        <div className="mx-auto mt-4 flex max-w-xs items-center justify-between rounded-2xl bg-white px-4 py-3">
          <span className="text-2xl font-extrabold tracking-widest">{data.code}</span>
          <button
            onClick={() => {
              navigator.clipboard?.writeText(data.link)
              setCopied(true)
              setTimeout(() => setCopied(false), 2000)
            }}
            className="flex items-center gap-1 text-sm font-medium text-primary-dark"
          >
            <Copy className="size-4" /> {copied ? 'Copié' : 'Copier le lien'}
          </button>
        </div>
        <Button className="mt-4 bg-ink text-white hover:bg-ink/90" onClick={share}>
          <Share2 className="size-5" /> Partager sur WhatsApp
        </Button>
      </section>

      <section className="grid gap-3 sm:grid-cols-3">
        <Stat icon={<Users />} value={String(data.referrals)} label="ami(s) inscrit(s)" />
        <Stat icon={<Share2 />} value={String(data.affiliate_orders)} label="commande(s) via vos liens" />
        <Stat icon={<Gift />} value={<Price amount={data.affiliate_earnings} />} label="gagnés comme ambassadeur" />
      </section>

      <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
        <h2 className="font-semibold">Programme ambassadeur</h2>
        <p className="mt-1 text-sm text-ink/70">
          Partagez n'importe quel produit avec le bouton « Partager » : vous touchez {Math.round(data.affiliate_rate * 100)} % de chaque
          vente faite grâce à votre lien, versés dans votre <Link to="/portefeuille" className="text-primary-dark underline">portefeuille</Link> et
          retirables sur Orange Money ou MTN.
        </p>
      </section>

      {data.coupons.length > 0 && (
        <section className="rounded-2xl bg-surface p-5 ring-1 ring-ink/5">
          <h2 className="mb-2 font-semibold">Mes bons de réduction</h2>
          <ul className="space-y-2">
            {data.coupons.map((c) => (
              <li key={c.code} className="flex items-center justify-between rounded-xl bg-primary-light/40 p-3">
                <span>
                  <span className="font-bold">{c.code}</span>
                  <span className="block text-xs text-ink/60">{c.description} · dès <Price amount={c.min_amount} /> d'achat</span>
                </span>
                <Price amount={c.value} className="font-bold text-primary-dark" />
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  )
}

function Stat({ icon, value, label }: { icon: ReactNode; value: ReactNode; label: string }) {
  return (
    <div className="rounded-2xl bg-surface p-4 text-center ring-1 ring-ink/5 [&>svg]:mx-auto [&>svg]:size-6 [&>svg]:text-primary-dark">
      {icon}
      <p className="mt-1 text-xl font-extrabold">{value}</p>
      <p className="text-sm text-ink/60">{label}</p>
    </div>
  )
}
