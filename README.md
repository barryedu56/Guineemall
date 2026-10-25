# GuinéeMall v2

Marketplace multi-vendeurs pour la Guinée, avec Django en back et React en front.
Elle remplace la version Laravel (`../Guineemall-main`, conservée comme référence).

## Stack

| Couche | Technologie |
|---|---|
| Front | React 19 + TypeScript, Vite, Tailwind CSS 4, React Router, TanStack Query |
| API | Django 5.2 LTS + Django REST Framework, authentification JWT |
| Temps réel | Django Channels (chat, notifications, suivi livreur, directs) |
| Tâches de fond | Celery (commandes impayées, séquestre, WhatsApp) |
| Base de données | PostgreSQL |
| Cache / temps réel / file de tâches | Redis |
| Paiements | Orange Money Web Pay, MTN MoMo Collection, Stripe Checkout (+ mode simulation) |

## Structure

```
backend/
  config/          réglages Django
  apps/
    core/          modèles de base communs (dates, suppression douce, slug)
    accounts/      utilisateurs (admin / vendeur / client), adresses, journal admin
    vendors/       boutiques, badge « vérifié »
    catalog/       catégories, produits, images, favoris
    orders/        panier, commandes, commandes par vendeur, historique
    chat/          conversations client ↔ boutique (REST + WebSocket)
    statuts/       statuts éphémères de 24 h des boutiques
    payments/      paiements en ligne (Orange Money, MTN, carte, simulation)
    wallet/        portefeuilles, séquestre, retraits
    delivery/      zones et tarifs, points relais, livreurs et courses
    promotions/    codes promo, opérations commerciales
    notifications/ notifications dans le site + WhatsApp
    lives/         ventes en direct
frontend/src/
  lib/             client API (JWT renouvelé automatiquement), types, formatage GNF
  context/         connexion et panier (le panier invité est fusionné à la connexion)
  components/      en-tête, navigation mobile, carte produit, barre et lecteur de statuts
  pages/           pages client
  pages/vendor/    espace vendeur : tableau de bord, commandes, produits, statuts, boutique
```

## Installation

### Prérequis
PostgreSQL, Redis, Python 3.12+, Node 22+

```bash
# Ubuntu
sudo apt install postgresql redis-server
sudo -u postgres psql -c "CREATE USER guineemall WITH PASSWORD 'guineemall' CREATEDB;"
sudo -u postgres psql -c "CREATE DATABASE guineemall OWNER guineemall;"
```

### Back
```bash
cd backend
python3 -m venv .venv
.venv/bin/pip install -r requirements.txt
cp .env.example .env          # puis renseigner SECRET_KEY
.venv/bin/python manage.py migrate
.venv/bin/python manage.py createsuperuser
.venv/bin/python manage.py runserver      # http://127.0.0.1:8000/admin (HTTP + WebSocket)

# Dans deux autres terminaux : tâches de fond et tâches planifiées
.venv/bin/celery -A config worker -l info
.venv/bin/celery -A config beat -l info
```

Sans worker Celery, le site fonctionne, mais les messages WhatsApp ne partent pas et les
commandes impayées ne sont pas annulées automatiquement.

### Front
```bash
cd frontend
npm install
npm run dev                   # http://localhost:5173
```

### Données de démonstration (développement uniquement)
⚠️ Ne pas lancer sur une base contenant de vraies données : la commande crée des comptes, boutiques,
statuts et tarifs fictifs.
```bash
cd backend && .venv/bin/python manage.py seed_demo
```
La commande crée 3 boutiques, 15 produits avec images, des statuts, les tarifs de livraison
de Conakry et des grandes villes, 3 points relais, une opération Tabaski et un livreur. Mot de passe de tous les comptes : `demo1234`.

| Compte | Email |
|---|---|
| Client | `client@demo.gn` (code promo `BIENVENUE`) |
| Livreur | `livreur@demo.gn` |
| Vendeur (Kipé Tech) | `kipe.tech@demo.gn` |
| Vendeur (Madina Mode) | `madina.mode@demo.gn` |
| Vendeur (Fouta Saveurs) | `fouta.saveurs@demo.gn` |

### Tests
```bash
cd backend && .venv/bin/python manage.py test apps
```

## Paiements

En développement (`PAYMENT_MODE=simulation`), Orange Money, MTN et la carte renvoient vers une page
GuinéeMall où l'on choisit « réussi » ou « refusé ». Aucun argent réel n'est débité.

Pour la production, il faut renseigner les clés dans `.env` et passer `PAYMENT_MODE=live`.
- **Orange Money** : compte marchand Orange Guinée + application sur developer.orange.com (API « Orange Money Web Pay »)
- **MTN MoMo** : compte marchand MTN Guinée + abonnement « Collection » sur momodeveloper.mtn.com
- **Carte (diaspora)** : compte Stripe ; le montant est converti en euros avec `RATE_EUR_GNF`

