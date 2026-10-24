import { useAuth } from './auth'

/**
 * Vrai si l'utilisateur connecté est le propriétaire de la boutique `shopSlug`.
 * Le propriétaire voit ses pages publiques en « aperçu » : boutons de gestion
 * à la place des boutons d'achat, de favori et de contact (le serveur refuse aussi ces actions).
 */
export function useIsOwnShop(shopSlug: string | undefined | null) {
  const { user } = useAuth()
  return !!shopSlug && user?.shop?.slug === shopSlug
}
