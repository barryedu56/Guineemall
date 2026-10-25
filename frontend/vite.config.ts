import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
    // Application installable (PWA) : légère, utilisable avec une connexion faible
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg', 'apple-touch-icon.png'],
      manifest: {
        name: 'GuinéeMall',
        short_name: 'GuinéeMall',
        description: 'Le marché guinéen, dans votre poche.',
        lang: 'fr',
        start_url: '/',
        display: 'standalone',
        background_color: '#f8f9fa',
        theme_color: '#ffc107',
        icons: [
          { src: 'pwa-192.png', sizes: '192x192', type: 'image/png' },
          { src: 'pwa-512.png', sizes: '512x512', type: 'image/png' },
          { src: 'pwa-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        navigateFallbackDenylist: [/^\/api\//, /^\/admin\//, /^\/media\//, /^\/static\//, /^\/private\//, /^\/ws\//],
        runtimeCaching: [
          {
            // Catalogue : affiché depuis le cache si le réseau est lent, puis mis à jour
            urlPattern: ({ url }) => /^\/api\/(products|categories|shops|statuts|campaigns|config)/.test(url.pathname),
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'api-catalogue', expiration: { maxEntries: 200, maxAgeSeconds: 86400 } },
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/media/'),
            handler: 'CacheFirst',
            options: { cacheName: 'images', expiration: { maxEntries: 400, maxAgeSeconds: 30 * 86400 } },
          },
        ],
      },
    }),
  ],
  server: {
    // En développement, /api et /media sont redirigés vers Django.
    // changeOrigin: false garde l'hôte du front, donc les URL d'images renvoyées par l'API passent aussi par ici.
    proxy: {
      '/api': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      '/media': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      // Expressions exactes : '/admin' seul capturerait aussi '/administration' (pages du site)
      '^/admin/': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      '^/static/': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      '^/private/': { target: 'http://127.0.0.1:8000', changeOrigin: false },
      '/ws': { target: 'ws://127.0.0.1:8000', ws: true },
    },
  },
})