Les appels de retour des opérateurs ne sont jamais crus sur parole : ils déclenchent une vérification
du statut directement auprès de l'opérateur.

### Comment circule l'argent
- **Paiement en ligne** : l'argent reste en séquestre. Il est versé dans le portefeuille de la boutique
  (moins 8 % de commission) quand le client confirme la réception, ou automatiquement 3 jours après la livraison.
- **Paiement à la livraison** : celui qui livre encaisse. Si c'est la boutique, la commission est débitée de
  son portefeuille ; si c'est un livreur GuinéeMall, son portefeuille est débité des espèces encaissées et
  crédité de sa course.
- Les retraits vers Orange Money ou MTN sont demandés dans le portefeuille et validés par un admin
  (Admin › Demandes de retrait › « Marquer comme payé »).

## Notifications WhatsApp

Renseigner `WHATSAPP_TOKEN` et `WHATSAPP_PHONE_NUMBER_ID` (API WhatsApp Cloud de Meta), puis créer dans le
WhatsApp Manager un modèle nommé `guineemall_notification`, en français, avec le corps :
```
*{{1}}*
{{2}}
```
Sans jeton, les messages sont seulement écrits dans les logs du worker Celery.

## Back-office (dans le site : /administration)

Réservé à l'équipe, avec double authentification. Tout se gère depuis le site :

| Rubrique | Actions |
|---|---|
| Commandes | recherche et filtres, détail complet, remboursement effectué, déblocage d'un code de livraison |
| Retraits / Versements | payer (avec référence) ou refuser, confirmer les espèces reversées |
| Boutiques | approuver, suspendre (avec motif), retirer le badge, premium, commission propre |
| Badges vérifiés | voir les pièces d'identité (consultation journalisée), accorder ou refuser |
| Livreurs / Utilisateurs | valider, suspendre, désactiver un compte, réinitialiser la double authentification |
| Produits / Avis / Catégories | masquer un produit ou un avis, gérer les catégories |
| Codes promo / Opérations | créer, modifier, choisir les produits d'une opération |
| Livraison | zones et tarifs, points relais |
| Réglages du site | textes, contacts, réseaux sociaux, modes de paiement |
| Journal d'audit | qui a fait quoi |

L'admin Django (« Admin technique ») reste disponible pour les cas avancés.

## Admin technique Django (/admin)

- **Réglages du site** : nom, slogan, bandeau d'annonce, texte et image de l'accueil, coordonnées,
  réseaux sociaux, modes de paiement proposés. Le front ne contient aucune de ces valeurs en dur :
  il les lit sur `/api/config/`, tout comme les listes de statuts, les villes et communes livrées, etc.

- **Boutiques** : action « Approuver » (le vendeur est notifié)
- **Demandes de vérification** : voir les documents, action « Accepter (donner le badge) »
- **Livreurs** : action « Valider »
- **Zones de livraison** : tarifs modifiables directement dans la liste
- **Codes promo** et **Opérations commerciales** (Tabaski, Ramadan…)
- **Demandes de retrait**, **Paiements** (lecture seule), **Mouvements** de portefeuille

## Production

- Lancer l'application avec `daphne config.asgi:application` (HTTP + WebSocket), derrière Nginx en HTTPS
- `DEBUG=False`, vrai `SECRET_KEY`, `ALLOWED_HOSTS`, `FRONTEND_URL` / `BACKEND_URL` publics
- `python manage.py collectstatic`, front construit avec `npm run build` et servi par Nginx
- Nginx : `/api`, `/admin`, `/media`, `/static` → Django ; `/ws` → Django en WebSocket ; le reste → `frontend/dist`
- Celery worker + beat en service (systemd ou supervisor)
- Sauvegarde quotidienne de PostgreSQL et du dossier `media/`

## Sécurité

| Protection | Où |
|---|---|
| Fichiers envoyés reconnus à leur **contenu réel** (HTML/SVG déguisés refusés), renommés au hasard | `apps/core/uploads.py` |
| **Pièces d'identité** en stockage privé (`private_media/`), lisibles par l'équipe seulement, consultations tracées | `apps/core/storage.py`, `private_views.py` |
| **Limitation des tentatives** : connexion (par IP et par compte), inscription, codes promo, retraits, messages… | `apps/core/throttling.py` |
| Jetons courts (15 min), **rotation + révocation**, vraie déconnexion, déconnexion de tous les appareils au changement de mot de passe | `SIMPLE_JWT` |
| **Double authentification** obligatoire pour l'équipe ; page de connexion de l'admin Django fermée ; adresse de l'admin secrète (`ADMIN_URL`) | `apps/accounts/two_factor.py` |
| **Journal d'audit** en lecture seule (argent, validations, sécurité) + alertes à l'équipe | `apps/core/audit.py` |
| En-têtes de sécurité, CSP, HTTPS/HSTS | `deploy/nginx.conf`, `settings.py` |

