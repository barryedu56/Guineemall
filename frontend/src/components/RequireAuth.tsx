import type { ReactNode } from 'react'
import { Navigate, useLocation, useSearchParams } from 'react-router-dom'

import { useAuth } from '../context/auth'
import { afterLogin, ROLE_HOME } from '../lib/roles'
import type { Role } from '../lib/types'
import { Spinner } from './ui'

export default function RequireAuth({ role, children }: { role?: Role; children: ReactNode }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <Spinner />
  if (!user) return <Navigate to={`/connexion?suivant=${encodeURIComponent(location.pathname + location.search)}`} replace />
  if (role && user.role !== role) return <Navigate to={ROLE_HOME[user.role]} replace />
  return children
}

/**
 * Pages réservées aux visiteurs (connexion, inscription). Dès que l'utilisateur est connecté
 * (y compris juste après avoir validé le formulaire), il est renvoyé vers la page demandée
 * ou l'accueil de son rôle. C'est la seule redirection après connexion.
 */
export function GuestOnly({ children }: { children: ReactNode }) {
  const { user, loading } = useAuth()
  const [params] = useSearchParams()
  if (loading) return <Spinner />
  if (user) return <Navigate to={afterLogin(user.role, params.get('suivant'))} replace />
  return children
}
