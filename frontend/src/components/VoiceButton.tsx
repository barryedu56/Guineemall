import { Mic } from 'lucide-react'
import { useState } from 'react'

// API de reconnaissance vocale du navigateur (Chrome Android, Edge, Safari)
type SpeechRecognitionLike = {
  lang: string
  interimResults: boolean
  onresult: (e: { results: { 0: { transcript: string } }[] }) => void
  onend: () => void
  onerror: () => void
  start: () => void
}

function getRecognition(): SpeechRecognitionLike | null {
  const w = window as unknown as Record<string, new () => SpeechRecognitionLike>
  const Ctor = w.SpeechRecognition ?? w.webkitSpeechRecognition
  return Ctor ? new Ctor() : null
}

/** Recherche vocale : utile pour ceux qui préfèrent parler plutôt qu'écrire. */
export default function VoiceButton({ onResult }: { onResult: (text: string) => void }) {
  const [listening, setListening] = useState(false)
  if (!getRecognition()) return null

  function listen() {
    const recognition = getRecognition()!
    recognition.lang = 'fr-FR'
    recognition.interimResults = false
    recognition.onresult = (e) => onResult(e.results[0][0].transcript)
    recognition.onend = () => setListening(false)
    recognition.onerror = () => setListening(false)
    setListening(true)
    recognition.start()
  }

  return (
    <button
      type="button"
      onClick={listen}
      className={`absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-1.5 ${listening ? 'animate-pulse bg-red-100 text-red-600' : 'text-ink/50 hover:bg-ink/5'}`}
      aria-label="Recherche vocale"
    >
      <Mic className="size-5" />
    </button>
  )
}