Avant la mise en ligne : `DEBUG=False`, un vrai `SECRET_KEY`, `ADMIN_URL` difficile à deviner, puis
`python manage.py check --deploy` (doit être propre).

**Première connexion de l'admin :** le tableau de bord demande d'activer la double authentification
(scanner le QR code avec Google Authenticator, Microsoft Authenticator ou Authy). Gardez la clé affichée
en lieu sûr : sans le téléphone, elle permet de réinstaller le compte dans l'application.

## Sauvegardes

`deploy/backup.sh` sauvegarde chaque nuit la base et les fichiers (chiffrés, conservés 14 jours) ;
la restauration est indiquée en fin de script. Le dossier `backups/` (non versionné) contient la
sauvegarde faite avant le nettoyage de la base du 25/10/2026.

## Langues locales

L'interface est en français. La recherche vocale et les messages vocaux sont en place pour ceux qui
préfèrent parler. Les traductions en Pular, Malinké et Soussou doivent être faites par des locuteurs natifs :
une traduction automatique serait de mauvaise qualité dans ces langues. Il suffira de fournir les textes.

## API

Toutes les routes sont préfixées par `/api/`. L'authentification se fait par JWT, avec l'en-tête `Authorization: Bearer <access>`.

| Espace | Routes |
|---|---|
| Comptes | `auth/register/` · `auth/register/vendor/` · `auth/token/` · `auth/token/refresh/` · `auth/me/` · `auth/addresses/` |
| Catalogue (public) | `categories/` · `products/` (filtres `category`, `vendor`, `min_price`, `max_price`, `in_stock`, `search`, `ordering`) · `shops/` |
| Client | `favorites/` · `favorites/<slug>/` · `cart/` · `cart/clear/` · `orders/` (POST = passer commande) · `orders/<id>/cancel/` |
| Commande | `orders/quote/` (devis) · `orders/` · `orders/<id>/pay/` · `orders/<id>/confirm-reception/` · `orders/<id>/cancel/` · `payments/<ref>/` |
| Statuts | `statuts/` (fil groupé par boutique) · `statuts/<id>/view/` |
| Social | `conversations/` (+ `start/`, `messages/`, `read/`, `typing/`) · `notifications/` · `auth/referral/` · `products/<slug>/reviews/` · `products/<slug>/share-image/` |
| Promos et directs | `campaigns/` · `lives/` · `delivery/zones/` · `delivery/pickup-points/` · `config/` |
| Portefeuille | `wallet/` · `wallet/payouts/` |
| Vendeur | `vendor/shop/` · `vendor/products/` · `vendor/orders/` · `vendor/statuts/` · `vendor/coupons/` · `vendor/reviews/` · `vendor/lives/` · `vendor/stats/` · `vendor/verification/` |
| Livreur | `auth/register/rider/` · `rider/profile/` · `rider/deliveries/` (+ `available/`, `accept/`, `pickup/`, `deliver/`, `location/`) |
| Temps réel | `ws/?token=<jwt>` (messages, notifications, position du livreur) · `ws/live/<id>/` (commentaires d'un direct) |

L'espace vendeur n'est accessible qu'une fois la boutique approuvée par un admin.

## Différences avec la version Laravel

- **Prix en GNF entiers** (`PositiveBigIntegerField`) au lieu de `decimal(10,2)`, qui plafonnait à environ 100 millions GNF.
- **Adresses structurées** : ville, commune, quartier, repère et GPS, au lieu d'un champ texte libre.
  L'adresse est recopiée dans la commande pour que l'historique reste juste.
- **Les articles commandés gardent le nom et le prix** du produit au moment de l'achat.
- **Plusieurs images par produit** (`ProductImage`).
- **Chat** : conversation liée à la boutique (et non plus à un utilisateur), unique par couple client/boutique.
  Les champs `receiver_id` et `is_read` ont été supprimés car on peut les déduire.
  Les messages vocaux sont prévus.
- **Panier** : contraintes d'unicité correctes pour les clients connectés et pour les invités.
- **Stock** : il est remis quand un vendeur annule sa partie de commande. Quand le client annule,
  seules les parties qui ne sont pas encore en préparation sont annulées, et seul leur stock est remis.
- **Statut de commande** : une commande dont toutes les parties sont livrées ou annulées passe en « livrée »
  au lieu de rester bloquée « en cours ».
