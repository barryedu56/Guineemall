import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'

import { useAuth } from '../context/auth'
import type { Role } from '../lib/types'
import { Spinner } from './ui'

export default function RequireAuth({ role, children }: { role?: Role; children: ReactNode }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <Spinner />
  if (!user) return <Navigate to={`/connexion?suivant=${encodeURIComponent(location.pathname)}`} replace />
  if (role && user.role !== role) return <Navigate to="/" replace />
  return children
}
