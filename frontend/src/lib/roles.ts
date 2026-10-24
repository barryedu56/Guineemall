import type { Role } from './types'

/** Page d'arrivée de chaque rôle après connexion. */
export const ROLE_HOME: Record<Role, string> = {
  client: '/',
  admin: '/',
  vendeur: '/vendeur',
  livreur: '/livreur',
}

/** Destination après connexion : le paramètre ?suivant= s'il s'agit d'une page du site, sinon l'accueil du rôle. */
export function afterLogin(role: Role, next: string | null) {
  return next && next.startsWith('/') && !next.startsWith('//') ? next : ROLE_HOME[role]
}
