# PHOTOCOM

Site vitrine et dashboard d'administration pour PHOTOCOM — matériel photo, vidéo et cinéma au Maroc.

## Stack

- PHP 8.2+
- SQLite (base de données locale)
- Import unique depuis WordPress / WooCommerce

## Installation

```bash
cd photocom
cp .env.example .env
# Éditez .env avec vos informations (admin + WordPress)
php scripts/init-db.php
php -S localhost:8000 router.php
```

## Accès

| URL | Description |
|-----|-------------|
| http://localhost:8000 | Site vitrine |
| http://localhost:8000/admin/login.php | Dashboard admin |

Identifiants par défaut (après init) : voir `.env` (`ADMIN_EMAIL` / `ADMIN_PASSWORD`).

## Import WordPress (une seule fois)

1. Créez des clés API dans WordPress : **WooCommerce → Réglages → Avancé → REST API**
2. Renseignez dans `.env` :
   - `WORDPRESS_URL`
   - `WOOCOMMERCE_KEY`
   - `WOOCOMMERCE_SECRET`
3. Connectez-vous au dashboard → **Import WordPress** → **Lancer l'import**

L'import récupère les catégories, produits et télécharge les images en local.

## Structure

```
photocom/
├── admin/          Dashboard (produits, catégories, import)
├── public/         Vitrine publique
├── lib/            Classes PHP
├── database/       SQLite + schéma
├── uploads/        Images produits
├── config/         Configuration
└── scripts/        Scripts utilitaires
```

## Production

- Utilisez HTTPS
- Changez le mot de passe admin
- Copiez `.env` avec les vraies valeurs
- Sauvegardez `database/photocom.sqlite` et `uploads/` régulièrement
