import { useEffect, useRef, useState } from 'react'

const MAX_SECONDS = 120

/**
 * Enregistrement d'un message vocal : toucher le micro démarre, ➤ envoie, 🗑 annule.
 * Envoi automatique au bout de MAX_SECONDS. Le fichier est passé à onRecorded.
 */
export function useVoiceRecorder(onRecorded: (file: File) => void, onError: (message: string) => void) {
  const [recorder, setRecorder] = useState<MediaRecorder | null>(null)
  const [seconds, setSeconds] = useState(0)
  const cancelled = useRef(false)

  useEffect(() => {
    if (!recorder) return
    const timer = setInterval(() => setSeconds((s) => s + 1), 1000)
    return () => clearInterval(timer)
  }, [recorder])
  useEffect(() => {
    if (recorder && seconds >= MAX_SECONDS) recorder.stop()
  }, [recorder, seconds])

  async function start() {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      const media = new MediaRecorder(stream)
      const chunks: Blob[] = []
      cancelled.current = false
      media.ondataavailable = (e) => chunks.push(e.data)
      media.onstop = () => {
        stream.getTracks().forEach((t) => t.stop())
        setRecorder(null)
        setSeconds(0)
        if (cancelled.current || !chunks.length) return
        const type = media.mimeType || 'audio/webm'
        const ext = type.includes('ogg') ? 'ogg' : type.includes('mp4') ? 'm4a' : 'webm'
        onRecorded(new File([new Blob(chunks, { type })], `vocal.${ext}`, { type }))
      }
      media.start()
      setRecorder(media)
    } catch {
      onError("Impossible d'accéder au micro. Autorisez-le dans votre navigateur.")
    }
  }

  return {
    recording: recorder !== null,
    seconds,
    start,
    send: () => recorder?.stop(),
    cancel: () => {
      cancelled.current = true
      recorder?.stop()
    },
  }
}
