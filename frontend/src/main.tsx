import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'

import App from './App'
import { AuthProvider } from './context/AuthProvider'
import { CartProvider } from './context/CartProvider'
import { RealtimeProvider } from './context/RealtimeProvider'
import { ToastProvider } from './context/ToastProvider'
import './index.css'
import { captureAffiliateCode } from './lib/prefs'

// Lien d'ambassadeur ou de parrainage (?ref=CODE) mémorisé dès l'arrivée
captureAffiliateCode()

const queryClient = new QueryClient({
  defaultOptions: {
    // Connexions mobiles parfois lentes : on évite de recharger à chaque retour sur l'onglet
    queries: { staleTime: 60_000, refetchOnWindowFocus: false, retry: 1 },
  },
})

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <RealtimeProvider>
          <CartProvider>
            <ToastProvider>
              <App />
            </ToastProvider>
          </CartProvider>
        </RealtimeProvider>
      </AuthProvider>
    </QueryClientProvider>
  </StrictMode>,
)
