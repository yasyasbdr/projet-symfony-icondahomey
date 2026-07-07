# Déploiement sur Render (Docker + PostgreSQL)

> Render ne propose pas de MySQL gratuit → on utilise **PostgreSQL** (gratuit).
> Doctrine gère la différence automatiquement, le code ne change pas.

## Étape 0 — Committer les fichiers de déploiement

Assure-toi que `Dockerfile`, `docker-entrypoint.sh`, `.dockerignore` et tes
**photos** sont bien sur GitHub :

```bash
git add -A
git add -f public/uploads/*.jpg     # les images sont gitignorées, on les force
git commit -m "Deploiement Render (Docker + Postgres)"
git push
```

## Étape 1 — Créer la base PostgreSQL

1. Sur **dashboard.render.com** → **New +** → **PostgreSQL**.
2. Nom : `icon-dahomey-db`, région au choix, plan **Free** → **Create Database**.
3. Attends que le statut passe à **Available**.
4. Copie l'**Internal Database URL** (elle commence par `postgres://...`).

## Étape 2 — Créer le service web

1. **New +** → **Web Service** → connecte ton dépôt GitHub `projet-symfony-icondahomey`.
2. **Runtime** : Docker (détecté automatiquement grâce au `Dockerfile`).
3. **Branch** : `main`. **Instance Type** : **Free**.
4. **Create Web Service** (le premier build va démarrer).

## Étape 3 — Variables d'environnement

Dans le service web → onglet **Environment** → ajoute ces variables :

| Clé | Valeur |
|-----|--------|
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | une chaîne aléatoire (ex : résultat de `openssl rand -hex 16`) |
| `DATABASE_URL` | l'Internal URL de l'étape 1, **modifiée** (voir ci-dessous) |
| `MAILER_DSN` | `null://null` |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=1` |
| `STRIPE_SECRET_KEY` | (vide, ou ta clé `sk_test_...` pour Stripe réel) |
| `SMS_API_URL` | (vide) |
| `SMS_API_TOKEN` | (vide) |

**Transforme le `DATABASE_URL`** : Render te donne `postgres://user:pass@host/db`.
Mets-le sous cette forme (scheme `postgresql` + version + charset) :

```
postgresql://user:pass@host:5432/db?serverVersion=16&charset=utf8
```

Enregistre → Render relance un déploiement.

## Étape 4 — Créer le schéma + les données (une seule fois)

Quand le service est **Live**, ouvre l'onglet **Shell** du service web et lance :

```bash
php bin/console doctrine:schema:create
APP_ENV=dev php bin/console doctrine:fixtures:load --no-interaction
```

- `schema:create` crée toutes les tables sur PostgreSQL.
- `fixtures:load` (en env dev, car le bundle de fixtures est en dev) charge les
  produits, les photos, la commande de démo et les comptes de test.

## Étape 5 — Tester

Ouvre l'URL fournie par Render (`https://icon-dahomey-xxxx.onrender.com`).
Comptes de test :
- `cliente@icon-dahomey.local` / `password`
- `admin@icon-dahomey.local` / `password`
- `super@icon-dahomey.local` / `password`

---

## Dépannage

- **Le build échoue** → regarde les logs de build (onglet **Logs**). Souvent une
  extension PHP ou une dépendance ; copie l'erreur.
- **Page 500 au démarrage** → variable manquante (souvent `APP_SECRET` ou
  `DATABASE_URL` mal formée). Vérifie l'onglet Logs.
- **« relation does not exist »** → tu as oublié l'étape 4 (schéma non créé).
- **Les photos ne s'affichent pas** → tu as oublié `git add -f public/uploads/*.jpg`.
- **Premier chargement lent** → normal en plan Free, le service « s'endort »
  après inactivité et met ~30 s à se réveiller.

> Note : la base PostgreSQL gratuite de Render expire au bout de ~30 jours.
> C'est suffisant pour le rendu et la soutenance.
