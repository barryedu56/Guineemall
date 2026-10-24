# Feuille de route GuinéeMall v2

Validée le 24/10/2026. ✅ = fait · 🔑 = fait, mais il faut des clés ou un compte marchand pour l'activer en vrai.

## V2.0 : migration + confiance
- [x] Modèles migrés depuis Laravel (utilisateurs, boutiques, catalogue, commandes, panier, chat)
- [x] **Statuts** de boutique sur 24 h, liés à un produit, avec la liste de qui les a vus
- [x] API REST et interfaces React : client, vendeur, livreur (l'admin passe par /admin de Django)
- [x] Chat en temps réel (WebSocket) avec photos et messages vocaux
- [x] 🔑 Paiement Orange Money / MTN MoMo + paiement à la livraison (mode simulation en attendant les clés)
- [x] Paiement sécurisé (séquestre) : versé à la boutique après confirmation de réception, ou automatiquement après 3 jours
- [x] Badge « Vendeur vérifié » : envoi des justificatifs, validation dans l'admin
- [x] Avis et notes avec photos (acheteurs vérifiés uniquement), réponses des boutiques
- [x] Adresse par repère + GPS
- [x] Tarifs de livraison par commune et ville (modifiables dans l'admin)
- [x] 🔑 Notifications WhatsApp (commande, statut, paiement) + notifications dans le site en temps réel
- [x] PWA : installable, cache du catalogue et des images pour les connexions faibles

## V2.1 : croissance par les réseaux sociaux
- [x] Image de partage automatique (statut WhatsApp, Facebook, TikTok)
- [x] Bouton « Commander via WhatsApp »
- [x] Programme ambassadeur : lien de partage, 2 % de commission versés dans le portefeuille
- [x] Parrainage : bon de 10 000 GNF pour le filleul, puis pour le parrain à sa 1re commande livrée
- [x] Opérations commerciales (Tabaski, Ramadan…) et codes promo (plateforme ou boutique)
- [x] Tableau de bord vendeur (ventes, vues, conversion, meilleurs produits) et portefeuille avec retraits

## V2.2 : logistique et expansion
- [x] Réseau de livreurs à moto : courses, suivi de position en direct, encaissement des espèces
- [x] Points relais
- [x] Ventes en direct (vidéo YouTube / Facebook / TikTok) avec produits épinglés et commentaires en direct
- [x] Recherche vocale et messages vocaux
- [ ] Interface en Pular, Malinké et Soussou : **nécessite des traducteurs natifs** (voir README)
- [x] 🔑 Achat par la diaspora : paiement par carte (Stripe), prix affichés aussi en € / $, livraison à un proche

## Modèle économique (en place)
Commission sur les ventes (8 % par défaut, réglable par boutique), abonnement premium (commission réduite),
réductions plateforme financées sur la commission, commission ambassadeur prise sur la marge.

## Avant la mise en ligne
- Ouvrir les comptes marchands Orange Money, MTN MoMo et Stripe, renseigner les clés (`PAYMENT_MODE=live`)
- Créer le modèle de message WhatsApp `guineemall_notification` dans le WhatsApp Manager de Meta
- Ajuster les tarifs de livraison et les points relais réels dans l'admin
- Hébergement : voir « Production » dans le README
