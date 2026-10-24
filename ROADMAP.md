# Feuille de route GuinéeMall v2

Validée le 24/10/2026.

## V2.0 : migration + confiance
- [x] Modèles migrés depuis Laravel (utilisateurs, boutiques, catalogue, commandes, panier, chat)
- [x] **Statuts** de boutique sur 24 h, liés à un produit, avec la liste de qui les a vus (modèles + API)
- [x] API REST (authentification, catalogue, panier, commandes, statuts), 31 tests
- [ ] Chat en temps réel (Channels)
- [ ] Paiement Orange Money / MTN MoMo + paiement à la livraison
- [ ] Paiement sécurisé (séquestre) : le vendeur est payé après confirmation de la réception
- [ ] Badge « Vendeur vérifié » (champ prêt, workflow à faire)
- [ ] Avis et notes avec photos (acheteurs vérifiés uniquement)
- [ ] Adresse par repère + GPS (modèle prêt)
- [ ] Tarifs de livraison par commune et ville
- [ ] Notifications WhatsApp (commande, message, suivi)
- [ ] PWA : installable, légère, fonctionne avec une connexion faible
- [x] Interfaces React : client et vendeur (l'admin passe par /admin de Django)

## V2.1 : croissance par les réseaux sociaux
- [ ] Image de partage automatique (Statut WhatsApp, Facebook, TikTok)
- [ ] Bouton « Commander via WhatsApp »
- [ ] Programme d'affiliation / ambassadeurs (commission)
- [ ] Parrainage
- [ ] Promotions par événement (Tabaski, Ramadan, rentrée…) et codes promo
- [ ] Tableau de bord vendeur (ventes, vues, retraits)

## V2.2 : logistique et expansion
- [ ] Réseau de livreurs à moto + suivi en temps réel
- [ ] Points relais
- [ ] Lives shopping
- [ ] Langues locales (Pular, Malinké, Soussou) + recherche vocale
- [ ] Achat par la diaspora (paiement par carte, livraison à la famille)

## Modèle économique
Commission sur les ventes, produits sponsorisés, abonnement vendeur premium, marge sur la livraison.
