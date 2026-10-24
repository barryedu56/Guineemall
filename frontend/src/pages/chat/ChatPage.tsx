import { useInfiniteQuery, useQueryClient, type InfiniteData } from '@tanstack/react-query'
import { ArrowLeft, BadgeCheck, Check, CheckCheck, ImagePlus, Loader2, MessageCircle, Mic, Send, Square } from 'lucide-react'
import { useEffect, useRef, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import Price from '../../components/Price'
import { ShopAvatar } from '../../components/Statuts'
import { Empty, ErrorBox, Spinner } from '../../components/ui'
import { useAuth } from '../../context/auth'
import { useRealtime } from '../../context/realtime'
import { api, errorMessage, timeAgo } from '../../lib/api'
import type { Conversation, Message } from '../../lib/types'
import { useConversations } from './useConversations'

type MessagePage = { next: string | null; results: Message[] }

/** Messagerie client ↔ boutique. `base` = /messages (client) ou /vendeur/messages (vendeur). */
export default function ChatPage({ base }: { base: string }) {
  const { id } = useParams()
  const { data: conversations, isLoading } = useConversations()
  const current = conversations?.find((c) => String(c.id) === id)

  if (isLoading) return <Spinner />

  return (
    <div className="grid h-[calc(100dvh-14rem)] min-h-96 overflow-hidden card md:h-[calc(100dvh-10rem)] md:grid-cols-[320px_1fr]">
      <aside className={`overflow-y-auto border-r border-ink/5 ${id ? 'hidden md:block' : ''}`}>
        <h1 className="border-b border-ink/5 px-4 py-3 text-lg font-bold">Messages</h1>
        {!conversations?.length ? (
          <Empty icon={<MessageCircle />} title="Aucune conversation">
            <p className="px-4 text-sm text-ink/60">Écrivez à une boutique depuis la fiche d'un produit.</p>
          </Empty>
        ) : (
          conversations.map((c) => (
            <Link
              key={c.id}
              to={`${base}/${c.id}`}
              className={`flex items-center gap-3 border-b border-ink/5 px-4 py-3 hover:bg-ink/5 ${String(c.id) === id ? 'bg-primary-light/30' : ''}`}
            >
              <ShopAvatar logo={c.other.logo} name={c.other.name} className="size-11 shrink-0" />
              <div className="min-w-0 flex-1">
                <p className="flex items-center gap-1 truncate font-semibold">
                  {c.other.name}
                  {c.other.is_verified && <BadgeCheck className="size-4 text-primary-dark" />}
                </p>
                <p className="truncate text-sm text-ink/60">{c.last_message?.preview}</p>
              </div>
              {c.unread > 0 && (
                <span className="flex size-5 items-center justify-center rounded-full bg-primary-dark text-xs font-bold text-white">{c.unread}</span>
              )}
            </Link>
          ))
        )}
      </aside>
      {current ? (
        <Thread key={current.id} conversation={current} base={base} />
      ) : (
        <div className="hidden items-center justify-center text-ink/50 md:flex">Choisissez une conversation</div>
      )}
    </div>
  )
}

function Thread({ conversation, base }: { conversation: Conversation; base: string }) {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const bottom = useRef<HTMLDivElement>(null)
  const [typing, setTyping] = useState(false)
  const key = ['messages', conversation.id]

  const { data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage } = useInfiniteQuery({
    queryKey: key,
    initialPageParam: `/conversations/${conversation.id}/messages/`,
    queryFn: async ({ pageParam }) => (await api.get<MessagePage>(pageParam.replace(/^.*\/api/, ''))).data,
    getNextPageParam: (last) => last.next,
  })
  // Pages du plus récent au plus ancien → affichage chronologique
  const messages = data ? data.pages.flatMap((p) => p.results).reverse() : []

  useEffect(() => {
    api.post(`/conversations/${conversation.id}/read/`).then(() => queryClient.invalidateQueries({ queryKey: ['conversations'] }))
  }, [conversation.id, conversation.unread, queryClient])

  useEffect(() => {
    bottom.current?.scrollIntoView({ block: 'end' })
  }, [messages.length])

  useRealtime((event) => {
    if (!('conversation' in event) || event.conversation !== conversation.id) return
    if (event.type === 'chat.message') {
      setTyping(false)
      queryClient.setQueryData<InfiniteData<MessagePage>>(key, (old) => {
        if (!old || old.pages[0].results.some((m) => m.id === event.message.id)) return old
        const [first, ...rest] = old.pages
        return { ...old, pages: [{ ...first, results: [event.message, ...first.results] }, ...rest] }
      })
    }
    if (event.type === 'chat.typing') {
      setTyping(true)
      setTimeout(() => setTyping(false), 3000)
    }
    if (event.type === 'chat.read') queryClient.invalidateQueries({ queryKey: key })
  })

  return (
    <section className="flex min-h-0 flex-col">
      <header className="flex items-center gap-3 border-b border-ink/5 px-3 py-2.5">
        <button onClick={() => navigate(base)} className="rounded-full p-1 hover:bg-ink/5 md:hidden" aria-label="Retour">
          <ArrowLeft className="size-5" />
        </button>
        <ShopAvatar logo={conversation.other.logo} name={conversation.other.name} className="size-9" />
        <div>
          {conversation.other.slug ? (
            <Link to={`/boutique/${conversation.other.slug}`} className="font-semibold hover:underline">{conversation.other.name}</Link>
          ) : (
            <p className="font-semibold">{conversation.other.name}</p>
          )}
          {typing && <p className="text-xs text-primary-dark">en train d'écrire…</p>}
        </div>
      </header>

      <div className="flex-1 space-y-2 overflow-y-auto bg-background p-3">
        {hasNextPage && (
          <button onClick={() => fetchNextPage()} className="mx-auto block text-sm text-primary-dark hover:underline">
            {isFetchingNextPage ? 'Chargement…' : 'Messages précédents'}
          </button>
        )}
        {isLoading && <Spinner />}
        {messages.map((m) => (
          <Bubble key={m.id} message={m} mine={m.sender === user?.id} />
        ))}
        <div ref={bottom} />
      </div>

      <Composer conversationId={conversation.id} queryKey={key} />
    </section>
  )
}

function Bubble({ message, mine }: { message: Message; mine: boolean }) {
  return (
    <div className={`flex ${mine ? 'justify-end' : 'justify-start'}`}>
      <div className={`max-w-[80%] rounded-2xl px-3 py-2 shadow-sm ${mine ? 'rounded-br-sm bg-primary-light' : 'rounded-bl-sm bg-surface'}`}>
        {message.product && (
          <Link to={`/produit/${message.product.slug}`} className="mb-2 flex items-center gap-2 rounded-xl bg-white/70 p-2 ring-1 ring-ink/5">
            {message.product.image && <img src={message.product.image} alt="" className="size-12 rounded-lg object-cover" />}
            <span className="text-sm">
              <span className="block font-medium">{message.product.name}</span>
              <Price amount={message.product.price} className="text-primary-dark" />
            </span>
          </Link>
        )}
        {message.type === 'image' && message.attachment && (
          <a href={message.attachment} target="_blank" rel="noreferrer">
            <img src={message.attachment} alt="" className="max-h-64 rounded-xl" />
          </a>
        )}
        {message.type === 'voice' && message.attachment && <audio controls src={message.attachment} className="max-w-full" />}
        {message.type === 'file' && message.attachment && (
          <a href={message.attachment} target="_blank" rel="noreferrer" className="text-sm underline">📎 Fichier joint</a>
        )}
        {message.content && <p className="whitespace-pre-line break-words">{message.content}</p>}
        <p className="mt-0.5 flex items-center justify-end gap-1 text-[11px] text-ink/50">
          {timeAgo(message.created_at)}
          {mine && (message.read_at ? <CheckCheck className="size-3.5 text-blue-600" /> : <Check className="size-3.5" />)}
        </p>
      </div>
    </div>
  )
}

function Composer({ conversationId, queryKey }: { conversationId: number; queryKey: unknown[] }) {
  const queryClient = useQueryClient()
  const [text, setText] = useState('')
  const [sending, setSending] = useState(false)
  const [recording, setRecording] = useState<MediaRecorder | null>(null)
  const [error, setError] = useState<string | null>(null)
  const lastTyping = useRef(0)

  async function send(payload: FormData | { content: string }) {
    setSending(true)
    setError(null)
    try {
      const { data } = await api.post<Message>(`/conversations/${conversationId}/messages/`, payload)
      queryClient.setQueryData<InfiniteData<MessagePage>>(queryKey, (old) => {
        if (!old || old.pages[0].results.some((m) => m.id === data.id)) return old
        const [first, ...rest] = old.pages
        return { ...old, pages: [{ ...first, results: [data, ...first.results] }, ...rest] }
      })
      queryClient.invalidateQueries({ queryKey: ['conversations'] })
    } catch (e) {
      setError(errorMessage(e))
    } finally {
      setSending(false)
    }
  }

  function submit(e: FormEvent) {
    e.preventDefault()
    if (!text.trim()) return
    send({ content: text.trim() })
    setText('')
  }

  function sendFile(file: File) {
    const data = new FormData()
    data.append('attachment', file)
    send(data)
  }

  async function toggleRecording() {
    if (recording) {
      recording.stop()
      return
    }
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      const recorder = new MediaRecorder(stream)
      const chunks: Blob[] = []
      recorder.ondataavailable = (e) => chunks.push(e.data)
      recorder.onstop = () => {
        stream.getTracks().forEach((t) => t.stop())
        setRecording(null)
        const type = recorder.mimeType || 'audio/webm'
        sendFile(new File([new Blob(chunks, { type })], `vocal.${type.includes('ogg') ? 'ogg' : type.includes('mp4') ? 'm4a' : 'webm'}`, { type }))
      }
      recorder.start()
      setRecording(recorder)
    } catch {
      setError("Impossible d'accéder au micro.")
    }
  }

  return (
    <form onSubmit={submit} className="border-t border-ink/5 p-2">
      <ErrorBox message={error} />
      <div className="flex items-center gap-1">
        <label className="cursor-pointer rounded-full p-2 text-ink/60 hover:bg-ink/5" aria-label="Envoyer une photo">
          <ImagePlus className="size-5" />
          <input type="file" accept="image/*" className="hidden" onChange={(e) => e.target.files?.[0] && sendFile(e.target.files[0])} />
        </label>
        {recording ? (
          <p className="flex-1 animate-pulse px-3 text-red-600">● Enregistrement… touchez ■ pour envoyer</p>
        ) : (
          <input
            value={text}
            onChange={(e) => {
              setText(e.target.value)
              if (Date.now() - lastTyping.current > 2500) {
                lastTyping.current = Date.now()
                api.post(`/conversations/${conversationId}/typing/`).catch(() => {})
              }
            }}
            placeholder="Votre message…"
            className="min-w-0 flex-1 rounded-full bg-background px-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/40"
          />
        )}
        {text.trim() ? (
          <button type="submit" disabled={sending} className="rounded-full bg-primary p-2.5 hover:bg-primary-hover" aria-label="Envoyer">
            {sending ? <Loader2 className="size-5 animate-spin" /> : <Send className="size-5" />}
          </button>
        ) : (
          <button
            type="button"
            onClick={toggleRecording}
            className={`rounded-full p-2.5 ${recording ? 'bg-red-600 text-white' : 'bg-primary hover:bg-primary-hover'}`}
            aria-label={recording ? "Arrêter et envoyer" : 'Message vocal'}
          >
            {recording ? <Square className="size-5" /> : <Mic className="size-5" />}
          </button>
        )}
      </div>
    </form>
  )
}
