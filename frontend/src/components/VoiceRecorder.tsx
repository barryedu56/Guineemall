import { Mic, Send, Trash2 } from 'lucide-react'

function formatDuration(seconds: number) {
  return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`
}

/** Bandeau affiché pendant l'enregistrement, à la place du champ texte. */
export function RecordingBar({ seconds, onCancel, onSend }: { seconds: number; onCancel: () => void; onSend: () => void }) {
  return (
    <div className="flex flex-1 items-center gap-2">
      <button type="button" onClick={onCancel} className="rounded-full p-2 text-ink/60 hover:bg-red-50 hover:text-red-600" aria-label="Annuler le vocal">
        <Trash2 className="size-5" />
      </button>
      <p className="flex flex-1 items-center gap-2 text-sm font-semibold text-red-600">
        <span className="size-2.5 animate-pulse rounded-full bg-red-600" />
        {formatDuration(seconds)}
        <span className="font-normal text-ink/50">Enregistrement…</span>
      </p>
      <button type="button" onClick={onSend} className="rounded-full bg-primary-dark p-2.5 text-white" aria-label="Envoyer le vocal">
        <Send className="size-4" />
      </button>
    </div>
  )
}

export function MicButton({ onClick, disabled }: { onClick: () => void; disabled?: boolean }) {
  return (
    <button type="button" onClick={onClick} disabled={disabled} className="rounded-full bg-primary-dark p-2.5 text-white disabled:opacity-40" aria-label="Enregistrer un message vocal">
      <Mic className="size-4" />
    </button>
  )
}
