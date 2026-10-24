# GuinéeMall v2

Marketplace multi-vendeurs pour la Guinée, avec Django en back et React en front.
Elle remplace la version Laravel (`../Guineemall-main`, conservée comme référence).

## Stack

| Couche | Technologie |
|---|---|
| Front | React 19 + TypeScript, Vite, Tailwind CSS 4, React Router, TanStack Query |
| API | Django 5.2 LTS + Django REST Framework, authentification JWT |
| Temps réel | Django Channels (chat, notifications) |
| Base de données | PostgreSQL |
| Cache / temps réel | Redis |

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
    chat/          conversations client ↔ boutique
    statuts/       statuts éphémères de 24 h des boutiques
frontend/          application React
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
.venv/bin/python manage.py runserver      # http://127.0.0.1:8000/admin
```

### Front
```bash
cd frontend
npm install
npm run dev                   # http://localhost:5173
```

### Tests
```bash
cd backend && .venv/bin/python manage.py test apps
```

## API

Toutes les routes sont préfixées par `/api/`. L'authentification se fait par JWT, avec l'en-tête `Authorization: Bearer <access>`.

| Espace | Routes |
|---|---|
| Comptes | `auth/register/` · `auth/register/vendor/` · `auth/token/` · `auth/token/refresh/` · `auth/me/` · `auth/addresses/` |
| Catalogue (public) | `categories/` · `products/` (filtres `category`, `vendor`, `min_price`, `max_price`, `in_stock`, `search`, `ordering`) · `shops/` |
| Client | `favorites/` · `favorites/<slug>/` · `cart/` · `cart/clear/` · `orders/` (POST = passer commande) · `orders/<id>/cancel/` |
| Statuts | `statuts/` (fil groupé par boutique) · `statuts/<id>/view/` |
| Vendeur | `vendor/shop/` · `vendor/products/` (+ `images/`) · `vendor/orders/` (+ `status/`) · `vendor/statuts/` (+ `viewers/`) |

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
